FROM --platform=$TARGETPLATFORM caddy:2-builder AS caddy
ARG CADDY_MWCACHE_COMMIT=a8314e0eb81aa77b9410cd58e9c8d5c1590e41a1
ARG CADDY_CERTMAGIC_S3_COMMIT=8af482af5f392bcd01b5f3e72653dc24e0b96012
ARG CADDY_RATELIMIT_COMMIT=5625512f24f6f59d6f64fb3aafe5eecff0b286db
ARG CADDY_CLOUDFRONT_IP_COMMIT=784a775e472892e784dc7cc6ff4ae2799d8404d8

# certmagic-s3 lists a directory without a trailing slash, so the rate limiter
# never found another generation's counts; see femiwiki/infra#1121
COPY certmagic-s3-list.patch /tmp/
RUN git clone -q https://github.com/ss098/certmagic-s3 /src/certmagic-s3 &&\
    git -C /src/certmagic-s3 checkout -q "${CADDY_CERTMAGIC_S3_COMMIT}" &&\
    git -C /src/certmagic-s3 apply /tmp/certmagic-s3-list.patch

RUN XCADDY_DEBUG=1 xcaddy build v2.11.7 \
      --with github.com/caddy-dns/route53 \
      --with "github.com/femiwiki/caddy-mwcache@${CADDY_MWCACHE_COMMIT}" \
      --with "github.com/ss098/certmagic-s3=/src/certmagic-s3" \
      --with "github.com/mholt/caddy-ratelimit@${CADDY_RATELIMIT_COMMIT}" \
      --with "github.com/femiwiki/caddy-cloudfront-ip@${CADDY_CLOUDFRONT_IP_COMMIT}" \
      ;

# The builder carries the Go toolchain: 2.84 GB to ship a 95 MB binary that
# every femiwiki image build pulls. A final stage makes it about 140 MB.
# Alpine rather than scratch, for the CA bundle the route53 and S3 modules need.
FROM alpine:3.24
COPY --from=caddy /usr/bin/caddy /usr/bin/caddy
