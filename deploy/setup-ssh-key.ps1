# One-time setup: copy SSH public key to VPS for passwordless login.
# Usage: powershell -ExecutionPolicy Bypass -File deploy/setup-ssh-key.ps1

$ErrorActionPreference = "Stop"
$HostName = "167.233.100.164"
$SshUser = "root"
$HostAlias = "hako-vps"
$KeyPath = Join-Path $env:USERPROFILE ".ssh\id_ed25519"
$PubKeyPath = "$KeyPath.pub"
$SshDir = Join-Path $env:USERPROFILE ".ssh"
$ConfigPath = Join-Path $SshDir "config"

if (-not (Test-Path $PubKeyPath)) {
    Write-Host "Generating SSH key..."
    New-Item -ItemType Directory -Force -Path $SshDir | Out-Null
    ssh-keygen -t ed25519 -f $KeyPath -N '""' -C "tuantt-hako"
}

$pubKey = Get-Content $PubKeyPath -Raw
Write-Host ""
Write-Host "Public key:" -ForegroundColor Cyan
Write-Host $pubKey.Trim()
Write-Host ""

Write-Host "Connecting to ${SshUser}@${HostName} (enter password when prompted)..." -ForegroundColor Yellow
Write-Host "Password is usually: Thanhtuan@3230" -ForegroundColor DarkGray
Write-Host ""

$remoteCmd = @"
mkdir -p ~/.ssh && chmod 700 ~/.ssh && touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys && grep -qxF '$($pubKey.Trim())' ~/.ssh/authorized_keys || echo '$($pubKey.Trim())' >> ~/.ssh/authorized_keys && echo KEY_INSTALLED_OK
"@

ssh -o StrictHostKeyChecking=accept-new "${SshUser}@${HostName}" $remoteCmd
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "Failed. Check username/password on the server." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Testing passwordless login..." -ForegroundColor Cyan
ssh -o BatchMode=yes -o IdentitiesOnly=yes -i $KeyPath "${SshUser}@${HostName}" "whoami && hostname"
if ($LASTEXITCODE -ne 0) {
    Write-Host "Key copied but login test failed." -ForegroundColor Red
    exit 1
}

$hostBlock = @"

Host $HostAlias
    HostName $HostName
    User $SshUser
    IdentityFile $KeyPath
    IdentitiesOnly yes
    ServerAliveInterval 60

"@

$config = if (Test-Path $ConfigPath) { Get-Content $ConfigPath -Raw } else { "" }
if ($config -notmatch "Host\s+$HostAlias\s") {
    Add-Content -Path $ConfigPath -Value $hostBlock
    Write-Host "Added SSH config alias: $HostAlias" -ForegroundColor Green
} else {
    Write-Host "SSH config alias '$HostAlias' already exists." -ForegroundColor DarkGray
}

Write-Host ""
Write-Host "Done. Connect with:" -ForegroundColor Green
Write-Host "  ssh $HostAlias"
