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
 * Canonical site and HTTPS service URLs.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guardlms\local;

/**
 * Configuration shared by web and cron entry points.
 */
class config {
    /**
     * Canonical site URL.
     *
     * @return string Canonical site URL.
     */
    public static function siteurl(): string {
        global $CFG;
        return rtrim($CFG->wwwroot, '/');
    }

    /**
     * Validated HTTPS service origin with optional base path.
     *
     * @return string Validated HTTPS service origin with optional base path.
     */
    public static function baseurl(): string {
        $url = rtrim(trim((string)get_config('local_guardlms', 'baseurl')), '/');
        if ($url === '') {
            $url = 'https://dashboard.guardlms.com';
        }
        $parts = parse_url($url);
        if (
            !$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) ||
                isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
        ) {
            throw new \moodle_exception('error:baseurl', 'local_guardlms');
        }
        return $url;
    }
}
