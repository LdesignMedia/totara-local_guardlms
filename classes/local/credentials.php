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
 * Encrypted, site-bound push credentials.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guardlms\local;

/**
 * Stores ciphertext in config_plugins and a separate key in protected dataroot.
 */
class credentials {
    /**
     * Local encryption key, never included in a payload.
     *
     * @return string Local encryption key, never included in a payload.
     */
    private static function key(): string {
        global $CFG;
        $directory = $CFG->dataroot . '/local_guardlms';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \moodle_exception('error:credentials', 'local_guardlms');
        }
        // Serialize key creation so concurrent first-time requests cannot read a partial key.
        $lock = fopen($directory . '/key.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            throw new \moodle_exception('error:credentials', 'local_guardlms');
        }
        try {
            $path = $directory . '/key';
            if (!file_exists($path)) {
                $key = random_bytes(32);
                if (file_put_contents($path, $key) !== 32 || !chmod($path, 0600)) {
                    throw new \moodle_exception('error:credentials', 'local_guardlms');
                }
            }
            $key = file_get_contents($path);
            if ($key === false || strlen($key) !== 32) {
                throw new \moodle_exception('error:credentials', 'local_guardlms');
            }
            return $key;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Encrypt the inventory token.
     *
     * @param string $token Site-bound inventory token.
     * @return void
     */
    public static function store(string $token): void {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $token,
            'aes-256-gcm',
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            config::siteurl() . "\0" . config::baseurl()
        );
        if ($ciphertext === false) {
            throw new \moodle_exception('error:credentials', 'local_guardlms');
        }
        set_config('credential', base64_encode($iv . $tag . $ciphertext), 'local_guardlms');
        set_config('credentialsite', config::siteurl(), 'local_guardlms');
        set_config('credentialservice', config::baseurl(), 'local_guardlms');
    }

    /**
     * Decrypted token, or empty when disconnected or cloned.
     *
     * @return string Decrypted token, or empty when disconnected or cloned.
     */
    public static function get(): string {
        $stored = (string)get_config('local_guardlms', 'credential');
        if ($stored === '') {
            return '';
        }
        if (
            get_config('local_guardlms', 'credentialsite') !== config::siteurl() ||
                get_config('local_guardlms', 'credentialservice') !== config::baseurl()
        ) {
            return '';
        }
        $bytes = base64_decode($stored, true);
        if ($bytes === false || strlen($bytes) < 29) {
            throw new \moodle_exception('error:credentials', 'local_guardlms');
        }
        $token = openssl_decrypt(
            substr($bytes, 28),
            'aes-256-gcm',
            self::key(),
            OPENSSL_RAW_DATA,
            substr($bytes, 0, 12),
            substr($bytes, 12, 16),
            config::siteurl() . "\0" . config::baseurl()
        );
        if ($token === false) {
            throw new \moodle_exception('error:credentials', 'local_guardlms');
        }
        return $token;
    }

    /**
     * Remove the stored credential and its site binding.
     *
     * @return void
     */
    public static function clear(): void {
        foreach (['credential', 'credentialsite', 'credentialservice'] as $name) {
            unset_config($name, 'local_guardlms');
        }
    }
}
