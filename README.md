Lightsail
=======
EC2
========

database+bots 서버
--------

데이터베이스 및 봇 실행.

- amazon linux 2

```sh
sudo yum update -y
sudo yum install -y htop tmux

#
# 도커 설치
# Reference: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/docker-basics.html#install_docker
#
sudo amazon-linux-extras install -y docker
sudo systemctl enable docker
sudo systemctl start docker
sudo usermod -a -G docker ec2-user
# 이후 로그아웃한 뒤 재로그인

#
# 스왑 메모리 생성
# 아마존 리눅스에서는 기본으로 XFS를 쓰는데, 이 경우 fallocate 명령어를 쓰지 못한다.
#
sudo dd if=/dev/zero of=/swapfile bs=256M count=12
sudo chmod 600 /swapfile
sudo mkswap /swapfile
echo '/swapfile swap swap defaults 0 0' | sudo tee -a /etc/fstab
sudo swapon -a

#
# 서비스 시작
#
sudo yum install -y git
git clone https://github.com/femiwiki/swarm.git ~/swarm
cp ~/swarm/secret.sample ~/swarm/secret
vim ~/swarm/secret
# 시크릿을 입력해주세요

docker swarm init --advertise-addr eth0
docker stack deploy -c ~/swarm/database.yml database
docker stack deploy -c ~/swarm/bots.yml bots
```

mediawiki 서버
--------

페미위키 실서버. 데이터베이스를 구성한 다음이어야 정상적으로 실행됨.

- Debian stretch
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
