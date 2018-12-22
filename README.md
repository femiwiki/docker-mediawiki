서버 현황
========

A. database+bots 서버
--------

기능 | 데이터베이스 및 크론잡
:---|----
Base AMI | [Femiwiki Base AMI](https://github.com/femiwiki/ami)

```sh
git clone https://github.com/femiwiki/swarm.git ~/swarm
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
Base AMI | Debian stretch

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
sudo swapon -a

#
# 서비스 시작
#
git clone https://github.com/femiwiki/swarm.git ~/swarm
cp ~/swarm/configs/fastcgi.env.example ~/swarm/configs/fastcgi.env
cp ~/swarm/configs/parsoid.env.example ~/swarm/configs/parsoid.env
cp ~/swarm/configs/secret.php.example ~/swarm/configs/secret.php
# 각 파일을 필요한 내용으로 고쳐주세요.

sudo docker swarm init
sudo docker stack deploy -c ~/swarm/docker-compose.yml mediawiki
```
