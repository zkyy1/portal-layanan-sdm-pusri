<?php

/*
|--------------------------------------------------------------------------
| SECURE SESSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../config/session.php";


/*
|--------------------------------------------------------------------------
| BUAT / AMBIL TOKEN CSRF
|--------------------------------------------------------------------------
*/

function csrfToken(): string
{
    if (
        empty(
            $_SESSION["csrf_token"]
        )
    ) {

        $_SESSION["csrf_token"] =
            bin2hex(
                random_bytes(32)
            );
    }


    return
        $_SESSION["csrf_token"];
}


/*
|--------------------------------------------------------------------------
| HIDDEN INPUT CSRF
|--------------------------------------------------------------------------
*/

function csrfField(): string
{
    return
        '<input type="hidden" name="csrf_token" value="'
        .
        htmlspecialchars(
            csrfToken(),
            ENT_QUOTES,
            "UTF-8"
        )
        .
        '">';
}


/*
|--------------------------------------------------------------------------
| VALIDASI TOKEN
|--------------------------------------------------------------------------
*/

function verifyCsrfToken(
    ?string $token
): bool {

    if (
        empty($token)
        ||
        empty(
            $_SESSION["csrf_token"]
        )
    ) {

        return false;
    }


    return hash_equals(
        $_SESSION["csrf_token"],
        $token
    );
}


/*
|--------------------------------------------------------------------------
| WAJIB CSRF VALID
|--------------------------------------------------------------------------
*/

function requireCsrf(): void
{
    $token =
        $_POST["csrf_token"]
        ?? null;


    if (
        !verifyCsrfToken(
            $token
        )
    ) {

        http_response_code(403);

        die(
            "403 - Permintaan tidak valid. "
            .
            "Token keamanan CSRF tidak cocok."
        );
    }
}