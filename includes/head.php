<!-- includes/head.php -->
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Títol dinàmic amb valor per defecte -->
    <title><?php echo isset($titol) ? $titol : "Entorns de Natura"; ?></title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/favicon/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/assets/favicon/android-chrome-512x512.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/assets/favicon/favicon-96x96.png">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg">
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/assets/favicon/site.webmanifest">

    <meta name="msapplication-TileColor" content="#3b6e3b">
    <meta name="theme-color" content="#3b6e3b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">


    <!-- Base per a rutes relatives (opcional) -->
    <base href="">

    <!-- Estils CSS comuns -->
    <link rel="stylesheet" href="/assets/css/styles.css">

    <!-- Open Graph (per compartir bé a xarxes socials) -->
    <meta property="og:title" content="<?php echo isset($titol) ? $titol : "Web educativa"; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://entorns-de-natura.cat<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="https://entorns-de-natura.cat/assets/images/social.jpg">
    <meta property="og:description" content="Projectes educatius de ciència ciutadana i natura a l'ESO.">

    <!-- JavaScript comú (amb defer per no bloquejar el renderitzat) -->
    <script src="/assets/js/scripts.js" defer></script>
</head>
