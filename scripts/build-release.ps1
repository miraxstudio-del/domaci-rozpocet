[CmdletBinding()]
param(
    [ValidatePattern('^\d+\.\d+(?:\.\d+)?$')]
    [string]$Version = '1.01',

    [string]$PhpRuntimePath = ''
)

$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
if ([string]::IsNullOrWhiteSpace($PhpRuntimePath)) {
    $PhpRuntimePath = Join-Path $projectRoot 'app\php'
}
$runtimePath = (Resolve-Path $PhpRuntimePath).Path
$phpExe = Join-Path $runtimePath 'php.exe'

if (-not (Test-Path -LiteralPath $phpExe -PathType Leaf)) {
    throw "PHP runtime nebyl nalezen: $phpExe"
}

$runtimeVersion = (& $phpExe -r "echo PHP_VERSION;").Trim()
if ($LASTEXITCODE -ne 0 -or -not $runtimeVersion.StartsWith('8.2.')) {
    throw "Očekává se ověřený PHP runtime řady 8.2.x; nalezeno: $runtimeVersion"
}

$dirty = git -C $projectRoot status --porcelain
if ($dirty) {
    throw 'Pracovní strom není čistý. Před sestavením release nejdřív commitněte nebo ukliďte změny.'
}

$commit = (git -C $projectRoot rev-parse HEAD).Trim()
$distPath = Join-Path $projectRoot 'dist'
$packageName = "Domaci-rozpocet-v$Version-windows-x64"
$stagePath = Join-Path $distPath $packageName
$zipPath = Join-Path $distPath "$packageName.zip"
$checksumPath = Join-Path $distPath "$packageName.sha256"

New-Item -ItemType Directory -Path $distPath -Force | Out-Null
foreach ($path in @($stagePath, $zipPath, $checksumPath)) {
    if (Test-Path -LiteralPath $path) {
        Remove-Item -LiteralPath $path -Recurse -Force
    }
}

New-Item -ItemType Directory -Path $stagePath | Out-Null

# Zkopírujeme výhradně soubory sledované Gitem. Osobní data, exporty a nahrané
# doklady tak nemohou omylem skončit v distribuci.
$trackedFiles = git -C $projectRoot ls-files --cached
foreach ($relativePath in $trackedFiles) {
    if ($relativePath -eq 'app/php' -or $relativePath.StartsWith('app/php/')) {
        continue
    }

    $sourcePath = Join-Path $projectRoot ($relativePath -replace '/', '\')
    $destinationPath = Join-Path $stagePath ($relativePath -replace '/', '\')
    $destinationDirectory = Split-Path -Parent $destinationPath
    New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
    Copy-Item -LiteralPath $sourcePath -Destination $destinationPath -Force
}

$packageRuntimePath = Join-Path $stagePath 'app\php'
New-Item -ItemType Directory -Path $packageRuntimePath -Force | Out-Null
Get-ChildItem -LiteralPath $runtimePath -Force |
    Copy-Item -Destination $packageRuntimePath -Recurse -Force

$releaseInfo = @"
Domácí rozpočet $Version
========================

Portable Windows package for local use.
Source commit: $commit
Bundled PHP: $runtimeVersion

Integrity: compare the SHA-256 checksum of this ZIP with the accompanying
.sha256 file published in the same GitHub Release.
Privacy: this package was built from tracked source files only; it does not
include a household database, receipts, uploads, exports, backups, or settings.
"@
Set-Content -LiteralPath (Join-Path $stagePath 'RELEASE.txt') -Value $releaseInfo -Encoding utf8

Compress-Archive -LiteralPath $stagePath -DestinationPath $zipPath -CompressionLevel Optimal
$hash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLowerInvariant()
Set-Content -LiteralPath $checksumPath -Value "$hash *$packageName.zip" -Encoding ascii

Write-Host ''
Write-Host "Vytvořeno: $zipPath" -ForegroundColor Green
Write-Host "SHA-256: $hash" -ForegroundColor Green
Write-Host "PHP: $runtimeVersion" -ForegroundColor DarkGray
