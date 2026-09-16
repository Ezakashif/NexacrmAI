# Seller-only ZIP helper for Windows when Info-ZIP `zip` and PHP ZipArchive are unavailable.
# Includes hidden / dotfiles (.env.example, .gitignore) that Compress-Archive would skip.
param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('create', 'list')]
    [string] $Action,

    [Parameter(Mandatory = $true)]
    [string] $Path,

    [string] $Destination = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

if ($Action -eq 'create') {
    if (-not $Destination) {
        throw 'Destination zip path is required for create'
    }
    if (-not (Test-Path -LiteralPath $Path -PathType Container)) {
        throw "Source directory not found: $Path"
    }

    $destinationDir = Split-Path -Parent $Destination
    if ($destinationDir -and -not (Test-Path -LiteralPath $destinationDir)) {
        New-Item -ItemType Directory -Path $destinationDir | Out-Null
    }
    if (Test-Path -LiteralPath $Destination) {
        Remove-Item -LiteralPath $Destination -Force
    }

    $rootName = Split-Path -Leaf $Path
    $sourceFull = (Resolve-Path -LiteralPath $Path).Path
    $prefixLength = $sourceFull.Length
    $zip = [System.IO.Compression.ZipFile]::Open($Destination, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        Get-ChildItem -LiteralPath $sourceFull -Recurse -Force | ForEach-Object {
            $relative = $_.FullName.Substring($prefixLength).TrimStart('\', '/')
            $entry = ($rootName + '/' + ($relative -replace '\\', '/'))
            if ($_.PSIsContainer) {
                if (-not $entry.EndsWith('/')) {
                    $entry += '/'
                }
                [void]$zip.CreateEntry($entry)
            } else {
                [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                    $zip,
                    $_.FullName,
                    $entry,
                    [System.IO.Compression.CompressionLevel]::Optimal
                )
            }
        }
    } finally {
        $zip.Dispose()
    }
    exit 0
}

if ($Action -eq 'list') {
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "Zip not found: $Path"
    }
    $zip = [System.IO.Compression.ZipFile]::OpenRead($Path)
    try {
        foreach ($entry in $zip.Entries) {
            Write-Output $entry.FullName
        }
    } finally {
        $zip.Dispose()
    }
    exit 0
}
