<?php

/*
|--------------------------------------------------------------------------
| LOGIN ADMIN
|--------------------------------------------------------------------------
|
| Halaman Portal utama bersifat PUBLIC.
|
| File ini hanya digunakan ketika seseorang menekan tombol:
|
| Login Admin
|
| Hanya user dengan Realm Role ADMIN yang nantinya boleh
| masuk ke Dashboard Admin.
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
| CEK APAKAH SUDAH LOGIN SEBAGAI ADMIN
|--------------------------------------------------------------------------
*/

$currentUser =
    $_SESSION["user"]
    ?? null;


$currentRoles =
    [];


if (
    is_array($currentUser)
    &&
    isset($currentUser["roles"])
    &&
    is_array($currentUser["roles"])
) {

    $currentRoles =
        array_map(
            static function ($role) {

                return strtoupper(
                    trim(
                        (string) $role
                    )
                );
            },
            $currentUser["roles"]
        );
}


if (
    !empty(
        $_SESSION["logged_in"]
    )
    &&
    in_array(
        "ADMIN",
        $currentRoles,
        true
    )
) {

    /*
    |--------------------------------------------------------------------------
    | ADMIN SUDAH LOGIN
    |--------------------------------------------------------------------------
    */

    header(
        "Location: /portal-pusri/admin/",
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| BERSIHKAN SESSION AUTH LAMA
|--------------------------------------------------------------------------
|
| Misalnya sebelumnya masih ada data autentikasi lama.
|
| Jangan session_destroy(), karena session yang sama akan dipakai
| library OIDC untuk menyimpan state proses autentikasi.
|
*/

unset(
    $_SESSION["user"],
    $_SESSION["logged_in"],
    $_SESSION["id_token"],
    $_SESSION["csrf_token"]
);


/*
|--------------------------------------------------------------------------
| LOGIN KEYCLOAK
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | PAKSA TAMPILKAN FORM LOGIN
    |--------------------------------------------------------------------------
    |
    | Ini penting karena login ini khusus ADMIN.
    |
    | Kalau browser masih mempunyai session Keycloak akun lain,
    | form login tetap ditampilkan agar administrator memilih akun yang benar.
    |
    */

    $oidc->addAuthParam(
        [
            "prompt" => "login"
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | MULAI AUTENTIKASI
    |--------------------------------------------------------------------------
    |
    | Redirect URL client tetap:
    |
    | http://localhost/portal-pusri/callback.php
    |
    */

    $oidc->authenticate();


    /*
    |--------------------------------------------------------------------------
    | FALLBACK
    |--------------------------------------------------------------------------
    |
    | Pada kondisi normal kode tidak akan mencapai bagian ini
    | sebelum proses redirect selesai.
    |
    */

    header(
        "Location: /portal-pusri/admin/",
        true,
        302
    );

    exit;


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DETAIL ERROR HANYA KE LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        "Portal Admin Login Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | BERSIHKAN AUTH LOKAL
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION["user"],
        $_SESSION["logged_in"],
        $_SESSION["id_token"],
        $_SESSION["csrf_token"]
    );


    http_response_code(
        500
    );

    ?>

    <!DOCTYPE html>

    <html lang="id">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            Login Admin - Portal Layanan SDM PUSRI
        </title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                margin: 0;

                display: flex;
                align-items: center;
                justify-content: center;

                padding: 24px;

                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;

                background: #f4f7fb;
                color: #172033;
            }

            .card {
                width: 100%;
                max-width: 480px;

                padding: 35px;

                background: #ffffff;

                border:
                    1px solid
                    #e3e8ef;

                border-radius: 16px;

                box-shadow:
                    0 16px 40px
                    rgba(15, 23, 42, 0.08);

                text-align: center;
            }

            h1 {
                margin:
                    0
                    0
                    12px;

                font-size: 25px;

                color: #17365d;
            }

            p {
                margin:
                    0
                    0
                    25px;

                color: #64748b;

                line-height: 1.6;
            }

            .actions {
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }

            a {
                display: inline-flex;
                align-items: center;
                justify-content: center;

                min-height: 42px;

                padding:
                    0
                    18px;

                border-radius: 8px;

                text-decoration: none;

                font-size: 13px;
                font-weight: 700;
            }

            .primary {
                background: #17365d;
                color: #ffffff;
            }

            .secondary {
                border:
                    1px solid
                    #d7dee7;

                color: #17365d;
                background: #ffffff;
            }

        </style>

    </head>

    <body>

        <div class="card">

            <h1>
                Login Admin Tidak Berhasil
            </h1>

            <p>
                Proses autentikasi administrator tidak dapat
                diselesaikan. Silakan coba kembali.
            </p>

            <div class="actions">

                <a
                    href="/portal-pusri/login.php"
                    class="primary"
                >
                    Coba Login Lagi
                </a>

                <a
                    href="/portal-pusri/"
                    class="secondary"
                >
                    Kembali ke Portal
                </a>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit;
}