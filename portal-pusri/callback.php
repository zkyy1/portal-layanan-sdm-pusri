<?php

/*
|--------------------------------------------------------------------------
| CALLBACK LOGIN ADMIN KEYCLOAK
|--------------------------------------------------------------------------
|
| Callback ini hanya menerima user yang mempunyai Realm Role:
|
| ADMIN
|
| Pengunjung biasa tidak membutuhkan callback karena Portal bersifat public.
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
| CALLBACK
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | 1. VALIDASI AUTHORIZATION CODE
    |--------------------------------------------------------------------------
    */

    $oidc->authenticate();


    /*
    |--------------------------------------------------------------------------
    | 2. AMBIL IDENTITAS USER
    |--------------------------------------------------------------------------
    */

    $sub =
        $oidc->requestUserInfo(
            "sub"
        );


    $username =
        $oidc->requestUserInfo(
            "preferred_username"
        );


    $firstName =
        $oidc->requestUserInfo(
            "given_name"
        );


    $lastName =
        $oidc->requestUserInfo(
            "family_name"
        );


    $email =
        $oidc->requestUserInfo(
            "email"
        );


    /*
    |--------------------------------------------------------------------------
    | 3. VALIDASI IDENTITAS
    |--------------------------------------------------------------------------
    */

    if (
        !is_string($sub)
        ||
        trim($sub) === ""
    ) {

        throw new RuntimeException(
            "OIDC subject tidak tersedia."
        );
    }


    if (
        !is_string($username)
        ||
        trim($username) === ""
    ) {

        throw new RuntimeException(
            "Username Keycloak tidak tersedia."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALISASI
    |--------------------------------------------------------------------------
    */

    $sub =
        trim($sub);


    $username =
        trim($username);


    $firstName =
        is_string($firstName)
            ? trim($firstName)
            : "";


    $lastName =
        is_string($lastName)
            ? trim($lastName)
            : "";


    $email =
        is_string($email)
            ? trim($email)
            : "";


    /*
    |--------------------------------------------------------------------------
    | 4. ACCESS TOKEN
    |--------------------------------------------------------------------------
    */

    $accessTokenPayload =
        $oidc->getAccessTokenPayload();


    /*
    |--------------------------------------------------------------------------
    | 5. AMBIL REALM ROLE
    |--------------------------------------------------------------------------
    |
    | Role Realm Keycloak berada pada:
    |
    | realm_access.roles
    |
    */

    $roles =
        [];


    if (
        is_object(
            $accessTokenPayload
        )
        &&
        isset(
            $accessTokenPayload
                ->realm_access
        )
        &&
        is_object(
            $accessTokenPayload
                ->realm_access
        )
        &&
        isset(
            $accessTokenPayload
                ->realm_access
                ->roles
        )
        &&
        is_array(
            $accessTokenPayload
                ->realm_access
                ->roles
        )
    ) {

        foreach (
            $accessTokenPayload
                ->realm_access
                ->roles
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
    }


    /*
    |--------------------------------------------------------------------------
    | HILANGKAN DUPLIKAT
    |--------------------------------------------------------------------------
    */

    $roles =
        array_values(
            array_unique(
                $roles
            )
        );


    /*
    |--------------------------------------------------------------------------
    | 6. ID TOKEN
    |--------------------------------------------------------------------------
    */

    $idToken =
        $oidc->getIdToken();


    if (
        !is_string($idToken)
        ||
        trim($idToken) === ""
    ) {

        throw new RuntimeException(
            "ID Token Keycloak tidak tersedia."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 7. VALIDASI ROLE ADMIN
    |--------------------------------------------------------------------------
    |
    | INI BAGIAN UTAMA REQUIREMENT BARU.
    |
    */

    $isAdmin =
        in_array(
            "ADMIN",
            $roles,
            true
        );


    if (
        !$isAdmin
    ) {

        /*
        |--------------------------------------------------------------------------
        | AKUN BERHASIL LOGIN KEYCLOAK TETAPI BUKAN ADMIN
        |--------------------------------------------------------------------------
        |
        | Jangan membuat session login Portal.
        |
        */

        unset(
            $_SESSION["user"],
            $_SESSION["logged_in"],
            $_SESSION["id_token"],
            $_SESSION["csrf_token"]
        );


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


                .card {
                    width: 100%;

                    max-width: 520px;

                    padding: 38px;

                    border:
                        1px solid
                        #e4e9f0;

                    border-radius: 18px;

                    background: #ffffff;

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
                    display: inline-flex;

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
                        25px;

                    color: #64748b;

                    line-height: 1.7;
                }


                .username {
                    color: #172033;

                    font-weight: 700;
                }


                .actions {
                    display: flex;

                    justify-content: center;

                    gap: 10px;

                    flex-wrap: wrap;
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
                        #d8dee7;

                    background: #ffffff;

                    color: #17365d;
                }

            </style>

        </head>


        <body>


            <div class="card">


                <div class="status">

                    AKSES ADMIN DITOLAK

                </div>


                <h1>

                    Bukan Akun Administrator

                </h1>


                <p>

                    Akun

                    <span class="username">

                        <?= htmlspecialchars(
                            $username,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            "UTF-8"
                        ) ?>

                    </span>

                    berhasil diautentikasi oleh Keycloak,
                    tetapi akun tersebut tidak memiliki
                    Realm Role <strong>ADMIN</strong>.

                </p>


                <div class="actions">


                    <a
                        href="/portal-pusri/"
                        class="
                            button
                            secondary
                        "
                    >

                        Kembali ke Portal

                    </a>


                    <a
                        href="/portal-pusri/login.php"
                        class="
                            button
                            primary
                        "
                    >

                        Login Admin Lain

                    </a>


                </div>


            </div>


        </body>

        </html>

        <?php

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | 8. SESSION FIXATION PROTECTION
    |--------------------------------------------------------------------------
    */

    if (
        !session_regenerate_id(
            true
        )
    ) {

        throw new RuntimeException(
            "Session login tidak dapat diperbarui."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CATAT ROTASI SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION[
        "_session_regenerated_at"
    ] = time();


    /*
    |--------------------------------------------------------------------------
    | 9. BERSIHKAN AUTH LAMA
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION["user"],
        $_SESSION["logged_in"],
        $_SESSION["id_token"],
        $_SESSION["csrf_token"]
    );


    /*
    |--------------------------------------------------------------------------
    | 10. SIMPAN ADMIN
    |--------------------------------------------------------------------------
    */

    $_SESSION["user"] = [

        "id" =>
            $sub,

        "username" =>
            $username,

        "first_name" =>
            $firstName,

        "last_name" =>
            $lastName,

        "email" =>
            $email,

        "roles" =>
            $roles

    ];


    /*
    |--------------------------------------------------------------------------
    | LOGIN STATUS
    |--------------------------------------------------------------------------
    */

    $_SESSION["logged_in"] =
        true;


    /*
    |--------------------------------------------------------------------------
    | ID TOKEN UNTUK LOGOUT
    |--------------------------------------------------------------------------
    */

    $_SESSION["id_token"] =
        $idToken;


    /*
    |--------------------------------------------------------------------------
    | CSRF BARU NANTI DIBUAT DI ADMIN
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION["csrf_token"]
    );


    /*
    |--------------------------------------------------------------------------
    | 11. LANGSUNG MASUK DASHBOARD ADMIN
    |--------------------------------------------------------------------------
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
    | LOG SERVER
    |--------------------------------------------------------------------------
    */

    error_log(
        "Portal Admin Callback Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | BERSIHKAN AUTH
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
            Login Admin Gagal - Portal Layanan SDM PUSRI
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

                max-width: 500px;

                padding: 38px;

                border:
                    1px solid
                    #e3e8ef;

                border-radius: 18px;

                background: #ffffff;

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


            h1 {
                margin:
                    0
                    0
                    12px;

                color: #17365d;

                font-size: 26px;
            }


            p {
                margin:
                    0
                    0
                    25px;

                color: #64748b;

                line-height: 1.7;
            }


            .actions {
                display: flex;

                justify-content: center;

                gap: 10px;

                flex-wrap: wrap;
            }


            a {
                display: inline-flex;

                align-items: center;

                justify-content: center;

                min-height: 43px;

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
                    #d8dee7;

                background: #ffffff;

                color: #17365d;
            }

        </style>

    </head>


    <body>


        <div class="card">


            <h1>

                Login Admin Tidak Berhasil

            </h1>


            <p>

                Proses autentikasi administrator tidak
                dapat diselesaikan. Silakan mencoba
                login kembali.

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