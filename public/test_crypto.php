<?php
echo "<pre>TEST DE CARGA DE CRYPTO\n\n";

// Ruta real según Eventos.php
$path = dirname(__DIR__) . "/helpers/crypto.php";
echo "Ruta esperada: $path\n";

if (file_exists($path)) {
    echo "✔️ El archivo EXISTE\n";
} else {
    echo "❌ El archivo NO existe\n";
}

echo "\nIncluyendo crypto.php...\n";

require_once $path;

if (function_exists('encrypt_id')) {
    echo "✔️ encrypt_id() cargada correctamente\n";

    $t = encrypt_id(15);
    echo "Token: $t\n";
    echo "Decrypted: " . decrypt_id($t) . "\n";
} else {
    echo "❌ encrypt_id() NO está definida\n";
}

echo "</pre>";
