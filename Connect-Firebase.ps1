param(
    [string]$ProjectId = 'sd-ceria-nusantara',
    [string]$CredentialsPath = 'storage/app/private/firebase-service-account.json'
)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
if ($ProjectId -notmatch '^[a-z][a-z0-9-]{4,28}[a-z0-9]$') { throw 'Project ID tidak valid.' }
if (!(Test-Path -LiteralPath $CredentialsPath -PathType Leaf)) {
    throw 'Kunci service account belum tersedia. Lihat docs/FIREBASE-CONNECTION.md.'
}
$credentialFile = (Resolve-Path -LiteralPath $CredentialsPath).Path
$credentialInfo = Get-Content -LiteralPath $credentialFile -Raw | ConvertFrom-Json
if ($credentialInfo.type -ne 'service_account' -or $credentialInfo.project_id -ne $ProjectId -or !$credentialInfo.private_key) {
    throw 'Gunakan JSON service account dari proyek yang sama.'
}
$credentialInfo = $null
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
$phpPath = if ($phpCommand) { $phpCommand.Source } elseif (Test-Path -LiteralPath 'C:\xampp\php\php.exe') { 'C:\xampp\php\php.exe' } else { throw 'PHP 8.2+ diperlukan.' }
if (!(Test-Path -LiteralPath '.env')) { throw '.env belum tersedia. Jalankan instalasi pada README terlebih dahulu.' }
$previousEnv = @{}
$connection = @{
    SCHOOL_STORE = 'firestore'
    FIREBASE_PROJECT_ID = $ProjectId
    FIREBASE_DATABASE_ID = '(default)'
    FIREBASE_CREDENTIALS = $credentialFile.Replace('\','/')
}
foreach ($key in $connection.Keys) {
    $previousEnv[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
    [Environment]::SetEnvironmentVariable($key, $connection[$key], 'Process')
}
try {
    & $phpPath artisan config:clear
    if ($LASTEXITCODE -ne 0) { throw 'Gagal membersihkan konfigurasi.' }
    & $phpPath artisan school:firebase-check
    if ($LASTEXITCODE -ne 0) { throw 'Koneksi belum berhasil; driver di .env tetap seperti sebelumnya.' }
    & $phpPath artisan school:import-firestore
    if ($LASTEXITCODE -ne 0) { throw 'Import belum selesai. Perbaiki koneksi lalu ulangi; ID yang sudah tersalin akan dilewati.' }
    & $phpPath artisan school:seed-content
    if ($LASTEXITCODE -ne 0) { throw 'Konten awal belum selesai disiapkan.' }
    $envPath = Join-Path $PSScriptRoot '.env'
    $envText = [IO.File]::ReadAllText($envPath)
    foreach ($key in $connection.Keys) {
        $value = $connection[$key]
        if ($value.Contains('"') -or $value.Contains("`n") -or $value.Contains('$')) { throw 'Path konfigurasi mengandung karakter yang tidak didukung.' }
        $line = $key + '="' + $value + '"'
        $pattern = '(?m)^' + [regex]::Escape($key) + '=.*$'
        if ([regex]::IsMatch($envText, $pattern)) {
            $envText = [regex]::Replace($envText, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($match) $line })
        } else {
            $envText = $envText.TrimEnd() + "`r`n" + $line + "`r`n"
        }
    }
    [IO.File]::WriteAllText($envPath, $envText, [System.Text.UTF8Encoding]::new($false))
    & $phpPath artisan optimize:clear
    if ($LASTEXITCODE -ne 0) { throw 'Driver sudah diaktifkan, tetapi cache perlu dibersihkan dengan artisan optimize:clear.' }
    Write-Host "Laravel terhubung ke Firestore proyek $ProjectId. Data lokal tetap disimpan."
} finally {
    foreach ($key in $previousEnv.Keys) {
        [Environment]::SetEnvironmentVariable($key, $previousEnv[$key], 'Process')
    }
}
