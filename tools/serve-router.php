<?php
// Router for PHP's local development server; document root remains public/.
$public = dirname(__DIR__).'/public';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$resolved = realpath($public.$path);
if ($path !== '/' && $resolved && str_starts_with($resolved, realpath($public).DIRECTORY_SEPARATOR) && is_file($resolved)) return false;
require $public.'/index.php';
