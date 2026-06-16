#!/usr/bin/env bash
set -euo pipefail

mkdir -p /root/.ssh
chmod 700 /root/.ssh
touch /root/.ssh/authorized_keys
chmod 600 /root/.ssh/authorized_keys

add_key() {
    local key="$1"
    if ! grep -qF "$key" /root/.ssh/authorized_keys 2>/dev/null; then
        echo "$key" >> /root/.ssh/authorized_keys
        echo "Added: ${key:0:50}..."
    else
        echo "Exists: ${key:0:50}..."
    fi
}

add_key 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIBSRM1r1YHkyYYYneMhBOVvANtFT06bC/KhvABS0Ce40 tuantt@gmail.com'
add_key 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDJ8r7sZFRaomT5l83caZVwO+m/Oi7mDd69WL+x7ePA1UeiMZEF72VwxE523FcC15LNfmdsuAYxSGDefDLpFHem2b4zUwS6db7f1Q0Hz+nU6SAYPEGQJjcy8PIzj2FlJI3Dk0OBdcf7sWTp3L+Vi24prjdbjnPBYZce3abfAFB0CjU/4+LJGNTvJpOjncBg3JKZUoCxhWE1wMrsPK+6Z533WbsThwfPJAuoczoJYxsAE7Yd2BP2ewjAUtOukaLC9CVXUzGK9rvKwoXtJ5akFmnrkm9PGBwmBxsBdLXU+n1n1Rt2Rgs/lo0zp9dpy9fI5rpfIfJnlytHfOrCuje3MHJp'
