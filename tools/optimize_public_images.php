<?php

declare(strict_types=1);

if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
    fwrite(STDERR, "GD with WebP support is required.\n");
    exit(1);
}

$public = dirname(__DIR__).'/public/assets';
$images = [
    'design/asset-01.png' => 78,
    'design/asset-03.png' => 78,
    'design/asset-04.png' => 78,
    'design/sd-ceria-nusantara-campus.png' => 78,
    'team/team-portraits.png' => 82,
    'team/lina-susanti.png' => 82,
];
foreach (range(1, 6) as $number) {
    $images['design/teacher-'.$number.'.png'] = 82;
}

foreach ($images as $relative => $quality) {
    $source = $public.'/'.$relative;
    $target = substr($source, 0, -4).'.webp';
    $image = imagecreatefrompng($source);
    if (! $image || ! imagewebp($image, $target, $quality)) {
        throw new RuntimeException('Could not optimize '.$relative);
    }
    imagedestroy($image);
    printf("%s: %.1f KB -> %.1f KB\n", $relative, filesize($source) / 1024, filesize($target) / 1024);
}

$logo = imagecreatefrompng($public.'/design/asset-02.png');
foreach ([256 => 'webp', 64 => 'png'] as $size => $format) {
    $small = imagecreatetruecolor($size, $size);
    imagealphablending($small, false);
    imagesavealpha($small, true);
    imagecopyresampled($small, $logo, 0, 0, 0, 0, $size, $size, imagesx($logo), imagesy($logo));
    $target = $public.'/design/logo-'.$size.'.'.$format;
    $saved = $format === 'webp' ? imagewebp($small, $target, 82) : imagepng($small, $target, 9);
    imagedestroy($small);
    if (! $saved) throw new RuntimeException('Could not optimize logo.');
    printf("%s: %.1f KB\n", basename($target), filesize($target) / 1024);
}
imagedestroy($logo);
