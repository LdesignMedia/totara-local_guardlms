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
 * English strings.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'GuardLMS for Totara';
$string['description'] = 'Connect this Totara site to report installed components and runtime versions daily. No learner or course records are collected. Preview the payload below.';
$string['connected'] = 'Connected to GuardLMS';
$string['disconnected'] = 'Disconnected. Connect this site to GuardLMS.';
$string['reconnectneeded'] = 'GuardLMS refused the stored key. Reconnect to renew access.';
$string['pushfailed'] = 'The last inventory push failed. Check the connection and scheduled task log, then retry.';
$string['pushsuccess'] = 'Inventory accepted by GuardLMS.';
$string['lastpush'] = 'Last accepted inventory';
$string['expiresat'] = 'Key expires';
$string['action:connect'] = 'Connect / reconnect to GuardLMS';
$string['action:disconnect'] = 'Disconnect';
$string['action:push'] = 'Push inventory now';
$string['action:preview'] = 'Preview payload';
$string['task:push'] = 'Push Totara inventory to GuardLMS';
$string['error:baseurl'] = 'The GuardLMS service URL must use HTTPS and contain no credentials, query or fragment.';
$string['error:credentials'] = 'Cannot read or write the protected GuardLMS credential. Check dataroot permissions and restore its encryption key if needed.';
$string['error:transport'] = 'Cannot reach GuardLMS securely. Check network access and the trusted TLS certificate chain.';
$string['error:http'] = 'GuardLMS rejected the request (HTTP {$a}). Check the canonical site URL and reconnect if needed.';
$string['error:state'] = 'The connection state is invalid, expired or already used. Start a new connection.';
$string['error:exchange'] = 'GuardLMS did not return a valid push credential.';
$string['error:disconnected'] = 'Connect this site to GuardLMS before sending inventory.';
$string['error:nottotara'] = 'Installed Totara release metadata is missing. This plugin requires Totara.';
$string['error:inventory'] = 'The installed plugin inventory is incomplete or exceeds the supported limit. No partial inventory was sent.';
$string['error:post'] = 'This action requires a POST request.';
$string['error:stale'] = 'GuardLMS already has a newer inventory. Check this server clock.';
$string['privacy:metadata'] = 'The plugin stores site connection settings and reports installed software metadata. It does not store or send personal data.';
