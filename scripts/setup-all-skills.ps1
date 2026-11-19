$ProjectDir = Get-Location
$TargetDir = Join-Path $ProjectDir 'ai-skills\community'

if (-not (Test-Path $TargetDir)) {
    throw "Target directory $TargetDir does not exist."
}

$Tools = @('.claude\skills', '.gemini\skills', '.kilo\skills')
foreach ($tool in $Tools) {
    $toolDir = Join-Path $ProjectDir $tool
    New-Item -ItemType Directory -Force -Path $toolDir | Out-Null
    
    foreach ($item in Get-ChildItem $TargetDir -Directory) {
        $link = Join-Path $toolDir $item.Name
        if (Test-Path $link) {
            Remove-Item -Recurse -Force $link
        }
        New-Item -ItemType Junction -Path $link -Target $item.FullName | Out-Null
        Write-Host "Linked $tool\$($item.Name) -> ai-skills\community\$($item.Name)"
    }
}
Write-Host "All skills unified in ai-skills/community and linked for Claude, Gemini, and Kilo successfully."
