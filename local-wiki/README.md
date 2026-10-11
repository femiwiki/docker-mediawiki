# Local wiki

A wiki on <http://localhost:8080/> that looks like femiwiki.com: production's image and `LocalSettings.php`, FemiwikiSkin and OOUIFemiwikiTheme at any ref, the site pages and gadgets from [remote-gadgets] with draft pull requests on top, `MediaWiki:Sidebar` from femiwiki.com, and a few real articles. Use it to try a skin or gadget change logged in, in VisualEditor or in a dialog, which the static skin preview cannot show.

```sh
./wiki up      # the first start installs and loads everything, a few minutes
./wiki down    # stop; ./wiki up again keeps the pages
./wiki reset   # stop and delete the database
```

Settings and the test account are in `.env`, copied from [`.env.example`](.env.example) on the first start. After changing a ref, run `./wiki up` for the skin or `./wiki gadgets` for remote-gadgets; `./wiki articles` and `./wiki pages` fetch from femiwiki.com again. The skin checkout is `.cache/FemiwikiSkin`, mounted into the containers, so an edit there shows on the next page load.

It needs Docker Compose. With rootless Podman, start `podman system service` and set `DOCKER_HOST=unix://$XDG_RUNTIME_DIR/podman/podman.sock`.

Left out, since they need production secrets or services: e-mail, hCaptcha (off in `MEDIAWIKI_DEBUG_MODE`), uploads to S3 (files show from femiwiki.com instead), Discord recent-changes feed, Google Analytics page views, OAuth signing keys, cron jobs, the CDN and the production Caddyfile's rate limits and cache.

[remote-gadgets]: https://github.com/femiwiki/remote-gadgets
