<?php

/*
|--------------------------------------------------------------------------
| SESSION SECURITY PORTAL
|--------------------------------------------------------------------------
|
| File ini HANYA menghapus session PHP Portal.
| Tidak melakukan logout Keycloak.
|
*/

require_once __DIR__ . "/config/session.php";


/*
|--------------------------------------------------------------------------
| HAPUS DATA SESSION
|--------------------------------------------------------------------------
*/

$_SESSION = [];


/*
|--------------------------------------------------------------------------
| HAPUS COOKIE SESSION
|--------------------------------------------------------------------------
*/

if (
    (bool) ini_get(
        "session.use_cookies"
    )
) {

    $params =
        session_get_cookie_params();


    setcookie(
        session_name(),
        "",
        [
            "expires" =>
                time() - 42000,

            "path" =>
                $params["path"]
                ?? "/",

            "domain" =>
                $params["domain"]
                ?? "",

            "secure" =>
                (bool) (
                    $params["secure"]
                    ?? false
                ),

            "httponly" =>
                (bool) (
                    $params["httponly"]
                    ?? true
                ),

            "samesite" =>
                $params["samesite"]
                ?? "Lax"
        ]
    );
}


/*
|--------------------------------------------------------------------------
| DESTROY SESSION
|--------------------------------------------------------------------------
*/

if (
    session_status()
    === PHP_SESSION_ACTIVE
) {

    session_destroy();
}


/*
|--------------------------------------------------------------------------
| KEMBALI KE LOGIN
|--------------------------------------------------------------------------
*/

header(
    "Location: /portal-pusri/login.php",
    true,
    302
);

exit;