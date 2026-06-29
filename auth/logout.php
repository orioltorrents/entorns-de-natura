<?php
session_start();

// Guardem la llengua abans de destruir la sessió
$lang = $_SESSION['lang'] ?? 'ca';

session_unset();      // Elimina totes les variables de sessió
session_destroy();    // Destrueix la sessió actual

// 🔁 Opcional: eliminar la cookie de sessió
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 🌐 Redirigeix a index.php amb la llengua
header("Location: /index.php?lang=" . $lang);
exit();

