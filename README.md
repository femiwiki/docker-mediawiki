[페미위키] 소스코드
========
한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 소스 코드입니다.

[도커]를 써서 개인 컴퓨터에서 페미위키를 실행할 수 있습니다. 자세한 배포과정은
[femiwiki/swarm]을 참고해주세요.

```bash
cp configs/LocalSettingsSecure.sample.php configs/LocalSettingsSecure.php
# configs/LocalSettingsSecure.php 를 적절히 수정해주세요

docker build -t femiwiki/femiwiki.com .
docker network create -d bridge mynetwork
docker run \
  --detach \
  --name femiwiki.com \
  --network mynetwork \
  -p 9000:9000 \
  --volume ${PWD}/configs/LocalSettingsSecure.php:/opt/femiwiki/LocalSettingsSecure.php \
  -e 'PROTOCOL=https' \
  -e 'HOST=femiwiki.com' \
  -e 'DB=localhost' \
  -e 'DB_PW=root' \
  -e 'PARSOID=parsoid.femiwiki.com' \
  femiwiki/femiwiki.com

# 'fw-resources', 'skins' 등을 볼륨으로 지정하면 편하게 변경 사항을 바로바로
# 확인하실 수 있습니다.
```

&nbsp;

--------

The source code of *femiwiki.com* is primarily distributed under the terms of
the [GNU Affero General Public License v3.0] or any later version. See
[COPYRIGHT] for details.

[페미위키]: https://femiwiki.com
[femiwiki.com]: https://femiwiki.com
[도커]: https://www.docker.com
[femiwiki/swarm]: https://github.com/femiwiki/swarm
[GNU Affero General Public License v3.0]: LICENSE
[COPYRIGHT]: COPYRIGHT
