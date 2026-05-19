# Converts docs/manual-fa-print.html to manual-fa-print.docx via Microsoft Word.
# Expands CSS variables and resolves asset paths for Word HTML import.
param(
    [string]$HtmlPath = (Join-Path $PSScriptRoot "..\manual-fa-print.html"),
    [string]$OutPath   = (Join-Path $PSScriptRoot "..\manual-fa-print.docx")
)

$ErrorActionPreference = "Stop"
$HtmlPath = (Resolve-Path $HtmlPath).Path
$OutPath  = [System.IO.Path]::GetFullPath($OutPath)
$docsDir  = Split-Path $HtmlPath -Parent
$rootDir  = (Resolve-Path (Join-Path $docsDir "..")).Path

$html = [System.IO.File]::ReadAllText($HtmlPath, [System.Text.UTF8Encoding]::new($false))

$vars = @{
    "--primary"      = "#c90f17"
    "--primary-dark" = "#9e0c12"
    "--primary-soft" = "rgba(214, 17, 25, 0.08)"
    "--slate"        = "#0f172a"
    "--slate-2"      = "#1e293b"
    "--muted"        = "#64748b"
    "--surface"      = "#ffffff"
    "--surface-2"    = "#f8fafc"
    "--border"       = "#e2e8f0"
    "--shadow"       = "0 4px 24px rgba(15, 23, 42, 0.06)"
    "--shadow-lg"    = "0 25px 50px -12px rgba(15, 23, 42, 0.12)"
    "--radius"       = "14px"
    "--radius-sm"    = "10px"
}
foreach ($k in $vars.Keys) {
    $html = $html -replace "var\($([regex]::Escape($k))\)", $vars[$k]
}

function To-FileUri([string]$path) {
    $p = [System.IO.Path]::GetFullPath($path)
    return "file:///" + ($p -replace "\\", "/")
}

$html = [regex]::Replace($html, 'url\("\.\./public/([^"]+)"\)', {
    param($m)
    $abs = Join-Path $rootDir ("public\" + $m.Groups[1].Value)
    'url("' + (To-FileUri $abs) + '")'
})

# Embed figures as data URIs — Word HTML import often skips linked images.
$html = [regex]::Replace($html, 'src="figures/([^"]+)"', {
    param($m)
    $imgPath = Join-Path $docsDir ("figures\" + $m.Groups[1].Value)
    if (-not (Test-Path $imgPath)) { return $m.Value }
    $bytes = [System.IO.File]::ReadAllBytes($imgPath)
    $b64 = [Convert]::ToBase64String($bytes)
  'src="data:image/png;base64,' + $b64 + '"'
})

$tempHtml = Join-Path $docsDir ".manual-fa-print-word-temp.html"
[System.IO.File]::WriteAllText($tempHtml, $html, [System.Text.UTF8Encoding]::new($false))

$word = $null
$doc  = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Open($tempHtml, $false, $true)
    $doc.SaveAs2([ref]$OutPath, [ref]16) # wdFormatXMLDocument
    Write-Host "Saved: $OutPath"
}
finally {
    if ($doc)  { $doc.Close($false) | Out-Null }
    if ($word) {
        $word.Quit() | Out-Null
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    if (Test-Path $tempHtml) { Remove-Item $tempHtml -Force }
}
[GC]::Collect()
[GC]::WaitForPendingFinalizers()
