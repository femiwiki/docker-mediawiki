FROM --platform=$TARGETPLATFORM caddy:2-builder AS caddy
ARG CADDY_MWCACHE_COMMIT=fd96237785afb28c3ebd06b7b0ec35e590ff8342
ARG CADDY_CERTMAGIC_S3_COMMIT=62a3ac98984dae7208ba2c126ecf6b0bf638dfa6

RUN XCADDY_DEBUG=1 xcaddy build v2.9.1 \
      --with github.com/caddy-dns/route53 \
      --with "github.com/femiwiki/caddy-mwcache@${CADDY_MWCACHE_COMMIT}" \
      --with "github.com/ss098/certmagic-s3@${CADDY_CERTMAGIC_S3_COMMIT}" \
      ;
