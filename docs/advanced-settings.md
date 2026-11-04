# Connection, troubleshooting and advanced settings

For the short installation and connection steps, see the [README](../README.md).
This guide covers packaging, connection status, cron, service configuration and
connection data on backups or cloned sites.

## Installation

### 1. Create the installable package

```sh
git clone git@github.com:LdesignMedia/totara-local_guardlms.git
cd totara-local_guardlms
./scripts/package.sh "$PWD/dist/guardlms-totara-0.1.0.zip"
```

The archive contains an installable **`guardlms/`** directory, the GPL license and
documentation. It excludes tests, CI files, development scripts and private sources.
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

## Connection details

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
