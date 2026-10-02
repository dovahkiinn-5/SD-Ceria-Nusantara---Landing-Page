<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('local') || config('school.store') !== 'sqlite') exit(1);
$content = app(App\Services\SchoolContent::class);
$defaults = $content->defaults();
$current = app(App\Contracts\DocumentStore::class)->get('content','home');
// Preserve edits; only add the original PDF's line breaks when the words match.
if ($current && preg_replace('/\s+/u',' ',$current['profile_text']) === preg_replace('/\s+/u',' ',$defaults['home']['profile_text'])) {
    unset($current['id']);
    $current['profile_text'] = $defaults['home']['profile_text'];
    $content->save('home',$current);
}
echo "PDF paragraph line breaks checked.\n";
