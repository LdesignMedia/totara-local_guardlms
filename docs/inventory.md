# Inventory and privacy reference

See the [README](../README.md) for the connector overview and
[connection guide](advanced-settings.md) for setup and reporting controls.

## Reported inventory

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
