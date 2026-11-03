<?php

/*
|--------------------------------------------------------------------------
| SECURITY HEADERS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/security.php";


/*
|--------------------------------------------------------------------------
| SESSION SECURITY - PORTAL PUSRI
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    /*
    |--------------------------------------------------------------------------
    | DETEKSI HTTPS
    |--------------------------------------------------------------------------
    |
    | Localhost:
    | http://localhost  → false
    |
    | Production:
    | https://...       → true
    |
    */

    $isHttps =
        (
            !empty($_SERVER["HTTPS"])
            &&
            strtolower(
                (string) $_SERVER["HTTPS"]
            ) !== "off"
        )
        ||
        (
            isset($_SERVER["SERVER_PORT"])
            &&
            (int) $_SERVER["SERVER_PORT"] === 443
        );


    /*
    |--------------------------------------------------------------------------
    | PHP SESSION HARDENING
    |--------------------------------------------------------------------------
    */

    ini_set(
        "session.use_strict_mode",
        "1"
    );

    ini_set(
        "session.use_only_cookies",
        "1"
    );

    ini_set(
        "session.use_trans_sid",
        "0"
    );

    /*
    |--------------------------------------------------------------------------
    | COOKIE HANYA DAPAT DIAKSES MELALUI HTTP
    |--------------------------------------------------------------------------
    */

    ini_set(
        "session.cookie_httponly",
        "1"
    );


    /*
    |--------------------------------------------------------------------------
    | SESSION COOKIE
    |--------------------------------------------------------------------------
    */

    session_set_cookie_params([

        /*
        | Session berakhir ketika browser ditutup.
        */
        "lifetime" => 0,

        /*
        | Berlaku untuk seluruh localhost.
        |
        | Dipertahankan supaya login Keycloak yang sudah berjalan
        | tidak terganggu.
        */
        "path" => "/",

        "domain" => "",

        /*
        | HTTP localhost:
        | false
        |
        | HTTPS production:
        | true
        */
        "secure" => $isHttps,

        /*
        | Cookie tidak dapat dibaca JavaScript.
        */
        "httponly" => true,

        /*
        | Kompatibel dengan redirect login Keycloak.
        */
        "samesite" => "Lax"

    ]);


    /*
    |--------------------------------------------------------------------------
    | START SESSION
    |--------------------------------------------------------------------------
    */

    session_start();


    /*
    |--------------------------------------------------------------------------
    | SESSION ACTIVITY
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk regenerasi session ID secara berkala.
    |
    */

    $now =
        time();


    /*
    |--------------------------------------------------------------------------
    | REGENERATE SESSION ID SETIAP 30 MENIT
    |--------------------------------------------------------------------------
    |
    | Callback login juga tetap melakukan session_regenerate_id(true).
    |
    */

    if (
        !isset(
            $_SESSION["_session_regenerated_at"]
        )
    ) {

        $_SESSION["_session_regenerated_at"] =
            $now;

    } elseif (
        (
            $now
            -
            (int) $_SESSION["_session_regenerated_at"]
        )
        >= 1800
    ) {

        session_regenerate_id(
            true
        );

        $_SESSION["_session_regenerated_at"] =
            $now;
    }
}