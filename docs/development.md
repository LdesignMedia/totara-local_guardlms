# Development, tests and compatibility

The connector is alpha 0.1.0. Real Totara runtime/browser E2E verification is
pending; the standalone checks use explicit Totara API/transport doubles.

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

## Standalone checks

```sh
php tests/unit.php
phpcs --standard=moodle callback.php classes cli db index.php lang lib.php settings.php version.php
```

The regression checks explicitly double Totara APIs and HTTP transport. CI runs
those checks, PHP syntax validation and packaging on PHP 8.1–8.4. It does not run
a real Totara installation.

## Real Totara E2E — pending

No licensed Totara source or test instance was available for the initial
2026-11-04 verification. The prepared
[GuardLMS E2E harness](https://github.com/LdesignMedia/guardlms/blob/4d8cf0031d04e94a737c492e6d7087cdc023ca63/integrations/totara/README.md)
has not run against Totara. When a licensed runtime becomes available, follow that
guide with `TOTARA_PLUGIN_ROOT` pointing to this checkout and `TOTARA_SOURCE_ROOT`
pointing to the licensed source. The harness uses an isolated GuardLMS receiver;
its consent-code fixture is separate from the GuardLMS approval UI tests.

The receiver/frontend checks and harness source are pinned in
[GuardLMS integrations/totara](https://github.com/LdesignMedia/guardlms/tree/4d8cf0031d04e94a737c492e6d7087cdc023ca63/integrations/totara).

## Initial verification record — 2026-11-04

| Check | Recorded result |
| --- | --- |
| Backend receiver/connect/platform regressions | 159 tests / 651 assertions passed; 78 PHPUnit deprecations emitted |
| Standalone plugin checks | 38 passed, using Totara API/transport doubles |
| Focused frontend tests | 44 passed; TypeScript and focused ESLint passed |
| Moodle PHP coding standards | Passed without errors or warnings |
| PHP, shell, browser-script and Compose checks | Passed |
| Real Totara setup, HTTPS delivery and browser E2E | **Not run**; setup correctly stops without licensed Totara source |
