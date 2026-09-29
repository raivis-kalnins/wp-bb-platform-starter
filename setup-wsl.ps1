param(
    [string]$ProjectName = "wp-bb-platform"
)

$ErrorActionPreference = "Stop"
Write-Host "WP BB Platform - Windows 11 / WSL2 setup"
Write-Host "This copies the project into the WSL Linux filesystem for better Docker performance."

$winPath = (Get-Location).Path.Replace("'", "''")
$cmd = @"
set -e
SRC=`$(wslpath -a '$winPath')
DEST=`$HOME/projects/$ProjectName
mkdir -p `$HOME/projects
rm -rf "`$DEST"
cp -a "`$SRC" "`$DEST"
cd "`$DEST"
chmod +x bin/*
./bin/install
"@

wsl.exe bash -lc $cmd
