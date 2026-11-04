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
 * Daily inventory task.
 *
 * @package    local_guardlms
 * @copyright  2026 Luuk Verhoeven, LdesignMedia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guardlms\task;

/**
 * Reports the complete installed inventory once per day.
 */
class push_inventory extends \core\task\scheduled_task {
    /**
     * Task name.
     *
     * @return string Task name.
     */
    public function get_name(): string {
        return get_string('task:push', 'local_guardlms');
    }

    /**
     * Send the daily inventory for a connected site.
     *
     * @return void
     */
    public function execute(): void {
        if (get_config('local_guardlms', 'enabled') && \local_guardlms\local\credentials::get() !== '') {
            \local_guardlms\local\connector::push();
            mtrace('GuardLMS Totara inventory accepted.');
        }
    }
}
