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
 * TLS-verified GuardLMS HTTP transport.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guardlms\local;

/**
 * Sends only to the configured service and refuses HTTP redirects.
 */
class client {
    /**
     * Send a JSON request to GuardLMS.
     *
     * @param string $path Fixed endpoint.
     * @param array $payload JSON data.
     * @param string $token Optional token.
     * @return array Response data.
     */
    public static function post(string $path, array $payload, string $token = ''): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl(['ignoresecurity' => true]);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $curl->setHeader($headers);
        $body = $curl->post(config::baseurl() . $path, json_encode($payload, JSON_THROW_ON_ERROR), [
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_FOLLOWLOCATION' => false,
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 2,
            'CURLOPT_PROTOCOLS' => CURLPROTO_HTTPS,
        ]);
        $status = (int)($curl->get_info()['http_code'] ?? 0);
        if ($curl->get_errno()) {
            throw new \moodle_exception('error:transport', 'local_guardlms');
        }
        if ($token !== '' && in_array($status, [401, 403], true)) {
            set_config('authrejected', 1, 'local_guardlms');
        }
        $decoded = json_decode((string)$body, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded) || !is_array($decoded['data'] ?? null)) {
            // Never copy remote response bodies, credentials or private URLs into logs.
            throw new \moodle_exception('error:http', 'local_guardlms', '', $status);
        }
        return $decoded['data'];
    }
}
