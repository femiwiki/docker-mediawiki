# 페미위키 미디어위키 서버 [![Container Registry]][container registry link] [![Github checks Status]][github checks link]

한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 미디어위키 서버입니다.
Dockerfile, 도커 컴포즈 파일 등 다양한 코드를 담고있습니다.
데이터베이스와 memcached, 각종 봇들이 실행됩니다.

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
sudo mkdir -p /srv/tweetbot
sudo docker swarm init
sudo docker stack deploy --prune -c ~/mediawiki/production.yml mediawiki
sudo docker stack deploy --prune -c ~/mediawiki/bots.yml bots
```

See also [How to deploy weekly femiwiki to production].

### About Docker image

페미위키를 위한 [PHP-FPM] 서버입니다.
동일한 이미지를 `FASTCGI_ADDR`과 `RESTBASE_ADDR` 환경 변수를 설정한 후 `caddy run`
커맨드로 사용할 경우에는 [Caddy] 웹 서버를 실행할 수 있습니다. 다음 예시 Compose file를 참고해 주세요.

```yml
http:
  image: ghcr.io/femiwiki/mediawiki
  command: caddy run
  ports:
    - 80:80
fastcgi:
  image: ghcr.io/femiwiki/mediawiki
  volumes:
    - ./configs:/a:ro
```

#### Configurations

개발 등의 목적으로 Caddyfile을 완전히 변경해야 할 경우에는 `/srv/femiwiki.com/Caddyfile`을 교체할 수 있습니다. 파일 마운트의 경우 일부 텍스트 편집기로 인한 편집이 무시될 수 있음을 주의하세요. (https://github.com/moby/moby/issues/15793)

```
./caddy/Caddyfile.dev:/srv/femiwiki.com/Caddyfile:ro
```

LocalSettings.php 파일이나 site-list.xml 파일을 교체해야 할 경우 다음과 같이 마운트해주세요.

```
./configs:/config/mediawiki:ro
```

위 두 경우 모두 development.yml에 예시가 있습니다.

&nbsp;

---

The source code of _femiwiki/mediawiki_ is primarily distributed under the terms
of the [GNU Affero General Public License v3.0] or any later version. See
[COPYRIGHT] for details.

[container registry]: https://badgen.net/badge/icon/docker?icon=docker&label
[container registry link]: https://github.com/orgs/femiwiki/packages/container/mediawiki
[github checks status]: https://badgen.net/github/checks/femiwiki/docker-mediawiki
[github checks link]: https://github.com/femiwiki/docker-mediawiki
[femiwiki.com]: https://femiwiki.com
[docker swarm]: https://docs.docker.com/engine/swarm/
[femiwiki/ami]: https://github.com/femiwiki/ami
[secret.php]: configs/secret.php.example
[how to deploy weekly femiwiki to production]: https://github.com/femiwiki/femiwiki/blob/main/how-to-deploy-weekly-femi-wiki-to-production.md
[php-fpm]: https://php-fpm.org/
[caddy]: https://caddyserver.com/
[gnu affero general public license v3.0]: LICENSE
[copyright]: COPYRIGHT
