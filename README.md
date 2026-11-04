# GuardLMS for Totara — alpha 0.1.0

A standalone `local_guardlms` plugin for self-hosted Totara. It connects a site to
GuardLMS and reports its installed Totara release/build, database-installed
plugins, PHP version, database engine/version and allowlisted server metadata.

**Status: alpha; real Totara runtime and browser E2E verification is pending.**
No licensed Totara source or test instance was available on 2026-11-04. The plugin
regression tests use explicit Totara API doubles; the GuardLMS feature tests run
the real Laravel application. Neither establishes Totara runtime compatibility.
The linked Docker/browser harness is prepared but has not run against Totara.

The intended initial baseline is Totara 16 or later, with PHP 7.4+ syntax and the
PHP version supported by that Totara release. This is a target, **not a tested
support matrix**. Totara's [versioning documentation](https://totara.atlassian.net/wiki/spaces/DEV/pages/121188024/Versioning)
explains why its build and release numbers must be handled separately from
Moodle. The connector never uses Moodle's update service or assigns Moodle
plugin-directory/advisory identities to Totara plugins.

## Install on a disposable site first

1. Deploy the matching GuardLMS receiver/backend and frontend [changes](https://github.com/LdesignMedia/guardlms/tree/feature-totara-connector). It adds `/api/externalpush/totara` and `/connect/totara`. No new database
   migration is required. An older GuardLMS deployment does not accept Totara.
2. Clone `LdesignMedia/totara-local_guardlms` and create the installable ZIP with
   `./scripts/package.sh /absolute/output/guardlms-totara-0.1.0.zip`. Extract its
   `guardlms` directory into `server/local/guardlms` in your Totara source.
   The package excludes tests, CI and development scripts.
3. Run Totara's normal upgrade as the web-service account, for example
   `php server/admin/cli/upgrade.php --non-interactive`, from the Totara root.
4. Log in as a site administrator. Open **Site administration → Plugins → Local
   plugins → GuardLMS for Totara**. Preview the payload, then click
   **Connect / reconnect to GuardLMS**.
5. Sign in to GuardLMS, review the canonical site and client, and approve. The
   browser returns to Totara; the server exchanges the single-use code and sends
   the first inventory. Check the connection status and last accepted inventory.
6. Keep Totara cron running normally. The registered scheduled task
   `\local_guardlms\task\push_inventory` reports once per day, at a randomized
   hour/minute. Retry immediately with **Push inventory now** or
   `php server/local/guardlms/cli/push.php`.

This is a separate distribution with the same component name as the Moodle
plugin. **Do not install both into the same LMS**, or overwrite an existing
Moodle `local_guardlms` installation with it.

For self-hosted GuardLMS, set the trusted service URL in Totara's root config:

```php
$CFG->forced_plugin_settings['local_guardlms']['baseurl'] = 'https://your-guardlms.example';
```

HTTPS with a valid trusted certificate is required. The configured service may
include a base path. Credentials, query strings and fragments in that URL are
rejected, and authenticated requests never follow redirects. The receiver path
is fixed; the plugin does not accept a remote push-path override.

## Reporting and connection behavior

- Only installed database versions are reported. Code present on disk but not
  installed is omitted. Unknown plugin enabled state stays null.
- Inventory replacement is atomic. Missing/malformed plugin lists are refused;
  a registry failure sends no partial inventory. A complete empty list can prune
  previous plugins. Older reports cannot replace a newer accepted report.
- Updates remain unknown: inventory is not evidence that a component is current.
  Totara-specific advisory matching, configuration audits, plugin source audits,
  and browser SDK injection are not part of this initial connector.
- No learner/course records, credentials, database names, hostnames, connection
  strings or plugin settings are collected. Runtime OS/architecture and the
  configured web-server signature may be reported. CLI cannot always supply a
  web-server signature.
- Connection, push, disconnect and preview controls require a logged-in site
  administrator. Actions require POST and a session key. Consent state is
  bound to the initiating administrator session and expires after 15 minutes.
- API keys are encrypted with AES-256-GCM in `config_plugins`. The encryption key
  is stored separately at `dataroot/local_guardlms/key` (0600), in a 0700 directory.
  Back up that key with dataroot. Missing keys require reconnecting; do not delete
  them during routine upgrades. All application nodes need the same protected
  dataroot/key and access as the web-service account.
- Credentials are bound to both the canonical `$CFG->wwwroot` and service URL.
  Changing either prevents their use until reconnecting. A clone preserving
  *both* original values and dataroot still contains live credentials: remove the
  connection on a clone before running cron, and set its own canonical URL.
- An initial push failure leaves the connection intact for retry via daily cron.
  The settings page shows failed pushes and refused keys. Reconnecting rotates
  the inventory key through GuardLMS. **Disconnect** clears local credentials,
  ownership meta tag, pending state and reporting status; it does not revoke the
  server token. Revoke its API key in GuardLMS if server-side invalidation is
  needed. It otherwise expires at the time shown in Totara.
- Removing the plugin does not remove previously received GuardLMS inventory;
  its freshness ages normally. Disconnect before uninstalling.

## Standalone verification

```sh
php tests/unit.php
phpcs --standard=moodle callback.php classes cli db index.php lang lib.php settings.php version.php
```

The standalone checks explicitly double Totara APIs and HTTP transport. They do
not prove compatibility with a real Totara runtime. CI runs these checks and PHP
syntax validation on PHP 8.1–8.4 and packages the plugin.

The GuardLMS receiver/frontend checks and real-runtime Docker/browser harness
live in [GuardLMS integrations/totara](https://github.com/LdesignMedia/guardlms/tree/main/integrations/totara).

## Real Totara E2E verification — pending

No licensed Totara runtime is available. Follow the
[GuardLMS E2E harness guide](https://github.com/LdesignMedia/guardlms/blob/main/integrations/totara/README.md)
when one becomes available. Set `TOTARA_PLUGIN_ROOT` to this checkout and
`TOTARA_SOURCE_ROOT` to the licensed Totara source. The harness exercises the real
installed plugin against an isolated GuardLMS receiver. Its consent-code fixture
is separate from the GuardLMS approval UI tests.

## Package

```sh
./scripts/package.sh /absolute/output/guardlms-totara-0.1.0.zip
```

The archive contains only the installable `guardlms/` plugin, its GPL license,
and this guide. It excludes test doubles, receiver fixtures and private sources.


## Verification record — 2026-11-04

- 159 backend tests / 651 assertions passed, including Totara and existing
  Canvas/connect/platform regressions. The suite emitted 78 PHPUnit deprecations.
- 38 standalone plugin checks passed, with explicit Totara API/transport doubles.
- 44 focused frontend tests passed; TypeScript and focused ESLint passed.
- Moodle PHP coding standards passed with no errors or warnings.
- Plugin/harness PHP syntax, shell syntax, browser script syntax and Compose
  configuration checks passed.
- Real Totara setup, HTTPS runtime delivery and browser E2E: **not run**. Setup
  correctly stops when the licensed Totara source is absent.
