<?php

/*
|--------------------------------------------------------------------------
| LOGOUT ADMIN PORTAL LAYANAN SDM PUSRI
|--------------------------------------------------------------------------
|
| Portal utama tetap PUBLIC.
|
| File ini hanya menghapus login ADMIN:
|
| 1. Ambil ID Token Keycloak
| 2. Bersihkan session lokal
| 3. Hapus cookie session
| 4. Logout dari Keycloak
| 5. Kembali ke Portal publik
|
*/


/*
|--------------------------------------------------------------------------
| KEYCLOAK + SECURE SESSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/config/keycloak.php";


/*
|--------------------------------------------------------------------------
| URL TUJUAN SETELAH LOGOUT
|--------------------------------------------------------------------------
*/

$portalUrl =
    "http://localhost/portal-pusri/";


/*
|--------------------------------------------------------------------------
| AMBIL ID TOKEN
|--------------------------------------------------------------------------
|
| ID Token diperlukan Keycloak untuk proses RP-Initiated Logout.
|
*/

$idToken =
    $_SESSION["id_token"]
    ?? null;


if (
    !is_string($idToken)
    ||
    trim($idToken) === ""
) {

    $idToken = null;
}


/*
|--------------------------------------------------------------------------
| BERSIHKAN SEMUA DATA SESSION
|--------------------------------------------------------------------------
*/

$_SESSION = [];


/*
|--------------------------------------------------------------------------
| HAPUS COOKIE SESSION
|--------------------------------------------------------------------------
*/

if (
    ini_get("session.use_cookies")
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
                ?: "/",

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
| HAPUS SESSION SERVER
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
| LOGOUT KEYCLOAK
|--------------------------------------------------------------------------
|
| Jika ID Token tersedia, logout dilakukan juga pada Keycloak.
|
| Setelah berhasil, Keycloak mengembalikan browser ke Portal publik.
|
*/

if (
    $idToken !== null
) {

    try {

        $oidc->signOut(
            $idToken,
            $portalUrl
        );


        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        |
        | Normalnya signOut() melakukan redirect dan kode tidak lanjut ke sini.
        |
        */

        header(
            "Location: "
            . $portalUrl,
            true,
            302
        );

        exit;


    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | DETAIL ERROR HANYA KE SERVER LOG
        |--------------------------------------------------------------------------
        */

        error_log(
            "Portal Admin Logout Error: "
            . get_class($e)
            . ": "
            . $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| JIKA TIDAK ADA ID TOKEN / KEYCLOAK LOGOUT GAGAL
|--------------------------------------------------------------------------
|
| Session Portal sudah dibersihkan.
| Pengguna tetap dikembalikan ke halaman publik.
|
*/

header(
    "Location: "
    . $portalUrl,
    true,
    302
);

exit;