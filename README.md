# 페미위키 미디어위키 서버 [![Container Registry]][container registry link] [![Github checks Status]][github checks link]

> [!IMPORTANT]  
> This docker image is specifically designed for the needs of FemiWiki, contains FemiWiki-specific configurations and includes arbitrary MediaWiki extensions. It is not recommended to use this image for general purposes. We recommend using the [Docker official image for MediaWiki](https://hub.docker.com/_/mediawiki/).

한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 미디어위키 도커 이미지입니다. Dockerfile, 테스트용 도커 컴포즈 파일 등 다양한 코드를 담고있습니다.

## Usage of Docker Image

페미위키를 위한 [PHP-FPM] 서버이며 동일한 이미지로 [Caddy] 웹 서버를 실행할 수도 있습니다. 다음 예시 Compose file를 참고해 주세요. compose.yaml에서 실행 가능한 개발용 전체 예시를 볼 수 있습니다.

```yml
fastcgi:
  image: ghcr.io/femiwiki/mediawiki
http:
  image: ghcr.io/femiwiki/mediawiki
  command: caddy run
  ports:
    - 80:80
  volumes:
    - ./path/to/Caddyfile:/srv/femiwiki.com/Caddyfile:ro
```

#### MediaWiki

다음 환경 변수를 설정할 수 있습니다.

- `MEDIAWIKI_SERVER`: `$wgServer`를 덮어씁니다. 기본값 `'https://femiwiki.com'`.
- `MEDIAWIKI_DEBUG_MODE`: 설정되었을 경우 미디어위키에서 제공하는 디버그 기능들을 켜고 개발 환경에서 세팅이 어려운 확장기능들을 비활성화합니다.
- `MEDIAWIKI_SKIP_INSTALL`: 설정되었을 경우 컨테이너 시작 시 install.php를 실행하지 않습니다. install.php는 데이터베이스가 세팅되지 않은지 검사하고 초기화하는 일과 LocalSettings.php 생성 작업을 하므로 둘 모두 이미 처리된 경우에 스킵할 수 있습니다.
- `MEDIAWIKI_SKIP_UPDATE`: 설정되었을 경우 컨테이너 시작 시 update.php를 실행하지 않습니다. update.php 실행은 미디어위키 설치 직후 혹은 확장 기능 추가 시에만 필요합니다.
- `MEDIAWIKI_SKIP_IMPORT_SITES`: 설정되었을 경우 컨테이너 시작 시 importSites.php를 실행하지 않습니다. 내용이 변경되지 않았다면 한 데이터베이스에 두 번 이상 실행할 필요가 없습니다.

`/etc/mediawiki`에 위치한 LocalSettings.php 파일이나 site-list.xml 파일은 테스트 목적에 따라 교체할 수 있습니다.

#### Caddy

Caddy를 실행할 경우에는 Caddyfile을 `/srv/femiwiki.com/Caddyfile`로 마운트하고 `caddy run`을 커맨드로 사용하여야 합니다. [Usage of Docker Image](#usage-of-docker-image)를 참고하세요. 파일 마운트의 경우 일부 텍스트 편집기로 인한 편집이 무시될 수 있음을 주의하세요. (https://github.com/moby/moby/issues/15793)

```
./caddy/Caddyfile.dev:/srv/femiwiki.com/Caddyfile:ro
```

## Usage of Docker Compose file

[Docker Compose]로 femiwiki.com과 같은 이미지와 LocalSettings.php에 스킨, 가젯, 실제 문서 몇 개를 얹은 위키를 로컬에서 띄웁니다. 로그인한 화면, 비주얼 편집기, 가젯처럼 정적 스킨 미리보기로는 볼 수 없는 것을 확인할 때 씁니다.

```bash
development/wiki up      # 첫 실행은 설치와 문서 적재로 몇 분 걸립니다
development/wiki down    # 멈춥니다. 다시 up 하면 문서가 남아 있습니다
development/wiki reset   # 멈추고 데이터베이스를 지웁니다
```

브라우저에서 [http://127.0.0.1:8080]을 방문할 수 있습니다.

- `compose.yaml`은 이미지 테스트(CI)도 쓰는 공통 부분이고, `docker compose`가 그 위에 읽는 `compose.override.yaml`이 스킨 마운트와 [development/LocalSettings.local.php](development/LocalSettings.local.php)를 더합니다.
- FemiwikiSkin과 OOUIFemiwikiTheme의 ref, 먼저 적용할 [remote-gadgets] ref와 그 위에 겹칠 풀 리퀘스트, 가져올 문서, 테스트 계정은 [development/wiki.env.example](development/wiki.env.example)에 있고, 첫 실행 때 `development/wiki.env`로 복사됩니다. ref를 바꾼 뒤에는 스킨이면 `development/wiki up`, 가젯이면 `development/wiki gadgets`를 실행합니다.
- `MediaWiki:Sidebar`와 문서는 femiwiki.com의 `api.php`와 `action=raw`로 하나씩 가져옵니다. 파일은 올리지 않고 femiwiki.com의 것을 보여줍니다.
- 운영 비밀값이나 서비스가 필요한 이메일, hCaptcha, S3 업로드, 디스코드 최근 바뀜 피드, 구글 애널리틱스 조회수, OAuth 서명 키, cron 작업, CDN과 운영 Caddyfile의 요청 제한은 빠져 있습니다.
- rootless Podman이라면 `podman system service`를 켜고 `DOCKER_HOST=unix://$XDG_RUNTIME_DIR/podman/podman.sock`을 지정합니다.

이미지 테스트처럼 `compose.override.yaml` 없이 띄우려면 `docker compose -f compose.yaml up`을 실행합니다.

## Build

페미위키 개발하실 때엔 아래 커맨드들을 참고해주세요.

```bash
# 도커이미지 빌드
docker build -t femiwiki/mediawiki:dev .
# 수정된 도커이미지를 실행할때엔 아래와 같이
docker service update --force femiwiki_fastcgi

# configs/LocalSettings.php 검사
composer install
composer test
# configs/LocalSettings.php 자동 교정
composer fix
```

```mermaid
flowchart TD
    composer --> femiwiki/php-fpm
    php-fpm --> femiwiki/php-fpm --> femiwiki/mediawiki --> femiwiki/femiwiki
    caddy --> femiwiki/caddy --> femiwiki/femiwiki
    composer --> femiwiki/femiwiki-extensions --> femiwiki/femiwiki
```

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
[docker compose]: https://docs.docker.com/compose/
[remote-gadgets]: https://github.com/femiwiki/remote-gadgets
[femiwiki/ami]: https://github.com/femiwiki/ami
[how to deploy weekly femiwiki to production]: https://github.com/femiwiki/femiwiki/blob/main/how-to-deploy-weekly-femi-wiki-to-production.md
[php-fpm]: https://php-fpm.org/
[caddy]: https://caddyserver.com/
[http://127.0.0.1:8080]: http://127.0.0.1:8080
[gnu affero general public license v3.0]: LICENSE
[copyright]: COPYRIGHT
