페미위키 데이터베이스 서버
========
한국의 페미니즘 위키인 [femiwiki.com]의 데이터베이스 서버 설정파일입니다. 데이터베이스와 memcached, 각종 봇들이 실행됩니다.

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
