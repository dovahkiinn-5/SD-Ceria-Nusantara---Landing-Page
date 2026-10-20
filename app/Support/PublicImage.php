<?php

namespace App\Support;

class PublicImage
{
    public static function url(?string $path): string
    {
        if (! is_string($path) || $path === '') return '';
        if ($path === '/assets/design/asset-02.png') return '/assets/design/logo-256.webp';
        if (! preg_match('#^/assets/(?:design|team)/[a-zA-Z0-9_-]+\.png$#', $path)) return $path;

        $webp = substr($path, 0, -4).'.webp';
        return is_file(public_path(ltrim($webp, '/'))) ? $webp : $path;
    }

    public static function favicon(?string $path): string
    {
        return $path === '/assets/design/asset-02.png' ? '/assets/design/logo-64.png' : (string) $path;
    }
}
