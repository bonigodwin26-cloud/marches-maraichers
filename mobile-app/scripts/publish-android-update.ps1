param(
    [Parameter(Mandatory = $true)]
    [string]$ApkPath,

    [Parameter(Mandatory = $true)]
    [ValidateRange(2, 2147483647)]
    [int]$VersionCode,

    [Parameter(Mandatory = $true)]
    [ValidatePattern('^\d+\.\d+\.\d+$')]
    [string]$VersionName,

    [Parameter(Mandatory = $true)]
    [string]$ReleaseNotes
)

$ErrorActionPreference = 'Stop'

$resolvedApk = (Resolve-Path -LiteralPath $ApkPath).Path
if ([System.IO.Path]::GetExtension($resolvedApk) -ne '.apk') {
    throw 'Le fichier indiqué doit être une APK Android.'
}

$mobileRoot = Split-Path -Parent $PSScriptRoot
$applicationRoot = Split-Path -Parent $mobileRoot
$updatesDirectory = Join-Path $applicationRoot 'storage\app\private\mobile-updates'
$manifestPath = Join-Path $updatesDirectory 'android.json'
$apkPathOnServer = Join-Path $updatesDirectory 'latest.apk'
$temporaryApk = Join-Path $updatesDirectory 'latest.apk.tmp'
$temporaryManifest = Join-Path $updatesDirectory 'android.json.tmp'

if (Test-Path -LiteralPath $manifestPath) {
    $currentManifest = Get-Content -LiteralPath $manifestPath -Raw | ConvertFrom-Json
    if ($VersionCode -le [int]$currentManifest.version_code) {
        throw "Le numéro de version doit dépasser la version publiée ($($currentManifest.version_code))."
    }
}

New-Item -ItemType Directory -Path $updatesDirectory -Force | Out-Null
Copy-Item -LiteralPath $resolvedApk -Destination $temporaryApk -Force
$hash = (Get-FileHash -LiteralPath $temporaryApk -Algorithm SHA256).Hash.ToLowerInvariant()
$manifest = [ordered]@{
    version_code = $VersionCode
    version_name = $VersionName
    release_notes = $ReleaseNotes
    sha256 = $hash
    mandatory = $false
}
$json = $manifest | ConvertTo-Json -Depth 4
$encoding = [System.Text.UTF8Encoding]::new($false)
[System.IO.File]::WriteAllText($temporaryManifest, $json + [Environment]::NewLine, $encoding)
Move-Item -LiteralPath $temporaryApk -Destination $apkPathOnServer -Force
Move-Item -LiteralPath $temporaryManifest -Destination $manifestPath -Force

Write-Host "Mise à jour Android publiée : $VersionName (code $VersionCode)"
Write-Host "Fichier servi par l’application : $apkPathOnServer"
Write-Host "SHA-256 : $hash"