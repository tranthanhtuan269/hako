# SSH passwordless login — VPS `167.233.100.164`

## Quick setup (one password entry)

```powershell
cd c:\xampp\htdocs\hako
powershell -ExecutionPolicy Bypass -File deploy/setup-ssh-key.ps1
```

When prompted for password, try in order:

1. `Thanhtuan@3230`
2. `3230` (if username is `root`)

If you get **Permission denied**, the password on the server is wrong — reset it in your VPS control panel (DigitalOcean, Vultr, etc.) first.

## After setup

```powershell
ssh hako-vps
```

No password needed.

## Manual (if script fails)

```powershell
type $env:USERPROFILE\.ssh\id_ed25519.pub | ssh root@167.233.100.164 "mkdir -p ~/.ssh && chmod 700 ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
```

## SSH key location

- Private: `C:\Users\Tuantt\.ssh\id_ed25519`
- Public:  `C:\Users\Tuantt\.ssh\id_ed25519.pub`
