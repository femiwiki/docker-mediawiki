페미위키 데이터베이스 서버
========
한국의 페미니즘 위키인 [femiwiki.com]에 사용되는 데이터베이스 외 기타 서비스 서버입니다.

|| 내용
:---|----
기능 | 데이터베이스 및 크론잡
Base AMI | [Femiwiki Base AMI](https://github.com/femiwiki/ami)

```sh
git clone https://github.com/femiwiki/swarm.git ~/swarm --depth=1
cp ~/swarm/secret.sample ~/swarm/secret
vim ~/swarm/secret
# 시크릿을 입력해주세요

docker swarm init
docker stack deploy -c ~/swarm/database.yml database
docker stack deploy -c ~/swarm/memcached.yml memcached
docker stack deploy -c ~/swarm/bots.yml bots
```

&nbsp;

--------

The source code of *femiwiki/database* is primarily distributed under the terms
of the [GNU Affero General Public License v3.0] or any later version. See
[COPYRIGHT] for details.

[femiwiki.com]: https://femiwiki.com
[GNU Affero General Public License v3.0]: LICENSE
[COPYRIGHT]: COPYRIGHT
