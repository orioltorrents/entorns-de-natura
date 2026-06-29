<?php
require '../includes/connexio.php';

try {
    // Comprovem si podem llegir usuaris
    $stmt = $conexio->query("SELECT COUNT(*) AS total FROM usuaris");
    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "✅ Connexió correcta. Total d'usuaris a la base de dades: " . $resultat['total'];
} catch (PDOException $e) {
    echo "❌ Error de consulta: " . $e->getMessage();
}