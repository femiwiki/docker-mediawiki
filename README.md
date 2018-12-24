페미위키 미디어위키 서버 [![Docker Hub Status]][Docker Hub Link]
========
한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 미디어위키 서버입니다.
Dockerfile, 도커 컴포즈 파일 등 다양한 코드를 담고있습니다.

|| 내용
:---|----
기능 | 미디어위키 서버
Base AMI | [Femiwiki Base AMI](https://github.com/femiwiki/ami)

```sh
git clone https://github.com/femiwiki/mediawiki ~/mediawiki --depth=1
cp ~/mediawiki/configs/env.example ~/mediawiki/configs/env
cp ~/mediawiki/configs/secret.php.example  ~/mediawiki/configs/secret.php
vim configs/{env,secret.php}
# 각 설정 파일을 필요한 내용으로 고쳐주세요.

sudo docker swarm init
sudo docker stack deploy -c ~/mediawiki/docker-compose.yml mediawiki
```

### 개발
```bash
# 도커 이미지 업데이트
docker build -t femiwiki/mediawiki .
docker push femiwiki/mediawiki
```

로컬에서 테스트하는 방법

```bash
cp configs/secret.php.example configs/secret.php
cp configs/env.example configs/env
vim configs/{env,secret.php}
# secret.php와 env 를 적절히 수정해주세요

# DB를 별도의 방법으로 적절히 띄운 뒤 도커 컴포즈 실행
docker-compose up
```

&nbsp;

--------

The source code of *femiwiki/mediawiki* is primarily distributed under the terms
of the [GNU Affero General Public License v3.0] or any later version. See
[COPYRIGHT] for details.

[Docker Hub Status]: https://badgen.net/docker/pulls/femiwiki/mediawiki/?icon=docker&label=pulls
[Docker Hub Link]: https://hub.docker.com/r/femiwiki/mediawiki/
[femiwiki.com]: https://femiwiki.com
[GNU Affero General Public License v3.0]: LICENSE
[COPYRIGHT]: COPYRIGHT
