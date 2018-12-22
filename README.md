
서버 현황
========

A. database+bots 서버
--------

기능 | 데이터베이스 및 크론잡
:---|----
Base AMI | [Femiwiki Base AMI](https://github.com/femiwiki/ami)

```sh
git clone https://github.com/femiwiki/swarm.git ~/swarm --depth=1
cp ~/swarm/secret.sample ~/swarm/secret
vim ~/swarm/secret
# 시크릿을 입력해주세요

docker swarm init
docker stack deploy -c ~/swarm/database.yml database
docker stack deploy -c ~/swarm/bots.yml bots
```

B. mediawiki 서버
--------

기능 | 미디어위키 서버
:---|----
Base AMI | [Femiwiki Base AMI](https://github.com/femiwiki/ami)

```sh
#
# 서비스 시작
#
git clone https://github.com/femiwiki/mediawiki ~/mediawiki --depth=1
cp ~/mediawiki/configs/env.example ~/mediawiki/configs/env
cp ~/mediawiki/configs/secret.php.example  ~/mediawiki/configs/secret.php
# 각 설정 파일을 필요한 내용으로 고쳐주세요.

sudo docker swarm init
sudo docker stack deploy -c ~/mediawiki/docker-compose.yml mediawiki
```
