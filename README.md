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

테스트서버
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
