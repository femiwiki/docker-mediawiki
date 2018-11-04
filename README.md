# [페미위키][femiwiki.com] 소스코드

한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 도커 소스 코드입니다.

## 개발 및 배포에 필요한 소프트웨어

* [Docker](https://www.docker.com/)

## 개발하기

개발 및 테스트 용도로 로컬 컴퓨터에서 페미위키를 실행하려면 다음 절차를 따라주세요.

1. ``www/LocalSettingsSecure.sample.php`` 파일을 복사하여 ``www/LocalSettingsSecure.php`` 파일을 만들고
   내용을 적절히 수정해주세요.
2. 데이터베이스를 설치·실행하여 주세요.
3. 다음 명령을 실행하면 미디어위키가 실행됩니다. 내용은 필요에 따라 적절히 수정해주세요.
    ``www/fw-resources``, ``www/skins`` 등을 볼륨으로 지정하면 바로 변경 사항을 확인할 수 있습니다.
    ```bash
    docker build -t femiwiki.com .
    docker network create -d bridge mynetwork
    docker run \
        --detach \
        --name femiwiki.com \
        --network mynetwork \
        -p 9000:9000 \
        -e "PROTOCOL=https" \
        -e "HOST=femiwiki.com" \
        -e "DB=localhost" \
        -e "DB_PW=root" \
        -e "PARSOID=parsoid.femiwiki.com" \
        femiwiki.com
    ```

MacOS 설치 예시:
```bash
    git clone https://github.com/femiwiki/femiwiki.com.git
    cd femiwiki.com
    cp www/LocalSettingsSecure.sample.php www/LocalSettingsSecure.php
    DB_PW=root vagrant up dev-www
    open http://192.168.50.10
```

## 실제 서버에 배포하기

https://github.com/femiwiki/swarm

~~실제 서버에 배포를 하려면 다음 절차를 따라주세요. 단 **배포키가 있어야만 합니다.**~~

1. ~~AWS credential 및 설정 파일을 ``PROJECT_HOME/.aws`` 디렉터리에 복사하세요.~~
2. ~~AWS에 ``www``이라는 이름의 보안 그룹을 만들고 SSH, HTTP, HTTPS 접속을 허용하세요.~~
3. ~~AWS에 ``parsoid``라는 이름의 보안 그룹을 만들고 TCP 8142 접속을 허용하세요.~~
4. ~~AWS에 ``fw``라는 이름의 키-페어를 만들고 비공개키를 ``PROJECT_HOME/fw.pem`` 이름으로 저장하세요.~~
5. ~~"개발하기" 섹션에서 명시한 방법에 따라 로컬 환경에서 먼저 테스트를 수행합니다.~~
6. ~~이상이 없으면 다음 명령을 실행하여 코드를 배포합니다. ``DB_PW=<PW> ./update prod``~~
   ~~(&gt;PW&lt; 대신 원하는 위키 관리자 패스워드를 입력하세요)~~

--------

The source code of *femiwiki.com* is primarily distributed under the terms of
the [GNU Affero General Public License v3.0] or any later version. See
[COPYRIGHT] for details.

[femiwiki.com]: https://femiwiki.com
[GNU Affero General Public License v3.0]: LICENSE
[COPYRIGHT]: COPYRIGHT
