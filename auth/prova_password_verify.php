<?php
$contrasenya = 'canvia123';
$hash = '$2y$10$NHhjD9gTu8DxPuDKvp8USuE8KlJSCRibDPA4Ri7F1egkf.aVEmeSu';

if (password_verify($contrasenya, $hash)) {
    echo "✅ Coincideix!";
} else {
    echo "❌ No coincideix!";
}
