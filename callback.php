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
 * Authenticated consent callback.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$code = required_param('code', PARAM_ALPHANUMEXT);
$state = required_param('state', PARAM_ALPHANUMEXT);
$returnurl = new moodle_url('/local/guardlms/index.php');
try {
    \local_guardlms\local\connector::complete($code, $state);
} catch (moodle_exception $e) {
    redirect($returnurl, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
}
redirect($returnurl);
