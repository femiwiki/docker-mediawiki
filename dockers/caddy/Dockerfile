FROM --platform=$TARGETPLATFORM caddy:2-builder AS caddy
ARG CADDY_MWCACHE_COMMIT=59f5bd602d7269304a5277a6a2c4d72910948ac4
ARG CADDY_CERTMAGIC_S3_COMMIT=62a3ac98984dae7208ba2c126ecf6b0bf638dfa6
ARG CADDY_RATELIMIT_COMMIT=5625512f24f6f59d6f64fb3aafe5eecff0b286db

RUN XCADDY_DEBUG=1 xcaddy build v2.11.4 \
      --with github.com/caddy-dns/route53 \
      --with "github.com/femiwiki/caddy-mwcache@${CADDY_MWCACHE_COMMIT}" \
      --with "github.com/ss098/certmagic-s3@${CADDY_CERTMAGIC_S3_COMMIT}" \
      --with "github.com/mholt/caddy-ratelimit@${CADDY_RATELIMIT_COMMIT}" \
      ;

# The builder carries the Go toolchain: 2.84 GB to ship a 95 MB binary that
# every femiwiki image build pulls. A final stage makes it about 140 MB.
# Alpine rather than scratch, for the CA bundle the route53 and S3 modules need.
FROM alpine:3.22
COPY --from=caddy /usr/bin/caddy /usr/bin/caddy
