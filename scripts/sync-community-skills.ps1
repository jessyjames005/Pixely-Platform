# Pixely Platform - Community AI skills sync (PowerShell version)
#
# Equivalent of scripts/sync-community-skills.sh for Windows/PowerShell.
# Needs only git + network access. Run from the repo root:
#   powershell -ExecutionPolicy Bypass -File scripts\sync-community-skills.ps1
#
# Optional: also (re)create the symlinks in .claude/skills, .gemini/skills and
# .kilo/skills (needs Developer Mode enabled, or an elevated PowerShell):
#   powershell -ExecutionPolicy Bypass -File scripts\sync-community-skills.ps1 -RecreateLinks

param(
    [switch]$RecreateLinks
)

$ErrorActionPreference = 'Stop'

$RepoUrl = if ($env:PIXELY_SKILLS_SOURCE_REPO) { $env:PIXELY_SKILLS_SOURCE_REPO } else { 'https://github.com/mrgoonie/claudekit-skills' }
$RepoRef = if ($env:PIXELY_SKILLS_SOURCE_REF)  { $env:PIXELY_SKILLS_SOURCE_REF }  else { 'main' }

$ProjectDir = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$TargetDir  = Join-Path $ProjectDir 'ai-skills\community'
$TmpDir     = Join-Path ([System.IO.Path]::GetTempPath()) ("pixely-skills-" + [guid]::NewGuid().ToString('N'))

# name in Pixely -> path in the upstream repository
$Mappings = [ordered]@{
    'ui-styling'    = '.claude/skills/ui-styling'
    'debugging'     = '.claude/skills/debugging'
    'code-review'   = '.claude/skills/code-review'
    'devops'        = '.claude/skills/devops'
    'web-testing'   = '.claude/skills/web-testing'
    'ai-multimodal' = '.claude/skills/ai-multimodal'
    'docs-seeker'   = '.claude/skills/docs-seeker'
    'mermaidjs-v11' = '.claude/skills/mermaidjs-v11'
    'skill-creator' = '.claude/skills/skill-creator'
}

# All skills that get a symlink (includes the 5 design-pack skills that are not synced from upstream)
$AllSkills = @('banner-design','brand','design','design-system','slides') + @($Mappings.Keys)

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    throw 'git is not installed or not in PATH.'
}

try {
    Write-Host "Cloning $RepoUrl@$RepoRef (shallow)..."
    # git writes progress to stderr; don't let PowerShell treat that as an error.
    $ErrorActionPreference = 'Continue'
    & git clone --depth 1 --branch $RepoRef --single-branch --quiet $RepoUrl $TmpDir 2>&1 | Out-Null
    $gitExit = $LASTEXITCODE
    $ErrorActionPreference = 'Stop'
    if ($gitExit -ne 0) { throw "git clone failed (exit code $gitExit). Check network access." }

    New-Item -ItemType Directory -Force -Path $TargetDir | Out-Null

    foreach ($name in $Mappings.Keys) {
        $src  = Join-Path $TmpDir ($Mappings[$name] -replace '/', '\')
        $dest = Join-Path $TargetDir $name

        if (-not (Test-Path $src)) {
            Write-Host "  ! skip $name : $($Mappings[$name]) not found upstream (repo layout may have changed)"
            continue
        }

        if (Test-Path $dest) { Remove-Item -Recurse -Force $dest }
        New-Item -ItemType Directory -Force -Path $dest | Out-Null
        Copy-Item -Path (Join-Path $src '*') -Destination $dest -Recurse -Force
        Write-Host "  synced $name <- $($Mappings[$name])"
    }
}
finally {
    if (Test-Path $TmpDir) {
        # .git objects are read-only on Windows; clear the attribute before deleting.
        Get-ChildItem -Recurse -Force $TmpDir | ForEach-Object { $_.Attributes = 'Normal' } 2>$null
        Remove-Item -Recurse -Force $TmpDir -ErrorAction SilentlyContinue
    }
}

if ($RecreateLinks) {
    Write-Host ''
    Write-Host 'Recreating symlinks...'
    foreach ($tool in @('.claude\skills', '.gemini\skills', '.kilo\skills')) {
        $toolDir = Join-Path $ProjectDir $tool
        New-Item -ItemType Directory -Force -Path $toolDir | Out-Null
        foreach ($name in $AllSkills) {
            $link   = Join-Path $toolDir $name
            $target = Join-Path $TargetDir $name
            $item   = Get-Item $link -Force -ErrorAction SilentlyContinue
            if ($item -and $item.LinkType -eq 'SymbolicLink') { continue }
            if ($item) { Remove-Item -Recurse -Force $link }
            try {
                New-Item -ItemType SymbolicLink -Path $link -Target $target | Out-Null
                Write-Host "  linked $tool\$name"
            } catch {
                Write-Host "  ! could not create symlink $tool\$name : enable Developer Mode or run PowerShell as Administrator."
                break
            }
        }
    }
}

Write-Host ''
Write-Host "Done. $TargetDir now holds the real SKILL.md files."
