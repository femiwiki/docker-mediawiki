FROM --platform=$TARGETPLATFORM caddy:2-builder AS caddy
ARG CADDY_MWCACHE_COMMIT=59f5bd602d7269304a5277a6a2c4d72910948ac4
ARG CADDY_CERTMAGIC_S3_COMMIT=8af482af5f392bcd01b5f3e72653dc24e0b96012
ARG CADDY_RATELIMIT_COMMIT=5625512f24f6f59d6f64fb3aafe5eecff0b286db

# certmagic-s3 lists a directory without a trailing slash, so the rate limiter
# never found another generation's counts; see femiwiki/infra#1121
COPY certmagic-s3-list.patch /tmp/
RUN git clone -q https://github.com/ss098/certmagic-s3 /src/certmagic-s3 &&\
    git -C /src/certmagic-s3 checkout -q "${CADDY_CERTMAGIC_S3_COMMIT}" &&\
    git -C /src/certmagic-s3 apply /tmp/certmagic-s3-list.patch

RUN XCADDY_DEBUG=1 xcaddy build v2.11.4 \
      --with github.com/caddy-dns/route53 \
      --with "github.com/femiwiki/caddy-mwcache@${CADDY_MWCACHE_COMMIT}" \
      --with "github.com/ss098/certmagic-s3=/src/certmagic-s3" \
      --with "github.com/mholt/caddy-ratelimit@${CADDY_RATELIMIT_COMMIT}" \
      ;

# The builder carries the Go toolchain: 2.84 GB to ship a 95 MB binary that
# every femiwiki image build pulls. A final stage makes it about 140 MB.
# Alpine rather than scratch, for the CA bundle the route53 and S3 modules need.
FROM alpine:3.22
COPY --from=caddy /usr/bin/caddy /usr/bin/caddy
