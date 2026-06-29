<?php
// Iniciem la sessió per poder guardar l'idioma
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Mirem si s'ha indicat un idioma a la URL (?lang=ca)
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang; // El guardem a la sessió
}
// 2. Si no hi ha idioma a la URL, però ja hi ha un a la sessió, fem servir aquest
elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}
// 3. Si no hi ha res ni a la URL ni a la sessió, posem per defecte català
else {
    $lang = 'ca';
    $_SESSION['lang'] = 'ca';
}

// 4. Diccionari de traduccions
$translations = [
    'ca' => [
        'titol' => 'Benvinguts a Entorns de Natura',
        'inici_sessio' => 'Inicia sessió',
        'continua_sessio' => 'Continua sense iniciar sessió',
        'titol_login' => 'Iniciar sessió',
        'usuari' => 'Usuari',
        'contrasenya' => 'Contrasenya',
        'entrar' => 'Entrar',
        'error_credencials' => 'Credencials incorrectes',
        'error_connexio' => 'Error de connexió a la base de dades',
        'projecte_rius' => 'Projecte Rius',
        'mat' => 'MAT Penedès',
        'agroparc' => 'Agroparc',
        'orenetes' => 'Orenetes',
        'liquencity' => 'Liquencity',
        'vespa' => 'Vespa velutina',
        'qui_som' => 'Qui som',
    ],
    'es' => [
        'titol' => 'Bienvenidos a Entorns de Natura',
        'inici_sessio' => 'Iniciar sesión',
        'continua_sessio' => 'Continua sin logear',
        'titol_login' => 'Iniciar sesión',
        'usuari' => 'Usuario',
        'contrasenya' => 'Contraseña',
        'entrar' => 'Entrar',
        'error_credencials' => 'Credenciales incorrectas',
        'error_connexio' => 'Error de connexión a la base de datos',
        'projecte_rius' => 'Proyecto Ríos',
        'mat' => 'MAT Penedès',
        'agroparc' => 'Agroparc',
        'orenetes' => 'Golondrinas',
        'liquencity' => 'Liquencity',
        'vespa' => 'Avispa asiática',
        'qui_som' => 'Quiénes somos',
    ],
    'en' => [
        'titol' => 'Welcome to Entorns de Natura',
        'inici_sessio' => 'Log in',
        'continua_sessio' => 'Continue without log in',
        'titol_login' => 'Log in',
        'usuari' => 'User',
        'contrasenya' => 'Password',
        'entrar' => 'Enter',
        'error_credencials' => 'incorrect credentials',
        'error_connexio' => 'Connection Error to the database',
        'projecte_rius' => 'Rivers Project',
        'mat' => 'MAT Penedès',
        'agroparc' => 'Agropark',
        'orenetes' => 'Swallows',
        'liquencity' => 'Liquencity',
        'vespa' => 'Asian hornet',
        'qui_som' => 'About us',
    ]
];

// 5. Triem el conjunt de traduccions segons l'idioma seleccionat
$t = $translations[$lang] ?? $translations['ca'];
