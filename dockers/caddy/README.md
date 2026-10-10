# caddy

Route53와 caddy-mwcache 패키지를 설치한 Caddy를 빌드한다.

## v1.11.1

- Bump Caddy from v2.11.4 to v2.11.7

## v1.11.0

- Add caddy-cloudfront-ip [784a775](https://github.com/femiwiki/caddy-cloudfront-ip/commit/784a775e472892e784dc7cc6ff4ae2799d8404d8)

## v1.10.1

- Bump caddy-mwcache to [aad9812](https://github.com/femiwiki/caddy-mwcache/commit/aad98123cedb9d9d2ef5e4c07affdb8a66902d75)

## v1.10.0

- Bump caddy-mwcache to [4dffb41](https://github.com/femiwiki/caddy-mwcache/commit/4dffb41d2f71f4334a0ae5fe3691a9042c0844fd)

## v1.9.0

- Bump caddy-mwcache to [485f578](https://github.com/femiwiki/caddy-mwcache/commit/485f5782fcdf8da87a7a12bab58022f2069485dc)

## v1.8.2

- let the rate limiter read the counts other generations share

## v1.8.1

- Bump caddy-mwcache to [59f5bd6](https://github.com/femiwiki/caddy-mwcache/commit/59f5bd602d7269304a5277a6a2c4d72910948ac4)

## v1.8.0

- Bump caddy-mwcache to [88764d1](https://github.com/femiwiki/caddy-mwcache/commit/88764d100aa9c829c001d1c4c99ee40ff0c3ff93)

## v1.7.1

- Bump caddy-mwcache to [d4c2145](https://github.com/femiwiki/caddy-mwcache/commit/d4c21458f2f538b3b101cde8944784bacac3a4fe)

## v1.7.0

- Bump caddy-mwcache to [c38104e](https://github.com/femiwiki/caddy-mwcache/commit/c38104e85938f11d6475cc8007bef8ac2222f948)

## v1.6.2

- Ship only the binary. The image carried the Go toolchain it was built with, 2.84 GB for a 95 MB `caddy`; a final stage brings it to about 140 MB. The binary and its plugins are unchanged.

## v1.6.1

- Pin caddy-mwcache to a commit of its `main`, [376c535](https://github.com/femiwiki/caddy-mwcache/commit/376c5358d670eb817bdd4d31086d14f8dddfad2c), instead of the v0.3.0 tag. Its Go code is the same as v0.3.0.

## v1.6.0

- Bump caddy-mwcache to [v0.3.0](https://github.com/femiwiki/caddy-mwcache/releases/tag/v0.3.0)

## v1.5.3

- Bump caddy-mwcache to [v0.2.0](https://github.com/femiwiki/caddy-mwcache/releases/tag/v0.2.0)

## v1.5.2

- Bump caddy-mwcache to [v0.1.2](https://github.com/femiwiki/caddy-mwcache/releases/tag/v0.1.2)

## v1.5.1

- Bump caddy-mwcache to [v0.1.1](https://github.com/femiwiki/caddy-mwcache/releases/tag/v0.1.1)

## v1.5.0

- Install [caddy-ratelimit](https://github.com/mholt/caddy-ratelimit) 5625512f24f6f59d6f64fb3aafe5eecff0b286db

## v1.4.0

- Bump Caddy from v2.8.4 to v2.11.4
- Bump caddy-mwcache to v0.1.0

## v1.3.3

- Downgrade Caddy from v2.9.1 to v2.8.4

## v1.3.2

- Rollback ss098/certmagic-s3 to 62a3ac98984dae7208ba2c126ecf6b0bf638dfa6 (https://github.com/ss098/certmagic-s3/issues/17)

## v1.3.1

- Bump Caddy-mwcache to fd96237785afb28c3ebd06b7b0ec35e590ff8342

## v1.3.0

- Bump Caddy to v2.9.1

## v1.2.0

- Install [certmagic-s3](https://github.com/ss098/certmagic-s3)

## v1.1.0

- Bump Caddy to v2.8.4

## v1.0.0
