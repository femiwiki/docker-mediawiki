페미위키 미디어위키 서버 [![Docker Hub Status]][Docker Hub Link] [![Travis CI Status]][Travis CI Link]
========
한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 미디어위키 서버입니다.
Dockerfile, 도커 컴포즈 파일 등 다양한 코드를 담고있습니다.

```bash
# 도커이미지 빌드
docker build -t femiwiki/mediawiki .

# 예제를 참고하여, secret.php 파일을 적절히 만들어주세요
cp configs/secret.php.example configs/secret.php
vim configs/secret.php

# (Optional) configs/LocalSettings.php 검사
composer install
composer test
# (Optional) configs/LocalSettings.php 자동 교정
composer fix

# MySQL와 memcached를 별도의 방법으로 띄운 뒤 도커 컴포즈를 실행해주면 됩니다.
# 자세한 내용은 https://github.com/femiwiki/database 참고
docker-compose up
```

&nbsp;

### Production
페미위키 프로덕션 배포는 [Docker Swarm]으로 이뤄집니다. 페미위키에서 사용하는
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
[Travis CI Status]: https://badgen.net/travis/femiwiki/mediawiki/master?label=build
[Travis CI Link]: https://travis-ci.org/femiwiki/mediawiki
[femiwiki.com]: https://femiwiki.com
[Docker Swarm]: https://docs.docker.com/engine/swarm/
[femiwiki/ami]: https://github.com/femiwiki/ami
[secret.php]: configs/secret.php.example
[GNU Affero General Public License v3.0]: LICENSE
[COPYRIGHT]: COPYRIGHT
