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
 * Funciones del plugin local_emoji2fa.
 * Implementa el hook after_config para interceptar la navegación
 * y redirigir al usuario a la verificación emoji si no la ha completado
 * o si ha caducado el periodo de gracia.
 *
 * @package   local_emoji2fa
 * @copyright 2026, Chapter Data
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tiempo en segundos que el usuario está exento de volver a verificar.
 * 3600 = 1 hora. Cambia este valor para ajustar el periodo.
 */
define('EMOJI2FA_EXPIRY_SECONDS', 3600);

/**
 * Hook que Moodle llama en cada página tras cargar la configuración.
 * Si el usuario está logueado y no ha pasado la verificación emoji
 * (o ha caducado), lo redirige a la página de verificación.
 */
function local_emoji2fa_after_config() {
    global $SESSION, $USER, $CFG, $DB;

    // Solo actuar si el usuario está logueado y no es invitado
    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Obtener la URL actual
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Evitar bucle de redirección
    if (strpos($current_url, '/local/emoji2fa/verify.php') !== false) {
        return;
    }

    // Evitar redirigir en peticiones AJAX o de logout
    if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
        return;
    }
    if (strpos($current_url, '/login/logout.php') !== false) {
        return;
    }

    // Si ya verificó en esta sesión, no hacer más consultas a BD
    if (!empty($SESSION->emoji2fa_verified)) {
        return;
    }

    // Consultar la BD: ¿tiene registro este usuario y está dentro del periodo?
    $registro = $DB->get_record('local_emoji2fa_sessions', ['userid' => $USER->id]);

    if ($registro) {
        $tiempo_transcurrido = time() - (int)$registro->last_verified;
        if ($tiempo_transcurrido < EMOJI2FA_EXPIRY_SECONDS) {
            // Todavía dentro del periodo, marcar la sesión para no repetir consultas
            $SESSION->emoji2fa_verified = true;
            return;
        }
    }

    // No tiene registro o ha caducado: redirigir a verificación
    redirect(new \moodle_url('/local/emoji2fa/verify.php'));
}
