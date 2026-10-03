<?php

// Run explicitly with PHP; not part of PHPUnit. Uses random runtime-only records.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FirestoreCacheStore;
use App\Services\FirestoreDocumentStore;
use App\Services\FirestoreSessionHandler;
use Illuminate\Support\Carbon;

$documents = new FirestoreDocumentStore;
$prefix = 'verify-runtime-'.bin2hex(random_bytes(8));
$cache = new FirestoreCacheStore($documents, $prefix);
$sessions = new FirestoreSessionHandler($documents, 1, $prefix);
$sessionId = bin2hex(random_bytes(20));
$locks = [];
$report = ['prefix'=>$prefix, 'checks'=>[], 'passed'=>false, 'cleanupErrors'=>[]];
$failed = false;
$assert = static function (bool $condition, string $label) {
    if (!$condition) throw new RuntimeException($label);
};
$counts = static function () use ($documents): array {
    $counts=[];
    foreach (['admins','content','applications','visits'] as $collection) $counts[$collection]=$documents->count($collection);
    return $counts;
};

try {
    $report['before'] = $counts();
    $cache->put('value',['hello'=>'Firestore','enabled'=>false],120);
    $assert($cache->get('value')===['hello'=>'Firestore','enabled'=>false],'cache round trip');
    $assert($cache->add('counter',0,120),'atomic add');
    $assert(!$cache->add('counter',9,120),'add preserves existing record');
    $assert($cache->increment('counter',3)===3,'atomic increment');
    $assert($cache->decrement('counter')===2,'atomic decrement');
    $report['checks'][]='Cache values, atomic add, increment and decrement passed';

    $id=hash('sha256',$prefix."\0counter");
    $snapshot=$documents->snapshot('runtime_cache',$id);
    $assert($cache->increment('counter')===3,'concurrent update');
    $assert(!$documents->compareAndSwap('runtime_cache',$id,$snapshot['version'],$snapshot['data']),'stale update rejected');
    $assert(!$documents->deleteIfUnchanged('runtime_cache',$id,$snapshot['version']),'stale delete rejected');
    $assert($cache->get('counter')===3,'newer value preserved');
    $report['checks'][]='Live Firestore update-time preconditions rejected stale writes and deletes';

    $first=$cache->lock('session-lock',120);
    $second=$cache->lock('session-lock',120);
    $locks=[$first,$second];
    $assert($first->get(),'first lock acquired');
    $assert(!$second->get(),'second lock blocked');
    $assert(!$second->release(),'wrong lock owner denied');
    $assert($first->release(),'correct lock owner released');
    $assert($second->get(),'next lock acquired');
    $assert($second->release(),'next lock released');
    $report['checks'][]='Distributed session locks respect ownership';

    $payload=serialize(['admin'=>'test-only','csrf'=>'test-only']);
    $assert($sessions->write($sessionId,$payload),'session write');
    $freshSession=new FirestoreSessionHandler(new FirestoreDocumentStore,1,$prefix);
    $assert($freshSession->read($sessionId)===$payload,'session persisted between instances');
    $cache->put('expires','temporary',1);
    Carbon::setTestNow(Carbon::now()->addSeconds(61));
    $assert($freshSession->read($sessionId)==='','session expires');
    $assert($cache->get('expires')===null,'cache expires');
    $assert($freshSession->gc(60)>=1,'expired session garbage collection');
    $assert($cache->pruneExpired()>=1,'expired cache garbage collection');
    Carbon::setTestNow();
    $report['checks'][]='Sessions persist, expire, and are garbage-collected; expired cache entries are pruned';
} catch (Throwable $error) {
    $failed=true;
    $report['errorType']=get_class($error);
    // Only assertion labels are safe to display. Remote exception bodies stay private.
    if (get_class($error)===RuntimeException::class) $report['failedCheck']=$error->getMessage();
} finally {
    Carbon::setTestNow();
    foreach ($locks as $lock) {
        try { $lock->release(); }
        catch (Throwable $error) { $report['cleanupErrors'][]='lock: '.get_class($error); }
    }
    try {
        $sessions->destroy($sessionId);
        foreach (['value','counter','expires'] as $key) $cache->forget($key);
        $report['testRecordsCleaned']=$sessions->read($sessionId)==='' && $cache->many(['value','counter','expires'])===['value'=>null,'counter'=>null,'expires'=>null];
    } catch (Throwable $error) { $report['cleanupErrors'][]='records: '.get_class($error); }
    try {
        $report['after']=$counts();
        $report['mainCollectionsUnchanged']=isset($report['before']) && $report['before']===$report['after'];
    } catch (Throwable $error) { $report['cleanupErrors'][]='counts: '.get_class($error); }
    $report['passed']=!$failed && !$report['cleanupErrors'] && ($report['testRecordsCleaned'] ?? false) && ($report['mainCollectionsUnchanged'] ?? false);
    echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
exit($report['passed'] ? 0 : 1);
