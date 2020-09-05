version: '3.5'
services:
  backupbot:
    image: ghcr.io/femiwiki/backupbot:2020-09-05T10-11-eefb914b
    env_file:
      - configs/bot-secret.env
    networks:
      hostnet: {}

networks:
  hostnet:
    external: true
    name: host
