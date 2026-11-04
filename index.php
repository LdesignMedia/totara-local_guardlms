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
 * Administrator connection, preview and reporting controls.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_login();
require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('local_guardlms');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$action = optional_param('action', '', PARAM_ALPHA);
$error = '';
$notice = '';
if ($action !== '') {
    require_sesskey();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new moodle_exception('error:post', 'local_guardlms');
    }
    try {
        if ($action === 'connect') {
            redirect(\local_guardlms\local\connector::start());
        } else if ($action === 'disconnect') {
            \local_guardlms\local\connector::disconnect();
            redirect(new moodle_url('/local/guardlms/index.php'));
        } else if ($action === 'push') {
            \local_guardlms\local\connector::push();
            $notice = get_string('pushsuccess', 'local_guardlms');
        }
    } catch (moodle_exception $e) {
        $error = $e->getMessage();
    }
}
$connected = false;
try {
    $connected = \local_guardlms\local\credentials::get() !== '';
} catch (moodle_exception $e) {
    $error = $e->getMessage();
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_guardlms'));
echo html_writer::tag('p', get_string('description', 'local_guardlms'));
if ($error !== '') {
    echo $OUTPUT->notification($error, 'notifyproblem');
}
if ($notice !== '') {
    echo $OUTPUT->notification($notice, 'notifysuccess');
}
echo html_writer::tag('p', get_string($connected ? 'connected' : 'disconnected', 'local_guardlms'));
if (get_config('local_guardlms', 'authrejected')) {
    echo $OUTPUT->notification(get_string('reconnectneeded', 'local_guardlms'), 'notifyproblem');
}
if (get_config('local_guardlms', 'lastpushfailed')) {
    echo $OUTPUT->notification(get_string('pushfailed', 'local_guardlms'), 'notifyproblem');
}
foreach (['lastpush', 'expiresat'] as $name) {
    $value = get_config('local_guardlms', $name);
    if ($value) {
        $display = $name === 'lastpush' ? userdate((int)$value) : s($value);
        echo html_writer::tag('p', get_string($name, 'local_guardlms') . ': ' . $display);
    }
}
$actions = $connected ? ['connect', 'push', 'disconnect'] : ['connect'];
foreach ($actions as $name) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/guardlms/index.php', ['action' => $name, 'sesskey' => sesskey()]),
        get_string('action:' . $name, 'local_guardlms'),
        'post'
    );
}
echo $OUTPUT->single_button(
    new moodle_url('/local/guardlms/index.php', ['action' => 'preview', 'sesskey' => sesskey()]),
    get_string('action:preview', 'local_guardlms'),
    'post'
);
if ($action === 'preview') {
    try {
        echo html_writer::tag('pre', s(json_encode(\local_guardlms\local\collector::build_payload(), JSON_PRETTY_PRINT)));
    } catch (moodle_exception $e) {
        echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
    }
}
echo $OUTPUT->footer();
