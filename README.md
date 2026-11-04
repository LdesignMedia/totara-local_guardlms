# GuardLMS for Totara

[![Plugin checks](https://github.com/LdesignMedia/totara-local_guardlms/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/LdesignMedia/totara-local_guardlms/actions/workflows/ci.yml)

Connect your self-hosted Totara site to [GuardLMS](https://guardlms.com) and report
its installed software inventory automatically.

The plugin provides a **Connect / reconnect to GuardLMS** button: sign in to
GuardLMS, approve the site and return to Totara. A site-bound inventory key is
installed automatically and the first report is sent. **No API key to create or
copy.**

**Alpha 0.1.0 — real Totara runtime and browser E2E verification is pending.**
The standalone checks use explicit Totara API and HTTP transport doubles; they do
not establish compatibility with a licensed Totara installation. The intended
baseline is Totara 16 or later, not a tested support matrix. Evaluate the plugin
on a disposable, licensed Totara site before considering production use.

This is a private repository. Ask LdesignMedia for access before installation.

## What the plugin does

- **Keyless connection.** Connect and reconnect through the GuardLMS consent
  screen, using a single-use code exchanged by the Totara server.
- **Daily inventory.** Reports the installed Totara release/build, installed
  plugins, PHP version, database product/version and selected server metadata.
- **Administrator controls.** Preview the exact payload, push it on demand,
  inspect the last accepted report and key expiry, or disconnect.
- **Connection warnings.** Shows failed reports and warns when GuardLMS refuses
  the stored key, so an installed connection is not mistaken for healthy reporting.
- **Protected credentials.** Encrypts the site-bound inventory key and keeps its
  encryption key separately in protected dataroot storage.

This is a separate distribution from the Moodle plugin, with the same
`local_guardlms` component name. **Do not install both into the same LMS or replace
an existing Moodle `local_guardlms` installation with this Totara package.**

## Requirements and current validation

| Requirement | Details |
| --- | --- |
| Totara | Self-hosted; intended baseline **16+**, with runtime verification still pending |
| PHP | PHP 7.4+ syntax; use the PHP version supported by your Totara release. CI checks PHP 8.1–8.4 with API doubles. |
| GuardLMS | The Totara receiver and consent support must be deployed first; see [GuardLMS PR #2877](https://github.com/LdesignMedia/guardlms/pull/2877). No new database migration is required. |
| Access | Repository/server access to install the plugin, then a site-administrator account to manage it |
| Storage | Protected dataroot writable by the web-service account; shared across application nodes |
| Networking | Access to the configured GuardLMS HTTPS service with a valid, trusted certificate |
| Packaging | Bash and `zip` when building the installable archive |

An older GuardLMS deployment does not accept `/api/externalpush/totara` or the
`/connect/totara` consent flow. Passing the standalone CI checks is not evidence
that a particular Totara/PHP combination is supported.

## Installation

### 1. Create the installable package

```sh
git clone git@github.com:LdesignMedia/totara-local_guardlms.git
cd totara-local_guardlms
./scripts/package.sh "$PWD/dist/guardlms-totara-0.1.0.zip"
```

The archive contains an installable **`guardlms/`** directory, the GPL license and
this guide. It excludes tests, CI files, development scripts and private sources.
The package script requires an absolute output path and refuses to overwrite an
existing archive; choose a new filename when packaging again.

A successful [CI run](https://github.com/LdesignMedia/totara-local_guardlms/actions/workflows/ci.yml)
also provides a `guardlms-totara-alpha` artifact containing the ZIP. Check the
branch and commit it was built from.

### 2. Install on a disposable Totara site

Extract the archive's `guardlms` directory into:

```text
<totara-source-root>/server/local/guardlms
```

Run the normal Totara upgrade as the web-service account. From the Totara source
root, for example:

```sh
php server/admin/cli/upgrade.php --non-interactive
```

Do not install the Git repository itself as the plugin directory: use the packaged
`guardlms/` directory so development fixtures stay outside the installed plugin.

### 3. Open the plugin

Log in as a site administrator and open:

**Site administration → Plugins → Local plugins → GuardLMS for Totara**

Click **Preview payload** to inspect the inventory before connecting.

## Connect to GuardLMS

1. Click **Connect / reconnect to GuardLMS** on the plugin page.
2. Sign in to GuardLMS, review the canonical site and client, and approve.
3. You return to Totara. The server installs the site-bound key and sends the
   first inventory. Check **Last accepted inventory** and any reporting warning.

Keep the same logged-in Totara administrator session while connecting; the
consent state expires after 15 minutes and cannot be reused.

The canonical site URL comes from `$CFG->wwwroot`. Use the same value in browser
requests and CLI/cron. The stored credential is bound to both that URL and the
GuardLMS service URL; changing either requires reconnecting.

### Reading the connection status

| Display / warning | Meaning and next step |
| --- | --- |
| **Disconnected. Connect this site to GuardLMS.** | No usable connection is installed for the current site/service URLs. Connect this site. |
| **Connected to GuardLMS** | A credential is installed. Check the last accepted report and warnings to confirm delivery. |
| **GuardLMS refused the stored key.** | An authenticated request returned HTTP 401 or 403. Reconnect to renew access, or retry after a temporary service problem clears. |
| **The last inventory push failed.** | Inspect the connection and scheduled-task log, then use **Push inventory now**. |

A failed initial push leaves the connection installed. Retry manually or let the
registered daily task try again. An accepted push clears the reporting and
refused-key warnings. Reconnecting replaces previous inventory keys for this
website through GuardLMS.

**Disconnect** clears local credentials, the ownership meta tag, pending consent
state and reporting status. It stops local reporting but does not delete the
GuardLMS website or revoke its server-side inventory key. Revoke the key in
GuardLMS if needed; otherwise it expires at the time shown on the plugin page.

### Site ownership

The plugin includes an HTML-head callback that serves the public ownership
verification meta tag after connection. Check the website's verification status
in GuardLMS; real Totara rendering of this callback is part of the pending
runtime validation. If ownership remains pending, follow GuardLMS's normal
verification instructions.

## Reporting and privacy

| Inventory | Contents |
| --- | --- |
| Totara | Installed Totara release and build/version, kept separate from inherited Moodle metadata |
| Plugins | Database-installed component IDs and versions; enabled state where known |
| PHP | Running PHP version |
| Database | Recognized database product/version, when available |
| Server | OS family, architecture and the web-server signature when available |

Code present on disk but not installed in the database is omitted. Unknown
plugin enabled state stays null. CLI requests cannot always supply a web-server
signature. Totara's [versioning documentation](https://totara.atlassian.net/wiki/spaces/DEV/pages/121188024/Versioning)
provides background on its release and build numbers.

The complete inventory replaces only this connector's previous inventory
atomically. A missing or malformed plugin list is refused; a registry failure
sends no partial inventory. Older reports cannot replace a newer accepted report.

Updates remain **unknown**. Inventory does not establish that a component is
current. The connector does not use Moodle's update service or assign Moodle
plugin-directory/advisory identities to Totara plugins. Totara-specific advisory
matching, configuration reviews, plugin source audits and browser SDK injection
are outside this initial connector.

Inventory collection excludes learner/course records, credentials, database
names, database hostnames, connection strings and plugin settings. The canonical
site URL and the allowlisted software/server metadata above are reported.

## Daily and on-demand reporting

Keep Totara cron running normally. The registered task
`\local_guardlms\task\push_inventory` (**Push Totara inventory to GuardLMS**)
runs once per day at a randomized hour and minute.

To send a report immediately, click **Push inventory now**, or run this command
as the web-service account from the Totara source root:

```sh
php server/local/guardlms/cli/push.php
```

The CLI prints `GuardLMS Totara inventory accepted.` on success and exits with a
nonzero status if reporting fails. Daily reporting requires an enabled connection.

## Advanced configuration and operations

### Self-hosted GuardLMS

The default service URL is `https://dashboard.guardlms.com`. For another trusted
GuardLMS deployment, set this in Totara's root configuration:

```php
$CFG->forced_plugin_settings['local_guardlms']['baseurl'] = 'https://your-guardlms.example';
```

The service URL must use HTTPS with a trusted certificate and can include a base
path. Credentials, query strings and fragments are rejected. Authenticated
requests never follow redirects. The Totara receiver path is fixed; a remote
push-path override is not accepted. Reconnect after changing the service URL.

### Credential storage and backups

The plugin encrypts inventory keys with AES-256-GCM in `config_plugins`. The
separate encryption key is generated automatically at:

```text
<dataroot>/local_guardlms/key
```

The directory uses mode `0700` and the key uses `0600`. Back up the key with
dataroot and preserve it during upgrades. All application nodes need the same
protected dataroot/key, accessible as the web-service account. If the key is lost,
restore it from backup or reconnect to install a usable credential.

### Clones, staging sites and uninstalling

Before running cron on a cloned site, remove its connection and set its own
canonical `$CFG->wwwroot`, then connect it to its own GuardLMS website. A clone
that preserves both original URLs and dataroot still contains live credentials;
URL binding alone cannot distinguish it from the original site.

Disconnect before uninstalling. Removing the plugin does not remove inventory
already received by GuardLMS; that inventory's freshness ages normally. Preserve
or archive the encryption key with any retained connection data and revoke the
server-side API key separately if necessary.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| Totara connection is not accepted by GuardLMS | Confirm that the matching receiver and consent changes are deployed. |
| Invalid, expired or reused connection state | Start again from the plugin page, using the same administrator session, and finish within 15 minutes. |
| Refused key or HTTP 401/403 | Reconnect after key expiry/revocation or removal of the GuardLMS website; retry if a temporary service problem caused the refusal. |
| HTTP 422 or site URL mismatch | Compare `$CFG->wwwroot` with the registered website URL, including scheme, path and port. Give a staging clone its own connection. |
| Cannot reach GuardLMS securely | Check network access, the configured service URL and its trusted TLS certificate chain. Keep certificate validation enabled. |
| Cannot read or write the protected credential | Check dataroot permissions and restore the original encryption key if available; reconnect if it was lost. |
| Missing Totara metadata or incomplete inventory | Confirm this is an installed Totara site with a complete plugin registry. No partial inventory is sent. |
| No new daily reports | Check Totara cron, the registered task log, connection status and the last accepted inventory. |

## Development and verification

### Standalone checks

```sh
php tests/unit.php
phpcs --standard=moodle callback.php classes cli db index.php lang lib.php settings.php version.php
```

The regression checks explicitly double Totara APIs and HTTP transport. CI runs
those checks, PHP syntax validation and packaging on PHP 8.1–8.4. It does not run
a real Totara installation.

### Real Totara E2E — pending

No licensed Totara source or test instance was available for the initial
2026-11-04 verification. The prepared
[GuardLMS E2E harness](https://github.com/LdesignMedia/guardlms/blob/4d8cf0031d04e94a737c492e6d7087cdc023ca63/integrations/totara/README.md)
has not run against Totara. When a licensed runtime becomes available, follow that
guide with `TOTARA_PLUGIN_ROOT` pointing to this checkout and `TOTARA_SOURCE_ROOT`
pointing to the licensed source. The harness uses an isolated GuardLMS receiver;
its consent-code fixture is separate from the GuardLMS approval UI tests.

The receiver/frontend checks and harness source are pinned in
[GuardLMS integrations/totara](https://github.com/LdesignMedia/guardlms/tree/4d8cf0031d04e94a737c492e6d7087cdc023ca63/integrations/totara).

### Initial verification record — 2026-11-04

| Check | Recorded result |
| --- | --- |
| Backend receiver/connect/platform regressions | 159 tests / 651 assertions passed; 78 PHPUnit deprecations emitted |
| Standalone plugin checks | 38 passed, using Totara API/transport doubles |
| Focused frontend tests | 44 passed; TypeScript and focused ESLint passed |
| Moodle PHP coding standards | Passed without errors or warnings |
| PHP, shell, browser-script and Compose checks | Passed |
| Real Totara setup, HTTPS delivery and browser E2E | **Not run**; setup correctly stops without licensed Totara source |
