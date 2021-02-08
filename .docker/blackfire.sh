#!/bin/sh

mkdir -p /tmp/blackfire
architecture=$(case $(uname -m) in i386 | i686 | x86) echo "i386" ;; x86_64 | amd64) echo "amd64" ;; aarch64 | arm64 | armv8) echo "arm64" ;; *) echo "amd64" ;; esac)
curl -A "Docker" -L https://blackfire.io/api/v1/releases/client/linux/$architecture | tar zxp -C /tmp/blackfire
mv /tmp/blackfire/blackfire /usr/bin/blackfire
rm -Rf /tmp/blackfire

apt install -y wget gnupg2
wget -q -O - https://packages.blackfire.io/gpg.key | apt-key add -
echo "deb http://packages.blackfire.io/debian any main" > /etc/apt/sources.list.d/blackfire.list
apt update -y
apt install -y blackfire-php
