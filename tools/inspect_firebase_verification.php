<?php

// Read-only proof for verify_firebase_workflow.py. Never emits passwords or tokens.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    if (config('school.store') !== 'firestore' || config('school.firebase_project') !== 'sd-ceria-nusantara') {
        throw new RuntimeException('Expected Firestore project is not active.');
    }
    $store = new App\Services\FirestoreDocumentStore;
    $canonical = function (mixed $value) use (&$canonical): mixed {
        if (!is_array($value)) return $value;
        if (!array_is_list($value)) ksort($value);
        return array_map($canonical, $value);
    };
    $action = $input['action'] ?? '';
    if ($action === 'state') {
        $sqlite = Illuminate\Support\Facades\DB::table('documents')->orderBy('collection')->orderBy('document_id')->get();
        $counts = [];
        foreach (['admins', 'content', 'applications', 'visits'] as $collection) {
            $counts[$collection] = $store->count($collection);
        }
        $result = ['driver'=>config('school.store'), 'project'=>config('school.firebase_project'),
            'database'=>config('school.firebase_database'), 'cloudCounts'=>$counts,
            'sqliteCount'=>$sqlite->count(), 'sqliteFingerprint'=>hash('sha256', json_encode($sqlite, JSON_THROW_ON_ERROR))];
    } elseif ($action === 'admin') {
        $credentials = file_get_contents(storage_path('app/private/local-admin.txt'));
        preg_match('/^Email: (.+)$/m', $credentials, $email);
        preg_match('/^Password: (.+)$/m', $credentials, $password);
        $record = $store->get('admins', hash('sha256', mb_strtolower(trim($email[1]))));
        $result = ['exists'=>(bool) $record, 'active'=>(bool) ($record['active'] ?? false),
            'passwordMatches'=> $record && Illuminate\Support\Facades\Hash::check(trim($password[1]), $record['password']),
            'role'=>$record['role'] ?? null];
    } elseif ($action === 'content') {
        $record = $store->get('content', 'home');
        $result = ['exists'=>(bool) $record, 'description'=>$record['description'] ?? null,
            'fingerprint'=>hash('sha256', json_encode($canonical($record), JSON_THROW_ON_ERROR))];
    } elseif ($action === 'record') {
        $collection = $input['collection'] ?? '';
        if (!in_array($collection, ['applications', 'visits'], true)) throw new InvalidArgumentException;
        $record = $store->get($collection, $input['id']);
        $name = $collection === 'applications' ? ($record['child']['child_name'] ?? null) : ($record['name'] ?? null);
        $result = ['exists'=>(bool) $record, 'markerMatches'=>$name === ($input['marker'] ?? ''),
            'status'=>$record['status'] ?? null, 'documentCount'=>count($record['documents'] ?? []),
            'hasConsent'=> !empty($record['consent_at'] ?? $record['consent'] ?? null)];
    } elseif ($action === 'fixtures') {
        $marker = $input['marker'] ?? '';
        if (!preg_match('/^Uji Firebase [a-f0-9]{12}$/', $marker)) throw new InvalidArgumentException;
        $result = ['applications'=>[], 'visits'=>[]];
        foreach (array_keys($result) as $collection) {
            $cursor = null;
            $pages = 0;
            do {
                if (++$pages > 100) throw new RuntimeException('Fixture scan limit reached.');
                $page = $store->page($collection, $cursor, 100);
                foreach ($page['items'] as $record) {
                    $name = $collection === 'applications' ? ($record['child']['child_name'] ?? null) : ($record['name'] ?? null);
                    if ($name === $marker) $result[$collection][] = $record['id'];
                }
                $cursor = $page['next'];
            } while ($cursor);
        }
    } else {
        throw new InvalidArgumentException('Unsupported read-only action.');
    }
    echo json_encode(['ok'=>true, 'result'=>$result], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    // Exception bodies can contain remote request details; report only their class.
    echo json_encode(['ok'=>false, 'errorType'=>get_class($error)]);
    exit(1);
}
