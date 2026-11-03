<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| SECURE SESSION
|--------------------------------------------------------------------------
|
| File auth.php boleh dipanggil dari:
|
| - index.php        → Portal PUBLIC
| - /admin/*.php     → ADMIN ONLY
|
| Memuat session TIDAK berarti user wajib login.
|
*/

require_once __DIR__ . "/../config/session.php";


/*
|--------------------------------------------------------------------------
| CEK STATUS LOGIN
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    if (
        empty($_SESSION["logged_in"])
        ||
        $_SESSION["logged_in"] !== true
    ) {

        return false;
    }


    if (
        !isset($_SESSION["user"])
        ||
        !is_array($_SESSION["user"])
    ) {

        return false;
    }


    if (
        empty($_SESSION["user"]["id"])
    ) {

        return false;
    }


    return true;
}


/*
|--------------------------------------------------------------------------
| USER SAAT INI
|--------------------------------------------------------------------------
|
| Pada Portal PUBLIC:
|
| - belum login  → null
| - admin login  → data admin
|
| Tidak melakukan redirect.
|
*/

function currentUser(): ?array
{
    if (
        !isLoggedIn()
    ) {

        return null;
    }


    return $_SESSION["user"];
}


/*
|--------------------------------------------------------------------------
| AMBIL ROLE
|--------------------------------------------------------------------------
*/

function currentRoles(): array
{
    $user =
        currentUser();


    if (
        $user === null
        ||
        !isset($user["roles"])
        ||
        !is_array($user["roles"])
    ) {

        return [];
    }


    $roles = [];


    foreach (
        $user["roles"]
        as $role
    ) {

        if (
            !is_string($role)
        ) {

            continue;
        }


        $role =
            strtoupper(
                trim($role)
            );


        if (
            $role !== ""
        ) {

            $roles[] =
                $role;
        }
    }


    return array_values(
        array_unique(
            $roles
        )
    );
}


/*
|--------------------------------------------------------------------------
| CEK ROLE
|--------------------------------------------------------------------------
*/

function hasRole(
    string $role
): bool {

    if (
        !isLoggedIn()
    ) {

        return false;
    }


    $role =
        strtoupper(
            trim($role)
        );


    if (
        $role === ""
    ) {

        return false;
    }


    return in_array(
        $role,
        currentRoles(),
        true
    );
}


/*
|--------------------------------------------------------------------------
| WAJIB LOGIN
|--------------------------------------------------------------------------
|
| Helper ini TIDAK digunakan pada index.php Portal.
|
| Dipertahankan apabila suatu halaman internal hanya membutuhkan login.
|
*/

function requireLogin(): void
{
    if (
        isLoggedIn()
    ) {

        return;
    }


    header(
        "Location: /portal-pusri/login.php",
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| WAJIB ROLE
|--------------------------------------------------------------------------
|
| Semua halaman /admin menggunakan:
|
| requireRole("ADMIN");
|
*/

function requireRole(
    string $role
): void {

    /*
    |--------------------------------------------------------------------------
    | BELUM LOGIN
    |--------------------------------------------------------------------------
    |
    | Arahkan ke Login Admin.
    |
    */

    if (
        !isLoggedIn()
    ) {

        header(
            "Location: /portal-pusri/login.php",
            true,
            302
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | SUDAH LOGIN + ROLE SESUAI
    |--------------------------------------------------------------------------
    */

    if (
        hasRole($role)
    ) {

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN TETAPI TIDAK BERHAK
    |--------------------------------------------------------------------------
    */

    http_response_code(
        403
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
            Akses Ditolak - Portal Layanan SDM PUSRI
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


            .access-card {
                width: 100%;

                max-width: 500px;

                padding: 38px;

                background: #ffffff;

                border:
                    1px solid
                    #e3e8ef;

                border-radius: 18px;

                box-shadow:
                    0
                    18px
                    45px
                    rgba(
                        15,
                        23,
                        42,
                        0.08
                    );

                text-align: center;
            }


            .status {
                display: inline-block;

                margin-bottom: 18px;

                padding:
                    7px
                    13px;

                border-radius: 20px;

                background: #fff1f2;

                color: #b42318;

                font-size: 12px;

                font-weight: 700;
            }


            h1 {
                margin:
                    0
                    0
                    12px;

                color: #17365d;

                font-size: 27px;
            }


            p {
                margin:
                    0
                    0
                    26px;

                color: #64748b;

                line-height: 1.7;
            }


            .button {
                display: inline-flex;

                align-items: center;

                justify-content: center;

                min-height: 43px;

                padding:
                    0
                    18px;

                border-radius: 8px;

                background: #17365d;

                color: #ffffff;

                text-decoration: none;

                font-size: 13px;

                font-weight: 700;
            }

        </style>

    </head>


    <body>


        <div class="access-card">


            <span class="status">

                403 - ACCESS DENIED

            </span>


            <h1>

                Akses Ditolak

            </h1>


            <p>

                Akun yang digunakan tidak mempunyai
                hak akses sebagai administrator.

            </p>


            <a
                href="/portal-pusri/"
                class="button"
            >

                Kembali ke Portal

            </a>


        </div>


    </body>

    </html>

    <?php

    exit;
}