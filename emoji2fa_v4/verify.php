<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

if (!empty($SESSION->emoji2fa_verified)) {
    redirect(new moodle_url('/my'));
}

$categorias = [
    0 => ['nombre' => '🐾 Animales',  'emojis' => ['🐶','🐱','🐸','🐼','🐨','🦁','🐯','🦊','🐮','🐷']],
    1 => ['nombre' => '🍓 Frutas',    'emojis' => ['🍎','🍌','🍓','🍇','🍊','🍋','🍑','🍒','🥝','🍍']],
    2 => ['nombre' => '🏅 Deportes',  'emojis' => ['⚽','🏀','🎾','🏈','⚾','🏐','🏉','🎱','🏓','🏸']],
    3 => ['nombre' => '🚗 Vehículos', 'emojis' => ['🚗','🚕','🚌','🚎','🚑','🚒','🚓','🚜','🚀','🛸']],
];

if (empty($SESSION->emoji2fa_challenge)) {
    $cat_idx = array_rand($categorias);
    $cat = $categorias[$cat_idx];
    $indices_correctos = (array) array_rand($cat['emojis'], 3);
    $emojis_correctos = [];
    foreach ($indices_correctos as $i) {
        $emojis_correctos[] = $cat['emojis'][$i];
    }
    $distractores = [];
    foreach ($categorias as $k => $c) {
        if ($k !== $cat_idx) {
            $picks = (array) array_rand($c['emojis'], 2);
            foreach ($picks as $p) {
                $distractores[] = $c['emojis'][$p];
            }
        }
    }
    shuffle($distractores);
    $distractores = array_slice($distractores, 0, 5);
    $todos = array_values(array_merge($emojis_correctos, $distractores));
    shuffle($todos);
    $todos = array_values($todos);
    $indices_en_todos = [];
    foreach ($todos as $pos => $e) {
        if (in_array($e, $emojis_correctos)) {
            $indices_en_todos[] = $pos;
        }
    }
    sort($indices_en_todos);
    $SESSION->emoji2fa_challenge = [
        'cat_nombre'        => $cat['nombre'],
        'todos'             => $todos,
        'indices_correctos' => $indices_en_todos,
    ];
}

$challenge = $SESSION->emoji2fa_challenge;
$intentos  = isset($SESSION->emoji2fa_intentos) ? (int)$SESSION->emoji2fa_intentos : 0;
$error     = '';
$exito     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enviados = isset($_POST['emoji_idx']) ? $_POST['emoji_idx'] : [];
    $enviados = array_map('intval', $enviados);
    sort($enviados);
    $correctos = $challenge['indices_correctos'];
    sort($correctos);
    if ($enviados === $correctos) {
        $SESSION->emoji2fa_verified = true;
        unset($SESSION->emoji2fa_challenge);
        unset($SESSION->emoji2fa_intentos);

        // Guardar o actualizar el timestamp en la BD
        $registro = $DB->get_record('local_emoji2fa_sessions', ['userid' => $USER->id]);
        if ($registro) {
            $registro->last_verified = time();
            $DB->update_record('local_emoji2fa_sessions', $registro);
        } else {
            $DB->insert_record('local_emoji2fa_sessions', [
                'userid'        => $USER->id,
                'last_verified' => time(),
            ]);
        }

        $exito = true;
    } else {
        $intentos++;
        $SESSION->emoji2fa_intentos = $intentos;
        unset($SESSION->emoji2fa_challenge);
        $error = 'Seleccion incorrecta. Intentalo de nuevo.';
    }
}

$PAGE->set_url(new moodle_url('/local/emoji2fa/verify.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Verificacion por Emojis');
$PAGE->set_heading('Verificacion por Emojis');

echo $OUTPUT->header();

if ($exito) {
    echo $OUTPUT->notification('Verificacion superada! Redirigiendo...', 'success');
    echo '<script>setTimeout(function(){ window.location.href = "/moodle/public/my/"; }, 1500);</script>';
} else {
    if ($error) {
        echo $OUTPUT->notification($error, 'error');
    }
    echo '<div class="card mx-auto mt-4" style="max-width:580px;">';
    echo '<div class="card-body text-center p-4">';
    echo '<p class="lead mb-1">Selecciona unicamente los emojis de la categoria:</p>';
    echo '<p class="mb-4"><strong style="font-size:1.3em;color:#2E75B6;">' . $challenge['cat_nombre'] . '</strong></p>';
    echo '<form method="post" id="emojiForm">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    echo '<div class="d-flex flex-wrap justify-content-center mb-4" style="gap:10px;">';
    foreach ($challenge['todos'] as $pos => $emoji) {
        echo '<div class="emoji-item" data-pos="' . $pos . '" onclick="toggleEmoji(this)" ';
        echo 'style="font-size:2.4em;cursor:pointer;padding:10px;border-radius:10px;';
        echo 'border:2px solid #dee2e6;background:#f8f9fa;min-width:65px;text-align:center;transition:all 0.15s;">';
        echo $emoji;
        echo '<input type="checkbox" name="emoji_idx[]" value="' . $pos . '" class="d-none emoji-cb">';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary btn-lg px-5">Verificar</button>';
    echo '</form>';
    if ($intentos > 0) {
        echo '<p class="text-muted mt-3 small">Intentos fallidos: ' . $intentos . '</p>';
    }
    echo '</div></div>';
    echo '<script>
function toggleEmoji(div) {
    var cb = div.querySelector(".emoji-cb");
    cb.checked = !cb.checked;
    if (cb.checked) {
        div.style.background = "#d0e8ff";
        div.style.borderColor = "#2E75B6";
        div.style.transform = "scale(1.12)";
        div.style.boxShadow = "0 0 0 3px rgba(46,117,182,0.3)";
    } else {
        div.style.background = "#f8f9fa";
        div.style.borderColor = "#dee2e6";
        div.style.transform = "scale(1)";
        div.style.boxShadow = "none";
    }
}
</script>';
}

echo $OUTPUT->footer();
