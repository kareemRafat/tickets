<?php

define('REMEMBER_COOKIE_NAME', 'remember_me_token');
define('REMEMBER_COOKIE_LIFETIME', 30 * 24 * 60 * 60);

function create_remember_token($userId, $role, $expiryDays = 30) {
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + ($expiryDays * 24 * 60 * 60));

    $db = getDBConnection();

    $clean = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid AND role = :role");
    $clean->execute(['uid' => $userId, 'role' => $role]);

    $insert = $db->prepare("INSERT INTO remember_tokens (user_id, role, token_hash, expires_at) VALUES (:uid, :role, :hash, :expires)");
    $insert->execute([
        'uid'     => $userId,
        'role'    => $role,
        'hash'    => $hash,
        'expires' => $expiresAt,
    ]);

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    setcookie(REMEMBER_COOKIE_NAME, $token, [
        'expires'  => time() + REMEMBER_COOKIE_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function validate_remember_token() {
    if (empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return null;
    }

    $token = $_COOKIE[REMEMBER_COOKIE_NAME];
    $hash = hash('sha256', $token);

    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM remember_tokens WHERE token_hash = :hash AND expires_at > NOW() LIMIT 1");
    $stmt->execute(['hash' => $hash]);
    $record = $stmt->fetch();

    if ($record) {
        return $record;
    }

    clear_remember_cookie();
    return null;
}

function clear_remember_token($userId, $role) {
    if ($userId && $role) {
        $db = getDBConnection();
        $delete = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid AND role = :role");
        $delete->execute(['uid' => $userId, 'role' => $role]);
    }

    clear_remember_cookie();
}

function clear_remember_cookie() {
    if (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
        setcookie(REMEMBER_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[REMEMBER_COOKIE_NAME]);
    }
}

function clean_expired_remember_tokens() {
    $db = getDBConnection();
    $db->exec("DELETE FROM remember_tokens WHERE expires_at < NOW()");
}
