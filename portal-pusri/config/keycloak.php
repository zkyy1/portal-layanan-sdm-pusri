<?php

/*
|--------------------------------------------------------------------------
| SECURE SESSION
|--------------------------------------------------------------------------
|
| Session harus dikonfigurasi sebelum proses autentikasi OIDC berjalan.
|
*/

require_once __DIR__ . "/session.php";


/*
|--------------------------------------------------------------------------
| COMPOSER AUTOLOAD
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../vendor/autoload.php";


use Jumbojett\OpenIDConnectClient;


/*
|--------------------------------------------------------------------------
| KONFIGURASI DASAR KEYCLOAK
|--------------------------------------------------------------------------
|
| DEVELOPMENT / LOCALHOST
|
*/

$keycloakBaseUrl =
    "http://localhost:8080";

$keycloakRealm =
    "pusri";

$keycloakClientId =
    "portal-pusri";


/*
|--------------------------------------------------------------------------
| FILE SECRET
|--------------------------------------------------------------------------
|
| Credential tidak disimpan di dalam:
|
| C:\xampp\htdocs\
|
| tetapi di:
|
| C:\xampp\secure\portal-pusri-secrets.php
|
*/

$secretFile =
    "C:/xampp/secure/portal-pusri-secrets.php";


/*
|--------------------------------------------------------------------------
| VALIDASI FILE SECRET
|--------------------------------------------------------------------------
*/

if (
    !is_file($secretFile)
    ||
    !is_readable($secretFile)
) {

    throw new RuntimeException(
        "Konfigurasi secret Portal PUSRI tidak tersedia."
    );
}


/*
|--------------------------------------------------------------------------
| LOAD SECRET
|--------------------------------------------------------------------------
*/

$secrets =
    require $secretFile;


/*
|--------------------------------------------------------------------------
| VALIDASI STRUKTUR SECRET
|--------------------------------------------------------------------------
*/

if (!is_array($secrets)) {

    throw new RuntimeException(
        "Konfigurasi secret Portal PUSRI tidak valid."
    );
}


/*
|--------------------------------------------------------------------------
| KEYCLOAK CLIENT SECRET
|--------------------------------------------------------------------------
*/

$keycloakClientSecret =
    $secrets["keycloak_client_secret"]
    ?? null;


/*
|--------------------------------------------------------------------------
| VALIDASI CLIENT SECRET
|--------------------------------------------------------------------------
*/

if (
    !is_string($keycloakClientSecret)
    ||
    trim($keycloakClientSecret) === ""
) {

    throw new RuntimeException(
        "Keycloak Client Secret belum dikonfigurasi."
    );
}


$keycloakClientSecret =
    trim($keycloakClientSecret);


/*
|--------------------------------------------------------------------------
| PROVIDER / ISSUER URL
|--------------------------------------------------------------------------
|
| Hasil:
|
| http://localhost:8080/realms/pusri
|
*/

$keycloakProviderUrl =
    rtrim(
        $keycloakBaseUrl,
        "/"
    )
    .
    "/realms/"
    .
    rawurlencode(
        $keycloakRealm
    );


/*
|--------------------------------------------------------------------------
| CALLBACK URL
|--------------------------------------------------------------------------
|
| Harus sama dengan konfigurasi Valid Redirect URI di Keycloak:
|
| http://localhost/portal-pusri/callback.php
|
*/

$keycloakRedirectUri =
    "http://localhost/portal-pusri/callback.php";


/*
|--------------------------------------------------------------------------
| PORTAL URL
|--------------------------------------------------------------------------
|
| Digunakan sebagai tujuan setelah logout Keycloak.
|
*/

$keycloakPortalUrl =
    "http://localhost/portal-pusri/";


/*
|--------------------------------------------------------------------------
| OPENID CONNECT CLIENT
|--------------------------------------------------------------------------
*/

$oidc =
    new OpenIDConnectClient(
        $keycloakProviderUrl,
        $keycloakClientId,
        $keycloakClientSecret
    );


/*
|--------------------------------------------------------------------------
| REDIRECT URI
|--------------------------------------------------------------------------
*/

$oidc->setRedirectURL(
    $keycloakRedirectUri
);


/*
|--------------------------------------------------------------------------
| OIDC SCOPES
|--------------------------------------------------------------------------
|
| Library menambahkan scope "openid".
|
| profile:
| - preferred_username
| - given_name
| - family_name
|
| email:
| - email
|
*/

$oidc->addScope([
    "profile",
    "email"
]);