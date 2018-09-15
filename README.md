ap-northeast-1
========

parsoid 서버
--------
Debian stretch

```sh
#
# 도커 설치
# Reference: https://docs.docker.com/install/linux/docker-ce/debian/
#
sudo apt-get update
sudo apt-get install \
  apt-transport-https \
  ca-certificates \
  curl \
  gnupg2 \
  software-properties-common
curl -fsSL https://download.docker.com/linux/debian/gpg | sudo apt-key add -

sudo add-apt-repository \
  "deb [arch=amd64] https://download.docker.com/linux/debian \
  $(lsb_release -cs) \
  stable"

sudo apt-get update
sudo apt-get install docker-ce

#
# 파소이드 구동
#
sudo docker run --detach \
  --name parsoid \
  --restart always \
  femiwiki/parsoid

#
# caddy 구동
#
sudo mkdir -p /srv/caddy/config
cat <<'EOF' | sudo tee /srv/caddy/config/Caddyfile
(common) {
  gzip

  # Strict security headers
  header / {
    # Enable HSTS. https://mdn.io/HSTS
    Strict-Transport-Security "max-age=15768000"
    # Enable stricter XSS protection. https://mdn.io/X-XSS-Protection
    X-XSS-Protection "1; mode=block"
    # Prevent MIME-sniffing. https://mdn.io/X-Content-Type-Options
    X-Content-Type-Options "nosniff"
    # Prevent clickjacking. https://mdn.io/X-Frame-Options
    X-Frame-Options "DENY"
  }

  log stdout
}

parsoid.femiwiki.com, :80 {
  import common
  proxy / parsoid:8000 {
    transparent
  }
}
EOF

sudo docker run --detach \
  --name caddy \
  --restart always \
  --publish 80:80 \
  --publish 443:443 \
  --volume /srv/caddy/config:/var/www/html:ro \
  --volume /srv/caddy/data:/.caddy:rw \
  --link parsoid \
  joshix/caddy
```
