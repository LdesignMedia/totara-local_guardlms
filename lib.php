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
 * Site ownership verification meta tag.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Verification tag; no token is emitted for cloned or disconnected sites.
 *
 * @return string Verification tag; no token is emitted for cloned or disconnected sites.
 */
function local_guardlms_before_standard_html_head(): string {
    if (!get_config('local_guardlms', 'enabled')) {
        return '';
    }
    try {
        if (\local_guardlms\local\credentials::get() === '') {
            return '';
        }
    } catch (\Throwable $e) {
        return '';
    }
    $token = (string)get_config('local_guardlms', 'verificationtoken');
    return $token === '' ? '' : '<meta name="guardlms-verification" content="' . s($token) . '">';
}
