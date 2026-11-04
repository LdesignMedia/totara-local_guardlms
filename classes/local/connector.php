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
 * Connection lifecycle and inventory delivery.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guardlms\local;

/**
 * Shared implementation for the settings page and scheduled task.
 */
class connector {
    /**
     * Consent URL with a session-bound state.
     *
     * @return \moodle_url Consent URL with a session-bound state.
     */
    public static function start(): \moodle_url {
        global $SESSION, $USER;
        $state = bin2hex(random_bytes(32));
        $SESSION->local_guardlms_connect = ['state' => $state, 'expires' => time() + 900, 'userid' => $USER->id];
        return new \moodle_url(config::baseurl() . '/connect/totara', [
            'siteurl' => config::siteurl(), 'state' => $state,
            'callback' => config::siteurl() . '/local/guardlms/callback.php',
        ]);
    }

    /**
     * Complete the single-use connection exchange.
     *
     * @param string $code One-time code.
     * @param string $state Session state.
     * @return void
     */
    public static function complete(string $code, string $state): void {
        global $SESSION, $USER;
        $pending = $SESSION->local_guardlms_connect ?? [];
        unset($SESSION->local_guardlms_connect);
        if (
            empty($pending['state']) || !hash_equals($pending['state'], $state) ||
                $pending['expires'] < time() || $pending['userid'] !== $USER->id
        ) {
            throw new \moodle_exception('error:state', 'local_guardlms');
        }
        $data = client::post('/api/integrations/exchange', [
            'code' => $code, 'state' => $state, 'siteurl' => config::siteurl(),
        ]);
        if (empty($data['token']) || !is_string($data['token'])) {
            throw new \moodle_exception('error:exchange', 'local_guardlms');
        }
        credentials::store($data['token']);
        set_config('enabled', 1, 'local_guardlms');
        set_config('connectedat', time(), 'local_guardlms');
        set_config('expiresat', (string)($data['expires_at'] ?? ''), 'local_guardlms');
        set_config('verificationtoken', (string)($data['verification_token'] ?? ''), 'local_guardlms');
        unset_config('authrejected', 'local_guardlms');
        // Connection survives an initial transport failure; the scheduled task retries.
        try {
            self::push();
        } catch (\Throwable $e) {
            set_config('lastpushfailed', time(), 'local_guardlms');
        }
    }

    /**
     * Accepted inventory response.
     *
     * @return array Accepted inventory response.
     */
    public static function push(): array {
        if (!get_config('local_guardlms', 'enabled') || credentials::get() === '') {
            throw new \moodle_exception('error:disconnected', 'local_guardlms');
        }
        try {
            $data = client::post('/api/externalpush/totara', collector::build_payload(), credentials::get());
            if (!empty($data['stale'])) {
                throw new \moodle_exception('error:stale', 'local_guardlms');
            }
            set_config('lastpush', time(), 'local_guardlms');
            unset_config('lastpushfailed', 'local_guardlms');
            unset_config('authrejected', 'local_guardlms');
            return $data;
        } catch (\Throwable $e) {
            set_config('lastpushfailed', time(), 'local_guardlms');
            throw $e;
        }
    }

    /**
     * Local teardown; reconnect rotates the server-side key.
     *
     * @return void Local teardown; reconnect rotates the server-side key.
     */
    public static function disconnect(): void {
        global $SESSION;
        credentials::clear();
        unset($SESSION->local_guardlms_connect);
        set_config('enabled', 0, 'local_guardlms');
        foreach (['connectedat', 'expiresat', 'verificationtoken', 'authrejected', 'lastpush', 'lastpushfailed'] as $name) {
            unset_config($name, 'local_guardlms');
        }
    }
}
