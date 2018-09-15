한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 미디어위키 서버입니다.
Dockerfile, 도커 컴포즈 파일 등 다양한 코드를 담고있습니다.

[Docker Swarm]을 이용해, 아래와 같이 간편하게 페미위키를 로컬에서 실행할 수
있습니다.

```bash
cp configs/secret.php.example configs/secret.php
docker stack deploy --prune -c development.yml mediawiki
```

페미위키 개발하실때엔 아래 커맨드들을 참고해주세요.

```bash
# 도커이미지 빌드
docker build -t femiwiki/mediawiki .
# 수정된 도커이미지를 실행할때엔 아래와 같이
docker service update --force femiwiki_fastcgi

# configs/LocalSettings.php 검사
composer install
composer test
# configs/LocalSettings.php 자동 교정
composer fix
```

&nbsp;

### Production
페미위키는 프로덕션 배포에도 [Docker Swarm]을 사용합니다. 페미위키에서 사용하는
AWS EC2 AMI는 [femiwiki/ami]를 참고해주세요.

프로덕션 배포를 할때엔 [secret.php] 에서 개발자모드를 반드시 꺼주세요.

```sh
sudo docker swarm init
sudo docker stack deploy --prune -c ~/mediawiki/production.yml mediawiki
```

&nbsp;

--------

The source code of *femiwiki/mediawiki* is primarily distributed under the terms
of the [GNU Affero General Public License v3.0] or any later version. See
[COPYRIGHT] for details.

[Docker Hub Status]: https://badgen.net/docker/pulls/femiwiki/mediawiki/?icon=docker&label=pulls
[Docker Hub Link]: https://hub.docker.com/r/femiwiki/mediawiki/
[Travis CI Status]: https://api.travis-ci.com/femiwiki/mediawiki.svg?branch=master
[Travis CI Link]: https://travis-ci.com/femiwiki/mediawiki
[femiwiki.com]: https://femiwiki.com
[Docker Swarm]: https://docs.docker.com/engine/swarm/
[femiwiki/ami]: https://github.com/femiwiki/ami
[secret.php]: configs/secret.php.example
[GNU Affero General Public License v3.0]: LICENSE
[COPYRIGHT]: COPYRIGHT
=======
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
