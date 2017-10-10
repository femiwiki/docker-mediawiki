[페미위키][femiwiki.com] 소스코드
========
한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 소스 코드 저장소입니다. 다음 코드들을 포함하고 있습니다.

* 웹 서버 및 시각편집기 서버(parsoid)용 Vagrantfile 및 프로비저닝 스크립트
* 개발/테스트 및 실제 서버용 배포 스크립트
* 페미위키 스킨

## 개발 및 배포에 필요한 소프트웨어

* [Vagrant](https://www.vagrantup.com/)

## 개발하기

개발 및 테스트 용도로 로컬 컴퓨터에서 페미위키를 실행하려면 다음 절차를 따라주세요.

1. ``www/LocalSettingsSecure.sample.php`` 파일을 복사하여 ``www/LocalSettingsSecure.php`` 파일을 만들고
   내용을 적절히 수정해주세요. 로컬에서 테스트만 하는 것이기 때문에 아무 내용도 수정하지 않아도 됩니다.
   참고로 데이터베이스 ID/PW는 ``root/root``로 고정되어 있습니다.
2. 다음 명령을 실행하면 미디어위키가 실행됩니다. ``ADMIN_PW=<PW> vagrant up dev-www``
   (<PW> 대신 원하는 위키 관리자 패스워드를 입력하세요)
3. 다음 명령을 실행하면 시각편집기 서버가 실행됩니다 ``vagrant up dev-parsoid``
   개발 환경에서 시각편집기를 사용하지 않을 것이라면 굳이 하지 않아도 됩니다.
4. 브라우저에서 다음 주소에 접속하세요. <http://192.168.50.10>
5. ``www/fw-resources`` 및 ``www/skins`` 디렉터리는 심볼릭 링크가 걸려 있으므로 내용을 수정하고 새로고침을 하면
   바로 변경 사항을 확인할 수 있습니다.
6. ``LocalSettings.php`` 파일 등을 수정한 후에는 다음 명령을 실행해야만 반영이 됩니다. ``ADMIN_PW=<PW> ./update dev``
   (<PW> 대신 원하는 위키 관리자 패스워드를 입력하세요)

MacOS 설치 예시:

    git clone https://github.com/femiwiki/femiwiki.com.git
    cd femiwiki.com
    cp www/LocalSettingsSecure.sample.php www/LocalSettingsSecure.php
    ADMIN_PW=blahblah vagrant up dev-www
    open http://192.168.50.10


## 실제 서버에 배포하기

실제 서버에 배포를 하려면 다음 절차를 따라주세요. 단 **배포키가 있어야만 합니다.**

1. AWS credential 및 설정 파일을 ``PROJECT_HOME/.aws`` 디렉터리에 복사하세요.
2. AWS에 ``www``이라는 이름의 보안 그룹을 만들고 SSH, HTTP, HTTPS 접속을 허용하세요.
3. AWS에 ``parsoid``라는 이름의 보안 그룹을 만들고 TCP 8142 접속을 허용하세요.
4. AWS에 ``fw``라는 이름의 키-페어를 만들고 비공개키를 ``PROJECT_HOME/fw.pem`` 이름으로 저장하세요.
5. "개발하기" 섹션에서 명시한 방법에 따라 로컬 환경에서 먼저 테스트를 수행합니다.
6. 이상이 없으면 다음 명령을 실행하여 코드를 배포합니다. ``ADMIN_PW=<PW> ./update prod``
   (<PW> 대신 원하는 위키 관리자 패스워드를 입력하세요)

<br>

--------

The source code of *femiwiki.com* is primarily distributed under the terms of
the [GNU Affero General Public License v3.0] or any later version. See
[COPYRIGHT] for details.

[femiwiki.com]: https://femiwiki.com
[GNU Affero General Public License v3.0]: LICENSE
[COPYRIGHT]: COPYRIGHT
