param([int]$Port = 8000)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
$phpPath = if ($phpCommand) { $phpCommand.Source } elseif (Test-Path -LiteralPath 'C:\xampp\php\php.exe') { 'C:\xampp\php\php.exe' } else { throw 'PHP 8.2+ diperlukan. Pasang PHP lalu tambahkan ke PATH.' }
if (!(Test-Path -LiteralPath 'vendor/autoload.php')) { throw 'Jalankan composer install terlebih dahulu. Lihat README.md.' }
& $phpPath -d upload_max_filesize=5M -d post_max_size=20M -d max_file_uploads=20 -S "127.0.0.1:$Port" -t public tools/serve-router.php
