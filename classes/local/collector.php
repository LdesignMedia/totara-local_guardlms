<?php
// This file is part of GuardLMS for Totara.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Totara installed-version and runtime inventory.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guardlms\local;

/**
 * Collects allowlisted metadata, without Moodle update-service requests.
 */
class collector {
    /**
     * Complete inventory; throws instead of publishing a partial list.
     *
     * @return array Complete inventory; throws instead of publishing a partial list.
     */
    public static function build_payload(): array {
        global $CFG, $DB;
        // Installed Totara metadata is distinct from the inherited Moodle release.
        $release = (string)($CFG->totara_release ?? '');
        $version = (string)($CFG->totara_version ?? '');
        if ($release === '' || $version === '') {
            throw new \moodle_exception('error:nottotara', 'local_guardlms');
        }
        $plugins = [];
        foreach (\core_plugin_manager::instance()->get_plugins() as $items) {
            foreach ($items as $info) {
                // Disk-only plugins are not installed; never substitute versiondisk.
                if (empty($info->versiondb)) {
                    continue;
                }
                $enabled = $info->is_enabled();
                $plugins[] = [
                    'id' => $info->component,
                    'version' => (string)$info->versiondb,
                    // Null means the plugin type has no enable/disable control.
                    'enabled' => $enabled === null ? null : (bool)$enabled,
                ];
            }
        }
        // A running installed connector must occur in its own registry snapshot.
        if (count($plugins) > 2000 || !in_array('local_guardlms', array_column($plugins, 'id'), true)) {
            throw new \moodle_exception('error:inventory', 'local_guardlms');
        }
        $server = ['os_family' => PHP_OS_FAMILY, 'arch' => php_uname('m')];
        if (!empty($_SERVER['SERVER_SOFTWARE'])) {
            $server['webserver'] = substr($_SERVER['SERVER_SOFTWARE'], 0, 255);
        }
        $payload = [
            'siteurl' => config::siteurl(),
            'generatedtime' => time(),
            'totara' => ['release' => $release, 'version' => $version, 'plugins' => $plugins],
            'php' => ['version' => PHP_VERSION],
            'server' => $server,
        ];
        $types = ['postgres' => 'PostgreSQL', 'mysql' => 'MySQL', 'mssql' => 'MSSQL', 'oracle' => 'Oracle'];
        $type = $types[$DB->get_dbfamily()] ?? null;
        $info = $DB->get_server_info();
        if ($type !== null && !empty($info['version'])) {
            $dbversion = (string)$info['version'];
            if ($type === 'MySQL' && stripos($dbversion, 'mariadb') !== false) {
                $type = 'MariaDB';
            }
            $payload['database'] = ['type' => $type, 'version' => substr($dbversion, 0, 100)];
        }
        return $payload;
    }
}
