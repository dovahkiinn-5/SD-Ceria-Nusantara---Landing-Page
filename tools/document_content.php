<?php

// Export repository defaults only; never reads or writes the live database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$content = require resource_path('content/site.php');
$output = "# Konten awal per node database\n\n";
$output .= "Snapshot dari `resources/content/site.php`, bukan ekspor database aktif. Alamat dan map_url mengikuti konfigurasi lokal saat dokumen dibuat. Daftar/objek bersarang adalah field dalam dokumen, bukan subkoleksi.\n\n";
foreach ($content as $node => $data) {
    $output .= "## content/{$node}\n\n```json\n";
    $output .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $output .= "\n```\n\n";
}
file_put_contents(__DIR__.'/../docs/DATABASE-CONTENT.md', $output);
echo 'Dokumentasi '.count($content)." node konten dibuat.\n";
