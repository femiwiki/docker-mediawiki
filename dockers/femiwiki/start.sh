# shellcheck shell=bash
# Startup shared by run (pages) and jobrunner (jobs), which both source it

# Production reads secrets from SSM; local and CI pass plain env. No tracing, to keep them out of the log.
# shellcheck disable=SC2034  # exported by set -a, read by LocalSettings.php
fw_load_secrets() {
  if [ -z "${SSM_SECRETS:-}" ]; then
    return
  fi
  # This container's own role, when infra gives it a certificate (femiwiki/infra#1173). Without
  # it chamber cannot read a secret, so fail the start.
  php /usr/local/lib/femiwiki/aws-credentials.php

  set +x -a
  WG_DB_PASSWORD="$(chamber read -q mysql/users/mediawiki password)"
  WG_O_AUTH_2_PRIVATE_KEY="$(chamber read -q mediawiki o_auth_2_private_key)"
  WG_RC_FEEDS_DISCORD_URL="$(chamber read -q mediawiki rc_feeds_discord_url)"
  # Missing means LocalSettings.php keeps the fallback captcha, so start anyway.
  WG_H_CAPTCHA_SECRET_KEY="$(chamber read -q mediawiki/h_captcha secret_key)" || WG_H_CAPTCHA_SECRET_KEY=
  WG_SECRET_KEY="$(chamber read -q mediawiki site_key)"
  WG_SMTP_PASSWORD="$(chamber read -q mediawiki/smtp password)"
  WG_SMTP_USERNAME="$(chamber read -q mediawiki/smtp username)"
  # Missing means BounceHandler keeps its default, so start anyway.
  WG_VERP_SECRET="$(chamber read -q mediawiki verp_secret)" || WG_VERP_SECRET=
  WG_BOUNCE_HANDLER_TOKEN="$(chamber read -q mediawiki/bounce_handler token)" || WG_BOUNCE_HANDLER_TOKEN=
  # Missing means slow-request profiles stay in files, so start anyway (femiwiki/femiwiki#614).
  FW_PROFILER_PYROSCOPE_PASSWORD="$(chamber read -q mediawiki/pyroscope password)" || FW_PROFILER_PYROSCOPE_PASSWORD=
  set +a -x

  # PageViewInfoGA authenticates to Google with an OIDC token for this instance's IAM role
  # (femiwiki/femiwiki#670). Missing only means no view counts, so start anyway; cron retries.
  install -d -o www-data -g www-data -m 0700 /run/secrets
  php /usr/local/lib/femiwiki/google-subject-token.php ||
    echo 'No Google subject token; PageViewInfo shows no view counts until cron gets one' >&2
  # Chart's renderer accepts only this instance role's tokens (femiwiki/femiwiki#671). Missing
  # only means charts show an error until cron gets one, so start anyway.
  php /usr/local/lib/femiwiki/chart-renderer-token.php ||
    echo 'No chart renderer token; charts fail to render until cron gets one' >&2
}

# Puts the image's settings in place, over what install.php may have written
fw_install_settings() {
  cp -f /etc/mediawiki/LocalSettings.php /srv/femiwiki.com/LocalSettings.php

  # Made here because maintenance and cron run as root and would otherwise
  # create it unwritable for php-fpm
  if [ -n "${FW_PROFILER:-}" ]; then
    install -d -o www-data -g www-data -m 0750 "${FW_PROFILER_DIR:-/tmp/profiler}"
  fi

  if [ -n "${MEDIAWIKI_HOTFIX_SNIPPET:-}" ]; then
    cat <<HOTFIX > /etc/mediawiki/Hotfix.php
${MEDIAWIKI_HOTFIX_SNIPPET}
HOTFIX
  fi
}

# Replaces the crontab: "jobs" or "tokens", then extra lines. Every container
# that renders pages needs the tokens; BASH_ENV carries the env (femiwiki#588).
fw_install_crontab() {
  local jobs="$1"
  shift
  umask 077
  export -p > /etc/femiwiki-cron.env
  {
    echo 'SHELL=/bin/bash'
    echo 'BASH_ENV=/etc/femiwiki-cron.env'
    # A token and a Roles Anywhere session live an hour; PageViewInfoGA reads its token at most
    # hourly, Chart on every render
    if [ -n "${SSM_SECRETS:-}" ]; then
      echo '*/15 * * * * /usr/local/bin/php /usr/local/lib/femiwiki/aws-credentials.php > /proc/1/fd/1 2>&1'
      echo '*/15 * * * * /usr/local/bin/php /usr/local/lib/femiwiki/google-subject-token.php > /proc/1/fd/1 2>&1'
      echo '*/15 * * * * /usr/local/bin/php /usr/local/lib/femiwiki/chart-renderer-token.php > /proc/1/fd/1 2>&1'
    fi
    if [ "$jobs" = jobs ]; then
      cat /etc/femiwiki/crontab
      # The cached special pages such as Special:AncientPages; a full run took
      # about 10 s on 2026-11-07.
      echo "${FW_SPECIAL_PAGES_SCHEDULE:-1 5 * * *} /usr/local/bin/update-special-pages > /proc/1/fd/1 2>&1"
      # The types LocalSettings keeps off the default runner, a few at a time.
      # Unset means UnlinkedWikibaseFetch; empty means none.
      if [ -n "${FW_JOB_SLOW_TYPES-UnlinkedWikibaseFetch}" ]; then
        for t in $(echo "${FW_JOB_SLOW_TYPES-UnlinkedWikibaseFetch}" | tr ',' '\n'); do
          echo "${FW_JOB_SLOW_SCHEDULE:-*/5 * * * *} /usr/local/bin/php /srv/femiwiki.com/maintenance/run.php runJobs --type=$t --maxjobs=${FW_JOB_SLOW_MAXJOBS:-20} > /proc/1/fd/1 2>&1"
        done
      fi
    fi
    for line in "$@"; do
      echo "$line"
    done
  } | crontab -
  umask 022
}
