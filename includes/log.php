<?php
function registraLog($conexio, $usuari, $accio) {
    $sql = "INSERT INTO logs 
            (usuari_id, email, nom_usuari, rol, accio, data_hora, ip, navegador)
            VALUES (:id, :email, :nom, :rol, :accio, NOW(), :ip, :navegador)";
    
    $stmt = $conexio->prepare($sql);
    $stmt->execute([
        ':id'        => $usuari['id'],
        ':email'     => $usuari['email'],
        ':nom'       => $usuari['nom_usuari'],
        ':rol'       => $usuari['rol'],
        ':accio'     => $accio,
        ':ip'        => $_SERVER['REMOTE_ADDR'] ?? 'desconeguda',
        ':navegador' => $_SERVER['HTTP_USER_AGENT'] ?? 'desconegut'
    ]);
}