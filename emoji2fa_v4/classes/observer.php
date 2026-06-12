<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Observer del evento de login para local_emoji2fa.
 *
 * @package   local_emoji2fa
 * @copyright 2026, Chapter Data
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_emoji2fa;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Se ejecuta cuando un usuario hace login.
     * Marca en la sesión que debe pasar la verificación emoji.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $SESSION;
        // Marcar que el usuario necesita verificación emoji
        $SESSION->emoji2fa_verified = false;
    }
}
