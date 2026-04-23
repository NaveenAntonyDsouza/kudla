# ============================================================================
# Build deploy ZIP for MatrimonyTheme (Windows PowerShell)
# Uses .NET ZipArchive directly (forward slashes - Linux-compatible)
# ============================================================================
# Usage (from project root):
#   powershell -ExecutionPolicy Bypass -File .\deploy-build.ps1
# ============================================================================

$ErrorActionPreference = 'Stop'

$projectRoot = $PSScriptRoot
$stamp = Get-Date -Format "yyyyMMdd-HHmm"
$staging = Join-Path $env:TEMP "matrimony-deploy-$stamp"
$zipPath = Join-Path $projectRoot "deploy-$stamp.zip"

Write-Host "=== Building deploy ZIP (Linux-compatible) ===" -ForegroundColor Cyan
Write-Host "Project root: $projectRoot"
Write-Host "Staging dir:  $staging"
Write-Host "Output ZIP:   $zipPath"
Write-Host ""

# Ensure clean staging directory
if (Test-Path $staging) {
    Remove-Item -Recurse -Force $staging
}
New-Item -ItemType Directory -Path $staging | Out-Null

# Items to INCLUDE (relative paths from project root)
$itemsToInclude = @(
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'resources',
    'routes',
    'composer.json',
    'composer.lock',
    'package.json',
    'package-lock.json',
    'artisan',
    '.htaccess'
)

# Folders to EXCLUDE inside public/ and storage/
$excludeDirs = @(
    'public\storage',
    'storage\app\public\photos',
    'storage\app\public\branding',
    'storage\app\public\id-proofs',
    'storage\app\public\jathakam',
    'storage\app\public\demo-avatars',
    'storage\logs',
    'storage\framework\cache',
    'storage\framework\sessions',
    'storage\framework\views'
)

$excludeFiles = @(
    '.env',
    '.env.example',
    '.env.production',
    'deploy-build.ps1'
)

Write-Host "Step 1/3: Copying included items to staging..." -ForegroundColor Yellow

foreach ($item in $itemsToInclude) {
    $src = Join-Path $projectRoot $item
    $dst = Join-Path $staging $item

    if (-not (Test-Path $src)) {
        Write-Host "  SKIP (not found): $item" -ForegroundColor DarkGray
        continue
    }

    if ((Get-Item $src).PSIsContainer) {
        $excludeArgs = @()
        foreach ($exDir in $excludeDirs) {
            $exFull = Join-Path $projectRoot $exDir
            if (Test-Path $exFull) {
                $excludeArgs += '/XD'
                $excludeArgs += $exFull
            }
        }
        foreach ($exFile in $excludeFiles) {
            $excludeArgs += '/XF'
            $excludeArgs += $exFile
        }

        Write-Host "  COPY DIR:  $item"
        $robocopyOutput = & robocopy $src $dst /E /NFL /NDL /NJH /NJS /NC /NS /NP @excludeArgs
        if ($LASTEXITCODE -ge 8) {
            Write-Host "  ERROR copying $item (exit code $LASTEXITCODE)" -ForegroundColor Red
            exit 1
        }
    } else {
        if ($excludeFiles -notcontains $item) {
            Write-Host "  COPY FILE: $item"
            Copy-Item -Path $src -Destination $dst -Force
        }
    }
}

# Recreate empty storage/framework/{cache,sessions,views} structure with .gitkeep
Write-Host ""
Write-Host "Step 2/3: Creating empty storage/framework/ + storage/logs/ structure..." -ForegroundColor Yellow
$emptyDirs = @(
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs',
    'storage\app\public'
)
foreach ($d in $emptyDirs) {
    $full = Join-Path $staging $d
    New-Item -ItemType Directory -Path $full -Force | Out-Null
    New-Item -ItemType File -Path (Join-Path $full '.gitkeep') -Force | Out-Null
}

# Sanity check
Write-Host ""
Write-Host "Step 3/3: Sanity check + creating Linux-compatible ZIP..." -ForegroundColor Yellow
$dangerCheck = @(
    'storage\app\public\photos',
    'storage\app\public\branding',
    'storage\app\public\id-proofs',
    'storage\app\public\jathakam',
    '.env',
    'vendor',
    'node_modules'
)
$failed = $false
foreach ($d in $dangerCheck) {
    if (Test-Path (Join-Path $staging $d)) {
        Write-Host "  FAILED: $d should NOT be in staging" -ForegroundColor Red
        $failed = $true
    }
}
if ($failed) {
    Write-Host "Aborting - fix exclusions and retry." -ForegroundColor Red
    exit 1
}
Write-Host "  All exclusions verified." -ForegroundColor Green

# Create ZIP using .NET ZipArchive directly with forward-slash entry names
# This is THE critical fix - Compress-Archive on PowerShell 5.1 uses backslashes
# which break extraction on Linux (Hostinger).
if (Test-Path $zipPath) {
    Remove-Item -Force $zipPath
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

Write-Host "  Compressing to $zipPath ..."

$zipStream = [System.IO.File]::Create($zipPath)
$zip = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    $stagingNorm = $staging.TrimEnd('\') + '\'
    $stagingLen = $stagingNorm.Length

    # Walk all files in staging
    $allFiles = Get-ChildItem -Path $staging -Recurse -File -Force
    $fileCount = 0

    foreach ($file in $allFiles) {
        # Compute relative path with FORWARD slashes
        $relPath = $file.FullName.Substring($stagingLen)
        $entryName = $relPath -replace '\\', '/'

        # Create entry and copy file content
        $entry = $zip.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        try {
            $fileStream = [System.IO.File]::OpenRead($file.FullName)
            try {
                $fileStream.CopyTo($entryStream)
            } finally {
                $fileStream.Dispose()
            }
        } finally {
            $entryStream.Dispose()
        }

        $fileCount++
        if ($fileCount % 200 -eq 0) {
            Write-Host "    ... $fileCount files added"
        }
    }

    Write-Host "  Total files in ZIP: $fileCount"
} finally {
    $zip.Dispose()
    $zipStream.Dispose()
}

# Cleanup staging
Remove-Item -Recurse -Force $staging

# Report
$zipInfo = Get-Item $zipPath
$sizeMB = [Math]::Round($zipInfo.Length / 1MB, 2)
Write-Host ""
Write-Host "=== DONE ===" -ForegroundColor Green
Write-Host "ZIP created: $zipPath"
Write-Host "ZIP size:    $sizeMB MB"
Write-Host ""
Write-Host "Next: upload this ZIP to public_html/ and extract DIRECTLY THERE"
Write-Host "      (NOT into a subfolder like '333')"
