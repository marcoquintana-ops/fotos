<?php
// helpers/crypto.php
// Funciones para encriptar / desencriptar IDs para URLs usando AES-256-CBC

if (!function_exists('encrypt_id')) {

    // En producción, lee estas claves desde variables de entorno.
    define('SECRET_KEY', 'vyr_change_this_to_a_very_long_secure_key_32bytes');
    define('SECRET_IV_SEED', 'vyr_iv_seed_change_me');

    function get_crypto_key_iv() {
        // Clave de 32 bytes
        $key = hash('sha256', SECRET_KEY, true);
        // IV de 16 bytes
        $iv_full = hash('sha256', SECRET_IV_SEED, true);
        $iv = substr($iv_full, 0, 16);
        return [$key, $iv];
    }

    function encrypt_id($id) {
        list($key, $iv) = get_crypto_key_iv();
        $cipher = 'AES-256-CBC';

        $encrypted = openssl_encrypt((string)$id, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            return false;
        }

        // Base64 URL-safe (sin =, +, /)
        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    function decrypt_id($token) {
        if (!$token) return false;

        // restaurar padding base64
        $b64 = strtr($token, '-_', '+/');
        $mod4 = strlen($b64) % 4;
        if ($mod4) {
            $b64 .= str_repeat('=', 4 - $mod4);
        }

        $encrypted = base64_decode($b64);
        if ($encrypted === false) return false;

        list($key, $iv) = get_crypto_key_iv();
        $cipher = 'AES-256-CBC';

        $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            return false;
        }

        return $decrypted;
    }

}
