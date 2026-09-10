FROM --platform=$TARGETPLATFORM debian:trixie-slim

# poolcounterd is packaged in Debian proper, built for arm64 among others, so
# there is nothing to compile and security updates arrive with the base image.
RUN apt-get update &&\
    apt-get install -y --no-install-recommends poolcounter &&\
    rm -rf /var/lib/apt/lists/*

USER nobody

EXPOSE 7531

# -l is the only option poolcounterd takes; without it the daemon binds
# localhost only, which is no use to backends on other hosts. Matching
# Debian's own unit here - the security group is what limits who can reach it.
ENTRYPOINT ["/usr/bin/poolcounterd"]
CMD ["-l", "0.0.0.0"]
