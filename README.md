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
sudo apt-get update && sudo apt-get install -y apt-transport-https ca-certificates curl gnupg2 software-properties-common
curl -fsSL https://download.docker.com/linux/debian/gpg | sudo apt-key add -
sudo add-apt-repository \
  "deb [arch=amd64] https://download.docker.com/linux/debian \
  $(lsb_release -cs) \
  stable"
sudo apt-get update && sudo apt-get install -y docker-ce

#
# 스왑 메모리 생성
#
sudo fallocate -l 3G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
sudo swapon

#
# 서비스 시작
#
git clone https://github.com/femiwiki/swarm.git ~/swarm
cp ~/swarm/secret.sample ~/swarm/secret
vim ~/swarm/secret
# 시크릿을 입력해주세요

sudo docker swarm init
sudo docker stack deploy -c ~/swarm/database.yml database
sudo docker stack deploy -c ~/swarm/parsoid.yml parsoid
sudo docker stack deploy -c ~/swarm/bots.yml bots
```

페미위키 실서버/테스트서버

--------

```sh
# 이미 있는 리눅스 인스턴스에 ENA를 활성화하는 과정이 필요함

# 커널 업그레이드
sudo apt-get update
sudo apt-get upgrade
sudo apt-get install linux-aws
sudo apt-get autoremove

# 인스턴스 '중지' 상태로 만듦
sudo poweroff
```

이후 로컬에서 아래 커맨드 실행

```sh
INSTANCE_ID='i-00ed7086ef9b999e6'

# 체크
aws ec2 describe-instances --instance-ids "${INSTANCE_ID}" --query 'Reservations[].Instances[].EnaSupport'
# 활성화
aws ec2 modify-instance-attribute --instance-id "${INSTANCE_ID}" --ena-support

# AMI 체크
AMI_ID='ami-029d4bdda4bd270dc'
aws ec2 describe-images --image-id "${AMI_ID}" --query 'Images[].EnaSupport'
```

이후 인스턴스 재시작

```sh
#
# Caddy 세팅
#
sudo apt-get install php7.0-fpm

# /usr/local/bin/caddy 에 바이너리 준비
# /etc/init/caddy.conf 에 서비스파일 준비
sudo setcap cap_net_bind_service=+ep /usr/local/bin/caddy
cat <<'EOF' | sudo tee /etc/caddy/Caddyfile
*:80 {
  root /var/www/femiwiki.com
  index index.php
  fastcgi / unix:/var/run/php/php7.2-fpm.sock php
  rewrite /w/api.php {
    to /api.php
  }
  rewrite /w {
    r  /(.*)
    to /index.php
  }
}
EOF

sudo service caddy start
```
