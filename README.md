# GuardLMS for Totara

[![Plugin checks](https://github.com/LdesignMedia/totara-local_guardlms/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/LdesignMedia/totara-local_guardlms/actions/workflows/ci.yml)

Connect your self-hosted Totara site to [GuardLMS](https://guardlms.com) and keep
its installed software inventory up to date in the dashboard.

The plugin reports Totara, installed plugins and the server environment once a
day. Connect through the administrator page without creating or copying an API key.

**Alpha 0.1.0:** real Totara runtime and browser E2E verification is still pending.
The intended Totara 16+ baseline has not been verified on a licensed installation.
See [compatibility and validation](docs/development.md) before evaluating it on a
disposable Totara site.

## Features

- Daily installed Totara and plugin inventory.
- PHP, database and selected server metadata.
- One-click connection with status, reconnect and disconnect controls.
- Payload preview and on-demand inventory reporting.
- Encrypted storage for the site-bound inventory key.

Update availability remains unknown. Totara-specific advisory matching,
configuration reviews and browser monitoring are outside this initial connector.

## Requirements

- Self-hosted Totara; intended baseline **16+**, with real-runtime validation pending.
- A PHP version supported by your Totara release. CI checks PHP 8.1–8.4 using API doubles.
- A GuardLMS account, outbound HTTPS access and working Totara cron.
- The [Totara receiver and consent support](https://github.com/LdesignMedia/guardlms/pull/2877)
  deployed in GuardLMS before connecting.
- Access to this private repository and the Totara server for installation.

This is a separate distribution with the same `local_guardlms` component name as
the Moodle plugin. Use the Totara package only; do not install both in one LMS.

## Install and connect

1. Build the installable ZIP using the [packaging guide](docs/advanced-settings.md#1-create-the-installable-package),
   or obtain the `guardlms-totara-alpha` artifact from a successful CI run.
2. Extract its `guardlms/` directory into `server/local/guardlms` in your Totara source.
3. Run Totara's normal upgrade as the web-service account.
4. Open **Site administration → Plugins → Local plugins → GuardLMS for Totara**.
5. Use **Preview payload**, then **Connect / reconnect to GuardLMS**. Sign in,
   review the site and confirm. Totara installs the key and sends the first inventory.

Inventory is sent daily by Totara cron. To send it now, use **Push inventory now**.
If the plugin reports a refused key, use **Connect / reconnect to GuardLMS** to
renew access. Check **Last accepted inventory** to confirm reporting is working.

Detailed installation, CLI commands and connection warnings are in the
[connection guide](docs/advanced-settings.md).

## Privacy

The inventory reports software and selected environment metadata. Learner and
course records, credentials, database connection details and plugin settings stay
in Totara. The canonical site URL is included to bind reports to the registered site.

See the [inventory and privacy reference](docs/inventory.md) for the full payload
scope and current limitations.

## Documentation

- [Connection, troubleshooting and advanced settings](docs/advanced-settings.md)
- [Inventory and privacy reference](docs/inventory.md)
- [Development, tests and compatibility](docs/development.md)

## Support and license

Report problems through [GitHub issues](https://github.com/LdesignMedia/totara-local_guardlms/issues).
Include the Totara/PHP versions and the warning shown; keep credentials out of reports.
Learn more at [guardlms.com](https://guardlms.com).

Licensed under [GNU GPL v3 or later](LICENSE).
