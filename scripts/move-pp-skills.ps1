$commit = '0c02cc763ca75086063d377526e960bbe89c3cf0'
$skills = @('PP_api-design', 'PP_extension-lifecycle', 'PP_frontend-structure', 'PP_sprint_delivery', 'PP_testing', 'PP_translation')
foreach ($s in $skills) {
    $targetPath = "ai-skills\community\$s"
    if (-not (Test-Path $targetPath)) { New-Item -ItemType Directory -Force -Path $targetPath | Out-Null }
    git checkout $commit -- ".claude/skills/$s"
    if (Test-Path ".claude/skills/$s") {
        Copy-Item -Path ".claude/skills/$s\*" -Destination $targetPath -Recurse -Force
        Remove-Item -Recurse -Force ".claude/skills/$s"
    }
}
Write-Host "PP_* skills moved to ai-skills/community successfully."
