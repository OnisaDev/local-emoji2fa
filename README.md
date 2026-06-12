<!-- local-emoji2fa — README -->
<div align="center">

# ✦ local-emoji2fa ✦
### Plugin Moodle · 2FA por reconocimiento visual de emojis · PHP

![PHP](https://img.shields.io/badge/PHP-8.2-ff9f43?style=flat-square&logo=php&logoColor=0f0a0d)
![Moodle](https://img.shields.io/badge/Moodle-4.0+-ff6b9d?style=flat-square)
![MySQL](https://img.shields.io/badge/MariaDB-10.4-ffe066?style=flat-square&logo=mariadb&logoColor=0f0a0d)
![Version](https://img.shields.io/badge/versión-1.1-cc8870?style=flat-square)
![DAM](https://img.shields.io/badge/Prácticas-Datacontrol_Tecnología-f97316?style=flat-square)

</div>

---

## 📋 Descripción

Plugin de tipo `local` para Moodle que implementa un **segundo factor de autenticación (2FA) basado en reconocimiento visual de emojis**, sin modificar el core de Moodle ni el sistema de autenticación existente.

Tras el login con usuario y contraseña, el sistema presenta al usuario una cuadrícula de emojis mezclados y le pide que seleccione únicamente los que pertenecen a una categoría concreta. Solo si la selección es correcta se permite el acceso a la plataforma.

La versión 1.1 añade un **periodo de gracia configurable** — si el usuario vuelve a entrar antes de que expire el tiempo configurado, no se le vuelve a pedir la verificación.

---

## ✨ Funcionalidades

- 🔐 **2FA visual** — reto de emojis tras el login estándar de Moodle
- 🎯 **4 categorías** — Animales, Frutas, Deportes, Vehículos (10 emojis cada una)
- 🎲 **Reto aleatorio** — 3 emojis correctos + 5 distractores de otras categorías, mezclados
- ⏱️ **Periodo de gracia** — configurable via constante `EMOJI2FA_EXPIRY_SECONDS` (por defecto 1h)
- 🗄️ **Persistencia en BD** — tabla propia `mdl_local_emoji2fa_sessions` creada automáticamente
- 🔁 **Observer de login** — limpia el estado de sesión al hacer logout
- 🌍 **i18n** — cadenas en español e inglés
- 🛡️ **Sin tocar el core** — implementado mediante hook `after_config`

---

## 🛠️ Stack

![PHP](https://img.shields.io/badge/PHP_8.2-ff9f43?style=flat-square&logo=php&logoColor=0f0a0d)
![Moodle](https://img.shields.io/badge/Moodle_API-ff6b9d?style=flat-square)
![MariaDB](https://img.shields.io/badge/MariaDB-ffe066?style=flat-square&logo=mariadb&logoColor=0f0a0d)

---

## 📁 Estructura del plugin

```
moodle/local/emoji2fa/
├── version.php              # Metadatos: nombre, versión, compatibilidad Moodle
├── lib.php                  # Hook after_config — intercepta navegación y gestiona periodo de gracia
├── verify.php               # Página de verificación: genera reto, procesa respuesta, actualiza BD
├── db/
│   ├── install.xml          # Define mdl_local_emoji2fa_sessions (creación automática al instalar)
│   ├── access.php           # Permisos del plugin
│   └── events.php           # Registro del observer de login
├── classes/
│   └── observer.php         # Escucha user_loggedin para limpiar estado de sesión
└── lang/
    ├── es/local_emoji2fa.php
    └── en/local_emoji2fa.php
```

---

## 🗄️ Base de datos

El plugin crea automáticamente una tabla al instalarse:

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT (PK, AUTO) | Identificador único |
| `userid` | INT (UNIQUE) | ID del usuario Moodle |
| `last_verified` | INT | Timestamp Unix de la última verificación superada |

---

## 🔄 Flujo de funcionamiento

```
Login Moodle
     ↓
after_config() se ejecuta en cada página
     ↓
¿Usuario logueado? ¿No es invitado? ¿No es AJAX?
     ↓
¿Tiene registro en BD dentro del periodo de gracia?
    SÍ → sesión marcada como verificada → acceso directo
    NO → redirect a verify.php
          ↓
     Muestra reto de emojis
          ↓
    ¿Selección correcta?
        SÍ → guarda timestamp en BD → redirect a escritorio
        NO → nuevo reto + contador de intentos
```

---

## 🚀 Instalación

1. Copiar la carpeta `emoji2fa` en `moodle/local/`
2. Acceder al panel de administración de Moodle (`/admin`)
3. Moodle detecta el plugin y muestra la pantalla de actualización
4. Hacer clic en **Actualizar base de datos** → **Continuar**
5. La tabla `mdl_local_emoji2fa_sessions` se crea automáticamente

> Requiere Moodle 4.0+ y PHP 8.2

---

## ⚙️ Configuración del periodo de gracia

Editar la constante en `lib.php`:

```php
define('EMOJI2FA_EXPIRY_SECONDS', 3600); // 1 hora por defecto
```

| Valor | Tiempo |
|---|---|
| `60` | 1 minuto (demos) |
| `3600` | 1 hora ✓ |
| `86400` | 1 día |
| `604800` | 1 semana |

---

<div align="center">
<sub>Desarrollado durante prácticas FCT en Datacontrol Tecnología de la Información · CFGS DAM · CESUR Málaga ☕</sub>
</div>
