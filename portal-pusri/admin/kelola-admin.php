<?php

/*

|--------------------------------------------------------------------------

| PROTEKSI ADMIN KEYCLOAK

|--------------------------------------------------------------------------

*/

require_once __DIR__ . "/../includes/auth.php";

requireRole("ADMIN");

$user = currentUser();

/*

|--------------------------------------------------------------------------

| CSRF

|--------------------------------------------------------------------------

*/

require_once __DIR__ . "/../includes/csrf.php";

/*

|--------------------------------------------------------------------------

| KONFIGURASI KEYCLOAK ADMIN API

|--------------------------------------------------------------------------

*/

$secureSecretsPath =

    __DIR__

    . "/../../../secure/portal-pusri-secrets.php";

if (

    !is_file(

        $secureSecretsPath

    )

) {

    http_response_code(500);

    die(

        "Konfigurasi keamanan tidak ditemukan."

    );

}

$secrets =

    require $secureSecretsPath;

$keycloakBaseUrl =

    "http://localhost:8080";

$keycloakRealm =

    "pusri";

$keycloakAdminClientId =

    $secrets[

        "keycloak_admin_client_id"

    ]

    ?? "";

$keycloakAdminClientSecret =

    $secrets[

        "keycloak_admin_client_secret"

    ]

    ?? "";

/*

|--------------------------------------------------------------------------

| VALIDASI KONFIGURASI

|--------------------------------------------------------------------------

*/

if (

    trim($keycloakAdminClientId) === ""

    ||

    trim($keycloakAdminClientSecret) === ""

) {

    http_response_code(500);

    die(

        "Konfigurasi Keycloak Admin API belum lengkap."

    );

}

/*

|--------------------------------------------------------------------------

| PESAN

|--------------------------------------------------------------------------

*/

$error =

    "";

$success =

    "";

/*

|--------------------------------------------------------------------------

| HELPER

|--------------------------------------------------------------------------

*/

function keycloakAdminGetToken(

    string $baseUrl,

    string $realm,

    string $clientId,

    string $clientSecret

): string {

    $url =

        rtrim(

            $baseUrl,

            "/"

        )

        . "/realms/"

        . rawurlencode($realm)

        . "/protocol/openid-connect/token";

    $ch =

        curl_init(

            $url

        );

    if (

        $ch === false

    ) {

        throw new RuntimeException(

            "cURL tidak dapat digunakan."

        );

    }

    curl_setopt_array(

        $ch,

        [

            CURLOPT_POST =>

                true,

            CURLOPT_POSTFIELDS =>

                http_build_query(

                    [

                        "grant_type" =>

                            "client_credentials",

                        "client_id" =>

                            $clientId,

                        "client_secret" =>

                            $clientSecret

                    ]

                ),

            CURLOPT_RETURNTRANSFER =>

                true,

            CURLOPT_CONNECTTIMEOUT =>

                10,

            CURLOPT_TIMEOUT =>

                30,

            CURLOPT_HTTPHEADER =>

                [

                    "Content-Type: application/x-www-form-urlencoded",

                    "Accept: application/json"

                ]

        ]

    );

    $response =

        curl_exec(

            $ch

        );

    $curlError =

        curl_error(

            $ch

        );

    $status =

        (int)

        curl_getinfo(

            $ch,

            CURLINFO_HTTP_CODE

        );

    curl_close(

        $ch

    );

    if (

        $response === false

        ||

        $curlError !== ""

    ) {

        throw new RuntimeException(

            "Keycloak token request gagal."

        );

    }

    $data =

        json_decode(

            $response,

            true

        );

    if (

        $status < 200

        ||

        $status >= 300

        ||

        !is_array($data)

        ||

        empty($data["access_token"])

    ) {

        throw new RuntimeException(

            "Token Admin API Keycloak tidak dapat diperoleh."

        );

    }

    return

        (string)

        $data["access_token"];

}

/*

|--------------------------------------------------------------------------

| REQUEST KEYCLOAK ADMIN API

|--------------------------------------------------------------------------

*/

function keycloakAdminRequest(

    string $method,

    string $url,

    string $token,

    ?array $payload = null

): array {

    $ch =

        curl_init(

            $url

        );

    if (

        $ch === false

    ) {

        throw new RuntimeException(

            "cURL tidak dapat digunakan."

        );

    }

    $headers = [

        "Authorization: Bearer " . $token,

        "Accept: application/json"

    ];

    $options = [

        CURLOPT_CUSTOMREQUEST =>

            strtoupper($method),

        CURLOPT_RETURNTRANSFER =>

            true,

        CURLOPT_HEADER =>

            true,

        CURLOPT_CONNECTTIMEOUT =>

            10,

        CURLOPT_TIMEOUT =>

            30

    ];

    if (

        $payload !== null

    ) {

        $json =

            json_encode(

                $payload,

                JSON_UNESCAPED_SLASHES

                |

                JSON_UNESCAPED_UNICODE

            );

        if (

            $json === false

        ) {

            curl_close(

                $ch

            );

            throw new RuntimeException(

                "Data request tidak dapat diproses."

            );

        }

        $options[

            CURLOPT_POSTFIELDS

        ] =

            $json;

        $headers[] =

            "Content-Type: application/json";

    }

    $options[

        CURLOPT_HTTPHEADER

    ] =

        $headers;

    curl_setopt_array(

        $ch,

        $options

    );

    $response =

        curl_exec(

            $ch

        );

    $curlError =

        curl_error(

            $ch

        );

    $status =

        (int)

        curl_getinfo(

            $ch,

            CURLINFO_HTTP_CODE

        );

    $headerSize =

        (int)

        curl_getinfo(

            $ch,

            CURLINFO_HEADER_SIZE

        );

    curl_close(

        $ch

    );

    if (

        $response === false

        ||

        $curlError !== ""

    ) {

        throw new RuntimeException(

            "Request ke Keycloak gagal."

        );

    }

    $responseHeaders =

        substr(

            $response,

            0,

            $headerSize

        );

    $responseBody =

        substr(

            $response,

            $headerSize

        );

    $data = [];

    if (

        trim($responseBody) !== ""

    ) {

        $decoded =

            json_decode(

                $responseBody,

                true

            );

        if (

            is_array($decoded)

        ) {

            $data =

                $decoded;

        }

    }

    $location =

        "";

    foreach (

        preg_split(

            "/\r\n|\r|\n/",

            $responseHeaders

        )

        as $headerLine

    ) {

        if (

            stripos(

                $headerLine,

                "Location:"

            ) === 0

        ) {

            $location =

                trim(

                    substr(

                        $headerLine,

                        strlen("Location:")

                    )

                );

            break;

        }

    }

    return [

        "status" =>

            $status,

        "headers" =>

            $responseHeaders,

        "body" =>

            $data,

        "raw_body" =>

            $responseBody,

        "location" =>

            $location

    ];

}

/*

|--------------------------------------------------------------------------

| AMBIL ID USER DARI LOCATION

|--------------------------------------------------------------------------

*/

function getKeycloakUserIdFromLocation(

    string $location

): string {

    if (

        trim($location) === ""

    ) {

        return "";

    }

    $parts =

        parse_url(

            $location

        );

    if (

        !isset(

            $parts["path"]

        )

    ) {

        return "";

    }

    $path =

        trim(

            $parts["path"],

            "/"

        );

    $segments =

        explode(

            "/",

            $path

        );

    if (

        count($segments) === 0

    ) {

        return "";

    }

    return

        (string)

        end($segments);

}

/*

|--------------------------------------------------------------------------

| PROSES TAMBAH ADMIN

|--------------------------------------------------------------------------

*/

if (

    $_SERVER["REQUEST_METHOD"]

    ===

    "POST"

) {

    try {

        /*

        |--------------------------------------------------------------------------

        | CSRF

        |--------------------------------------------------------------------------

        */

        requireCsrf();

        /*

        |--------------------------------------------------------------------------

        | AMBIL DATA

        |--------------------------------------------------------------------------

        */

        $username =

            trim(

                (string)

                (

                    $_POST["username"]

                    ??

                    ""

                )

            );

        $email =

            trim(

                (string)

                (

                    $_POST["email"]

                    ??

                    ""

                )

            );

        $firstName =

            trim(

                (string)

                (

                    $_POST["first_name"]

                    ??

                    ""

                )

            );

        $lastName =

            trim(

                (string)

                (

                    $_POST["last_name"]

                    ??

                    ""

                )

            );

        $password =

            (string)

            (

                $_POST["password"]

                ??

                ""

            );

        $passwordConfirm =

            (string)

            (

                $_POST["password_confirm"]

                ??

                ""

            );

        /*

        |--------------------------------------------------------------------------

        | VALIDASI USERNAME

        |--------------------------------------------------------------------------

        */

        if (

            $username === ""

        ) {

            throw new RuntimeException(

                "Username wajib diisi."

            );

        }

        if (

            strlen($username) < 3

            ||

            strlen($username) > 50

        ) {

            throw new RuntimeException(

                "Username harus berisi 3 sampai 50 karakter."

            );

        }

        if (

            !preg_match(

                '/^[A-Za-z0-9._-]+$/',

                $username

            )

        ) {

            throw new RuntimeException(

                "Username hanya boleh menggunakan huruf, angka, titik, garis bawah, dan tanda minus."

            );

        }

        /*

        |--------------------------------------------------------------------------

        | VALIDASI EMAIL

        |--------------------------------------------------------------------------

        */

        if (

            $email === ""

        ) {

            throw new RuntimeException(

                "Email wajib diisi."

            );

        }

        if (

            !filter_var(

                $email,

                FILTER_VALIDATE_EMAIL

            )

        ) {

            throw new RuntimeException(

                "Format email tidak valid."

            );

        }

        if (

            strlen($email) > 255

        ) {

            throw new RuntimeException(

                "Email terlalu panjang."

            );

        }

        /*

        |--------------------------------------------------------------------------

        | VALIDASI NAMA

        |--------------------------------------------------------------------------

        */

        if (

            $firstName === ""

        ) {

            throw new RuntimeException(

                "Nama depan wajib diisi."

            );

        }

        if (

            strlen($firstName) > 100

        ) {

            throw new RuntimeException(

                "Nama depan terlalu panjang."

            );

        }

        if (

            strlen($lastName) > 100

        ) {

            throw new RuntimeException(

                "Nama belakang terlalu panjang."

            );

        }

        /*

        |--------------------------------------------------------------------------

        | VALIDASI PASSWORD

        |--------------------------------------------------------------------------

        */

        if (

            strlen($password) < 8

        ) {

            throw new RuntimeException(

                "Password minimal 8 karakter."

            );

        }

        if (

            $password !== $passwordConfirm

        ) {

            throw new RuntimeException(

                "Konfirmasi password tidak sama."

            );

        }

        /*

        |--------------------------------------------------------------------------

        | TOKEN ADMIN API

        |--------------------------------------------------------------------------

        */

        $token =

            keycloakAdminGetToken(

                $keycloakBaseUrl,

                $keycloakRealm,

                $keycloakAdminClientId,

                $keycloakAdminClientSecret

            );

        /*

        |--------------------------------------------------------------------------

        | BUAT USER BARU

        |--------------------------------------------------------------------------

        */

        $createUserUrl =

            rtrim(

                $keycloakBaseUrl,

                "/"

            )

            . "/admin/realms/"

            . rawurlencode(

                $keycloakRealm

            )

            . "/users";

        $createResponse =

            keycloakAdminRequest(

                "POST",

                $createUserUrl,

                $token,

                [

                    "username" =>

                        $username,

                    "email" =>

                        $email,

                    "firstName" =>

                        $firstName,

                    "lastName" =>

                        $lastName,

                    "enabled" =>

                        true,

                    "emailVerified" =>

                        false,

                    "credentials" =>

                        [

                            [

                                "type" =>

                                    "password",

                                "value" =>

                                    $password,

                                "temporary" =>

                                    false

                            ]

                        ]

                ]

            );

        /*

        |--------------------------------------------------------------------------

        | USERNAME / EMAIL SUDAH TERPAKAI

        |--------------------------------------------------------------------------

        */

        if (

            $createResponse["status"]

            ===

            409

        ) {

            throw new RuntimeException(

                "Username atau email tersebut sudah terdaftar di Keycloak."

            );

        }

        if (

            $createResponse["status"]

            !==

            201

        ) {

            throw new RuntimeException(

                "User admin gagal dibuat di Keycloak."

            );

        }

        /*

        |--------------------------------------------------------------------------

        | AMBIL ID USER

        |--------------------------------------------------------------------------

        */

        $userId =

            getKeycloakUserIdFromLocation(

                $createResponse["location"]

            );

        if (

            $userId === ""

        ) {

            throw new RuntimeException(

                "ID user baru dari Keycloak tidak ditemukan."

            );

        }

        /*

        |--------------------------------------------------------------------------

        | AMBIL ROLE ADMIN

        |--------------------------------------------------------------------------

        */

        $roleUrl =

            rtrim(

                $keycloakBaseUrl,

                "/"

            )

            . "/admin/realms/"

            . rawurlencode(

                $keycloakRealm

            )

            . "/roles/ADMIN";

        $roleResponse =

            keycloakAdminRequest(

                "GET",

                $roleUrl,

                $token

            );

        if (

            $roleResponse["status"]

            !==

            200

        ) {

            /*

            |--------------------------------------------------------------------------

            | ROLLBACK USER

            |--------------------------------------------------------------------------

            */

            $deleteUserUrl =

                rtrim(

                    $keycloakBaseUrl,

                    "/"

                )

                . "/admin/realms/"

                . rawurlencode(

                    $keycloakRealm

                )

                . "/users/"

                . rawurlencode(

                    $userId

                );

            try {

                keycloakAdminRequest(

                    "DELETE",

                    $deleteUserUrl,

                    $token

                );

            } catch (

                Throwable $rollbackError

            ) {

                error_log(

                    "Portal PUSRI Admin Rollback Error: "

                    . $rollbackError->getMessage()

                );

            }

            throw new RuntimeException(

                "Role ADMIN tidak ditemukan di Keycloak."

            );

        }

        $adminRole =

            $roleResponse["body"];

        if (

            !is_array(

                $adminRole

            )

            ||

            empty(

                $adminRole["id"]

            )

        ) {

            throw new RuntimeException(

                "Data Realm Role ADMIN tidak valid."

            );

        }

        /*

        |--------------------------------------------------------------------------

        | PASANG ROLE ADMIN

        |--------------------------------------------------------------------------

        */

        $mappingUrl =

            rtrim(

                $keycloakBaseUrl,

                "/"

            )

            . "/admin/realms/"

            . rawurlencode(

                $keycloakRealm

            )

            . "/users/"

            . rawurlencode(

                $userId

            )

            . "/role-mappings/realm";

        $mappingResponse =

            keycloakAdminRequest(

                "POST",

                $mappingUrl,

                $token,

                [

                    [

                        "id" =>

                            (string)

                            $adminRole["id"],

                        "name" =>

                            "ADMIN"

                    ]

                ]

            );

        /*

        |--------------------------------------------------------------------------

        | JIKA ROLE GAGAL

        |--------------------------------------------------------------------------

        */

        if (

            $mappingResponse["status"]

            < 200

            ||

            $mappingResponse["status"]

            >= 300

        ) {

            /*

            |--------------------------------------------------------------------------

            | ROLLBACK USER

            |--------------------------------------------------------------------------

            */

            $deleteUserUrl =

                rtrim(

                    $keycloakBaseUrl,

                    "/"

                )

                . "/admin/realms/"

                . rawurlencode(

                    $keycloakRealm

                )

                . "/users/"

                . rawurlencode(

                    $userId

                );

            try {

                keycloakAdminRequest(

                    "DELETE",

                    $deleteUserUrl,

                    $token

                );

            } catch (

                Throwable $rollbackError

            ) {

                error_log(

                    "Portal PUSRI Admin Rollback Error: "

                    . $rollbackError->getMessage()

                );

            }

            throw new RuntimeException(

                "User berhasil dibuat, tetapi role ADMIN gagal diberikan. User otomatis dibatalkan."

            );

        }

        /*

        |--------------------------------------------------------------------------

        | BERHASIL

        |--------------------------------------------------------------------------

        */

        $success =

            "Admin berhasil ditambahkan. User "

            . $username

            . " sekarang memiliki Realm Role ADMIN.";

    } catch (

        Throwable $e

    ) {

        error_log(

            "Portal PUSRI Kelola Admin Error: "

            . get_class($e)

            . ": "

            . $e->getMessage()

        );

        $error =

            $e->getMessage();

    }

}

/* DAFTAR ADMIN DAN JUMLAH TOTAL */
function portalAdminEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

$portalAdminList = [];
$portalAdminListError = "";
$portalAdminTotal = null;
$portalAdminActive = null;
$portalAdminInactive = null;

try {
    $listToken = isset($token) && is_string($token) && $token !== ""
        ? $token
        : keycloakAdminGetToken(
            $keycloakBaseUrl,
            $keycloakRealm,
            $keycloakAdminClientId,
            $keycloakAdminClientSecret
        );
    $listBaseUrl = rtrim($keycloakBaseUrl, "/")
        . "/admin/realms/" . rawurlencode($keycloakRealm)
        . "/roles/ADMIN/users";
    $listOffset = 0;
    $listSeen = [];
    $listComplete = false;
    $listStarted = microtime(true);

    // Baca seluruh halaman sebelum menampilkan jumlah total.
    // Jangan gunakan /users/count karena itu menghitung semua user realm.
    for ($listPage = 0; $listPage < 1000; $listPage++) {
        if (microtime(true) - $listStarted > 25) {
            throw new RuntimeException("Batas waktu pemuatan daftar admin.");
        }
        $listUrl = $listBaseUrl . "?" . http_build_query([
            "first" => $listOffset,
            "max" => 100,
            "briefRepresentation" => "false",
        ]);
        $listResponse = keycloakAdminRequest("GET", $listUrl, $listToken);
        $listStatus = (int) ($listResponse["status"] ?? 0);
        if ($listStatus !== 200) {
            if ($listStatus === 403) {
                $portalAdminListError = "Daftar admin belum dapat dibaca karena izin akses ke data akun belum tersedia.";
            }
            throw new RuntimeException("Daftar admin tidak dapat dibaca.", $listStatus);
        }
        $listRows = $listResponse["body"] ?? null;
        $listRaw = ltrim((string) ($listResponse["raw_body"] ?? ""));
        if (!is_array($listRows) || substr($listRaw, 0, 1) !== "[") {
            throw new UnexpectedValueException("Format daftar admin tidak valid.");
        }
        if (count($listRows) === 0) {
            $listComplete = true;
            break;
        }
        $listAdded = 0;
        foreach ($listRows as $listRow) {
            if (
                !is_array($listRow)
                || !isset($listRow["id"], $listRow["username"])
                || !is_string($listRow["id"])
                || $listRow["id"] === ""
                || !is_string($listRow["username"])
            ) {
                throw new UnexpectedValueException("Data akun admin tidak valid.");
            }
            if (!isset($listSeen[$listRow["id"]])) {
                $listSeen[$listRow["id"]] = true;
                $portalAdminList[] = $listRow;
                $listAdded++;
            }
        }
        if ($listAdded === 0) {
            throw new RuntimeException("Daftar admin berulang saat membaca halaman berikutnya.");
        }
        $listOffset += count($listRows);
    }
    if (!$listComplete) {
        throw new RuntimeException("Daftar admin belum terbaca seluruhnya.");
    }
    usort($portalAdminList, static function (array $a, array $b): int {
        return strnatcasecmp($a["username"], $b["username"]);
    });
    $portalAdminTotal = count($portalAdminList);
    $portalAdminActive = count(array_filter($portalAdminList, static function (array $a): bool {
        return ($a["enabled"] ?? null) === true;
    }));
    $portalAdminInactive = count(array_filter($portalAdminList, static function (array $a): bool {
        return ($a["enabled"] ?? null) === false;
    }));
} catch (Throwable $listException) {
    $portalAdminList = [];
    if ($portalAdminListError === "") {
        $portalAdminListError = "Daftar dan jumlah admin belum dapat dimuat. Silakan coba lagi beberapa saat lagi.";
    }
    error_log("Portal PUSRI Admin List: " . get_class($listException)
        . " (code " . (int) $listException->getCode() . ")");
}

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

        Kelola Admin - Admin Layanan SDM

    </title>

    <style>

        /* =========================================================

           RESET

        ========================================================= */

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

        }

        body {

            font-family:

                Arial,

                Helvetica,

                sans-serif;

            background: #f5f7fa;

            color: #172033;

        }

        a {

            text-decoration: none;

        }

        button,

        input {

            font-family: inherit;

        }

        /* =========================================================

           SIDEBAR

        ========================================================= */

        .sidebar {

            position: fixed;

            top: 0;

            left: 0;

            width: 230px;

            height: 100vh;

            padding: 24px 14px;

            display: flex;

            flex-direction: column;

            z-index: 1000;

            overflow-y: auto;

            background: #17365d;

            color: #ffffff;

        }

        .sidebar-brand {

            padding:

                4px 10px 25px;

            border-bottom:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.12

                );

            margin-bottom: 22px;

        }

        .sidebar-brand-title {

            display: block;

            color: #ffffff;

            font-size: 20px !important;

            font-weight: 700;

            line-height: 1.25;

        }

        .sidebar-brand-subtitle {

            display: block;

            margin-top: 6px;

            color:

                rgba(

                    255,

                    255,

                    255,

                    0.66

                );

            font-size: 11px !important;

            line-height: 1.45;

        }

        .sidebar-menu {

            display: flex;

            flex-direction: column;

            gap: 6px;

        }

        .sidebar-menu a {

            position: relative;

            min-height: 44px;

            padding:

                0 14px;

            display: flex;

            align-items: center;

            border-radius: 9px;

            color:

                rgba(

                    255,

                    255,

                    255,

                    0.88

                );

            font-family:

                Arial,

                Helvetica,

                sans-serif !important;

            font-size: 13px !important;

            font-weight: 500;

            line-height: 1.2;

            text-decoration: none;

            transition:

                background 0.2s ease,

                color 0.2s ease;

        }

        .sidebar-menu a:hover {

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.10

                );

            color: #ffffff;

        }

        .sidebar-menu a.active {

            background: #28558a;

            color: #ffffff;

            font-weight: 700;

        }

        .sidebar-menu a.active::before {

            content: "";

            position: absolute;

            left: 0;

            top: 9px;

            bottom: 9px;

            width: 4px;

            border-radius:

                0 8px 8px 0;

            background: #f4c400;

        }

        /* =========================================================

           USER SIDEBAR

        ========================================================= */

        .sidebar-user {

            margin-top: auto;

            padding:

                18px 10px 0;

            border-top:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.14

                );

        }

        .sidebar-user-label {

            display: block;

            color:

                rgba(

                    255,

                    255,

                    255,

                    0.58

                );

            font-family:

                Arial,

                Helvetica,

                sans-serif !important;

            font-size: 10px !important;

            line-height: 1.3;

        }

        .sidebar-user strong {

            display: block;

            margin-top: 3px;

            color: #ffffff;

            font-family:

                Arial,

                Helvetica,

                sans-serif !important;

            font-size: 13px !important;

            font-weight: 700;

            line-height: 1.4;

            overflow-wrap: anywhere;

        }

        .sidebar-logout {

            width: 100%;

            min-height: 39px;

            margin-top: 13px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #ffffff;

            color: #17365d;

            font-family:

                Arial,

                Helvetica,

                sans-serif !important;

            font-size: 12px !important;

            font-weight: 700;

            line-height: 1;

            text-decoration: none;

            transition:

                background 0.2s ease;

        }

        .sidebar-logout:hover {

            background: #e9eef5;

        }

        /* =========================================================

           MAIN

        ========================================================= */

        .main {

            margin-left: 230px;

            min-height: 100vh;

            padding: 32px;

        }

        /* =========================================================

           HEADER

        ========================================================= */

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 25px;

        }

        .page-header h1 {

            margin: 0;

            color: #17365d;

            font-size: 28px;

            line-height: 1.25;

        }

        .page-header p {

            margin-top: 7px;

            color: #6b7280;

            font-size: 13px;

            line-height: 1.6;

        }

        /* =========================================================

           ALERT

        ========================================================= */

        .alert {

            margin-bottom: 20px;

            padding:

                13px 16px;

            border-radius: 9px;

            font-size: 13px;

            line-height: 1.5;

        }

        .alert-success {

            background: #ecfdf3;

            border:

                1px solid

                #bbf7d0;

            color: #166534;

        }

        .alert-error {

            background: #fff1f2;

            border:

                1px solid

                #fecdd3;

            color: #b42318;

        }

        /* =========================================================

           CARD

        ========================================================= */

        .card {

            width: 100%;

            max-width: 820px;

            background: #ffffff;

            border:

                1px solid

                #e7ebf0;

            border-radius: 12px;

            box-shadow:

                0 4px 18px

                rgba(

                    15,

                    23,

                    42,

                    0.05

                );

            overflow: hidden;

        }

        .card-header {

            padding:

                20px 24px;

            border-bottom:

                1px solid

                #edf0f3;

        }

        .card-header h2 {

            color: #17365d;

            font-size: 17px;

            line-height: 1.4;

        }

        .card-header p {

            margin-top: 5px;

            color: #7a8492;

            font-size: 12px;

            line-height: 1.5;

        }

        .card-body {

            padding: 24px;

        }

        /* =========================================================

           FORM

        ========================================================= */

        .form-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    2,

                    minmax(

                        0,

                        1fr

                    )

                );

            gap:

                18px 20px;

        }

        .form-group {

            display: flex;

            flex-direction: column;

        }

        .form-group.full {

            grid-column: 1 / -1;

        }

        .form-group label {

            margin-bottom: 7px;

            color: #344054;

            font-size: 13px;

            font-weight: 600;

            line-height: 1.4;

        }

        .required {

            color: #b42318;

        }

        .form-group input {

            width: 100%;

            min-height: 43px;

            padding:

                0 13px;

            border:

                1px solid

                #d9dee7;

            border-radius: 8px;

            outline: none;

            background: #ffffff;

            color: #172033;

            font-size: 13px;

            transition:

                border-color 0.2s ease,

                box-shadow 0.2s ease;

        }

        .form-group input:focus {

            border-color: #28558a;

            box-shadow:

                0 0 0 3px

                rgba(

                    40,

                    85,

                    138,

                    0.10

                );

        }

        .form-help {

            margin-top: 6px;

            color: #8490a0;

            font-size: 11px;

            line-height: 1.5;

        }

        /* =========================================================

           FORM ACTION

        ========================================================= */

        .form-actions {

            margin-top: 25px;

            padding-top: 20px;

            border-top:

                1px solid

                #edf0f3;

            display: flex;

            justify-content: flex-end;

            gap: 10px;

        }

        .btn {

            min-height: 41px;

            padding:

                0 17px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: none;

            border-radius: 8px;

            cursor: pointer;

            font-size: 13px;

            font-weight: 700;

            text-decoration: none;

            transition:

                background 0.2s ease,

                transform 0.2s ease;

        }

        .btn-primary {

            background: #17365d;

            color: #ffffff;

        }

        .btn-primary:hover {

            background: #28558a;

        }

        .btn-secondary {

            background: #e9edf3;

            color: #344054;

        }

        .btn-secondary:hover {

            background: #dfe5ed;

        }

        /* =========================================================

           INFO

        ========================================================= */

        .security-note {

            margin-top: 18px;

            padding:

                14px 16px;

            border-radius: 8px;

            background: #f7f9fc;

            border:

                1px solid

                #e6eaf0;

            color: #667085;

            font-size: 11px;

            line-height: 1.6;

        }

        .security-note strong {

            color: #17365d;

        }

        /* =========================================================

           RESPONSIVE

        ========================================================= */

        @media (

            max-width: 768px

        ) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }

            .sidebar-user {

                margin-top: 25px;

            }

            .main {

                margin-left: 0;

                padding: 20px;

            }

            .page-header {

                flex-direction: column;

            }

            .form-grid {

                grid-template-columns: 1fr;

            }

            .form-group.full {

                grid-column: auto;

            }

            .form-actions {

                flex-direction: column;

            }

            .btn {

                width: 100%;

            }

        }

        /* JUMLAH DAN DAFTAR ADMIN */
        .admin-directory { margin-bottom: 24px; }
        .admin-directory .card-header {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 16px;
        }
        .admin-count { display: block; margin-top: 5px; color: #17365d; font-size: 32px; line-height: 1.25; }
        .admin-totals { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 12px; }
        .admin-pill { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #edf2f7; color: #526176; }
        .admin-pill-active { background: #e8f7ee; color: #166534; }
        .admin-pill-inactive { background: #fff1f2; color: #b42318; }
        .admin-table-wrap { width: 100%; overflow-x: auto; }
        .admin-directory table { width: 100%; min-width: 680px; border-collapse: collapse; }
        .admin-directory th { background: #f7f9fc; color: #667085; text-align: left; font-size: 12px; }
        .admin-directory th, .admin-directory td { padding: 14px 18px; border-bottom: 1px solid #edf0f3; }
        .admin-directory td { color: #344054; font-size: 13px; overflow-wrap: anywhere; }
        .admin-directory td strong { color: #17365d; }
        .admin-directory tr:last-child td { border-bottom: 0; }
        .admin-directory td:last-child { white-space: nowrap; }
        .admin-self { display: inline-block; margin-left: 5px; font-size: 11px; color: #28558a; }
        .admin-list-message { padding: 20px 24px; font-size: 13px; line-height: 1.6; color: #667085; }
        .admin-list-error { background: #fff9eb; color: #77540b; border-top: 1px solid #f1d89b; }
        .admin-directory-footer { padding: 14px 24px; background: #f7f9fc; color: #667085; font-size: 12px; }

    </style>

</head>

<body>

<!-- =========================================================

     SIDEBAR

========================================================= -->

<aside class="sidebar">

    <div class="sidebar-brand">

        <span class="sidebar-brand-title">

            Admin Layanan SDM

        </span>

        <span class="sidebar-brand-subtitle">

            Portal Layanan SDM PUSRI

        </span>

    </div>

    <nav class="sidebar-menu">

        <a href="index.php">

            Dashboard

        </a>

        <a href="tambah.php">

            + Tambah Layanan

        </a>

        <a href="kategori.php">

            Kelola Kategori

        </a>

        <a href="banner.php">

            Kelola Banner

        </a>

        <a

            href="kelola-admin.php"

            class="active"

        >

            Kelola Admin

        </a>

        <a

            href="../index.php"

            target="_blank"

            rel="noopener noreferrer"

        >

            Portal Layanan SDM

        </a>

    </nav>

    <div class="sidebar-user">

        <span class="sidebar-user-label">

            Login sebagai

        </span>

        <strong>

            <?= htmlspecialchars(

                $user["username"]

                ?? "Admin",

                ENT_QUOTES | ENT_SUBSTITUTE,

                "UTF-8"

            ) ?>

        </strong>

        <a

            href="../logout.php"

            class="sidebar-logout"

        >

            Logout

        </a>

    </div>

</aside>

<!-- =========================================================

     MAIN

========================================================= -->

<main class="main">

    <div class="page-header">

        <div>

            <h1>

                Kelola Admin

            </h1>

            <p>

                Lihat jumlah dan daftar admin, serta tambahkan akun administrator baru.

            </p>

        </div>

    </div>

    <?php if ($success !== ""): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars(

                $success,

                ENT_QUOTES | ENT_SUBSTITUTE,

                "UTF-8"

            ) ?>

        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="alert alert-error">

            <?= htmlspecialchars(

                $error,

                ENT_QUOTES | ENT_SUBSTITUTE,

                "UTF-8"

            ) ?>

        </div>

    <?php endif; ?>

    <section class="card admin-directory" id="daftar-admin" aria-labelledby="adminDirectoryTitle">
        <div class="card-header">
            <div>
                <h2 id="adminDirectoryTitle">Daftar Admin</h2>
                <?php if ($portalAdminTotal !== null): ?>
                    <strong class="admin-count"><?= (int) $portalAdminTotal ?> Admin</strong>
                    <p>Total akun administrator Portal Layanan SDM PUSRI.</p>
                    <div class="admin-totals">
                        <span class="admin-pill admin-pill-active"><?= (int) $portalAdminActive ?> Aktif</span>
                        <span class="admin-pill admin-pill-inactive"><?= (int) $portalAdminInactive ?> Tidak Aktif</span>
                    </div>
                <?php else: ?>
                    <strong class="admin-count">— Admin</strong>
                    <p>Jumlah admin belum tersedia.</p>
                <?php endif; ?>
            </div>
            <a class="btn btn-primary" href="#tambah-admin">+ Tambah Admin</a>
        </div>
        <?php if ($portalAdminListError !== ""): ?>
            <div class="admin-list-message admin-list-error" role="alert">
                <?= portalAdminEscape($portalAdminListError) ?>
                <br><a href="kelola-admin.php#daftar-admin">Muat ulang daftar admin</a>
            </div>
        <?php elseif ($portalAdminTotal === 0): ?>
            <p class="admin-list-message">Belum ada akun admin yang terdaftar.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table aria-labelledby="adminDirectoryTitle">
                    <thead><tr>
                        <th scope="col">No</th><th scope="col">Username</th>
                        <th scope="col">Nama Lengkap</th><th scope="col">Email</th>
                        <th scope="col">Status Akun</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($portalAdminList as $listIndex => $listedAdmin): ?>
                        <?php
                        $listedName = trim((string) ($listedAdmin["firstName"] ?? "") . " "
                            . (string) ($listedAdmin["lastName"] ?? ""));
                        $listedEmail = trim((string) ($listedAdmin["email"] ?? ""));
                        $listedEnabled = $listedAdmin["enabled"] ?? null;
                        $listedIsSelf = ($user["username"] ?? null) === $listedAdmin["username"];
                        ?>
                        <tr>
                            <td><?= (int) ($listIndex + 1) ?></td>
                            <td><strong><?= portalAdminEscape($listedAdmin["username"]) ?></strong>
                                <?php if ($listedIsSelf): ?><span class="admin-self">(Anda)</span><?php endif; ?>
                            </td>
                            <td><?= portalAdminEscape($listedName !== "" ? $listedName : "—") ?></td>
                            <td><?= portalAdminEscape($listedEmail !== "" ? $listedEmail : "—") ?></td>
                            <td>
                                <?php if ($listedEnabled === true): ?>
                                    <span class="admin-pill admin-pill-active">Aktif</span>
                                <?php elseif ($listedEnabled === false): ?>
                                    <span class="admin-pill admin-pill-inactive">Tidak Aktif</span>
                                <?php else: ?>
                                    <span class="admin-pill">Tidak diketahui</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if ($portalAdminTotal !== null): ?>
            <div class="admin-directory-footer"><?= (int) $portalAdminTotal ?> akun admin terdaftar. Status Aktif berarti akun diaktifkan, bukan sedang online.</div>
        <?php endif; ?>
    </section>

    <div class="card" id="tambah-admin">

        <div class="card-header">

            <h2>

                Tambah Admin

            </h2>

            <p>

                Akun yang dibuat melalui form ini otomatis diberi Realm Role

                <strong>ADMIN</strong> di Keycloak.

            </p>

        </div>

        <div class="card-body">

            <form

                method="POST"

                action="kelola-admin.php"

                autocomplete="off"

            >

                <?= csrfField() ?>

                <div class="form-grid">

                    <!-- USERNAME -->

                    <div class="form-group">

                        <label for="username">

                            Username

                            <span class="required">

                                *

                            </span>

                        </label>

                        <input

                            type="text"

                            id="username"

                            name="username"

                            value="<?= htmlspecialchars(

                                $_POST["username"]

                                ?? "",

                                ENT_QUOTES | ENT_SUBSTITUTE,

                                "UTF-8"

                            ) ?>"

                            maxlength="50"

                            required

                            autocomplete="off"

                        >

                        <span class="form-help">

                            3–50 karakter. Gunakan huruf, angka, titik, garis bawah, atau tanda minus.

                        </span>

                    </div>

                    <!-- EMAIL -->

                    <div class="form-group">

                        <label for="email">

                            Email

                            <span class="required">

                                *

                            </span>

                        </label>

                        <input

                            type="email"

                            id="email"

                            name="email"

                            value="<?= htmlspecialchars(

                                $_POST["email"]

                                ?? "",

                                ENT_QUOTES | ENT_SUBSTITUTE,

                                "UTF-8"

                            ) ?>"

                            maxlength="255"

                            required

                            autocomplete="off"

                        >

                    </div>

                    <!-- NAMA DEPAN -->

                    <div class="form-group">

                        <label for="first_name">

                            Nama Depan

                            <span class="required">

                                *

                            </span>

                        </label>

                        <input

                            type="text"

                            id="first_name"

                            name="first_name"

                            value="<?= htmlspecialchars(

                                $_POST["first_name"]

                                ?? "",

                                ENT_QUOTES | ENT_SUBSTITUTE,

                                "UTF-8"

                            ) ?>"

                            maxlength="100"

                            required

                        >

                    </div>

                    <!-- NAMA BELAKANG -->

                    <div class="form-group">

                        <label for="last_name">

                            Nama Belakang

                        </label>

                        <input

                            type="text"

                            id="last_name"

                            name="last_name"

                            value="<?= htmlspecialchars(

                                $_POST["last_name"]

                                ?? "",

                                ENT_QUOTES | ENT_SUBSTITUTE,

                                "UTF-8"

                            ) ?>"

                            maxlength="100"

                        >

                    </div>

                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label for="password">

                            Password

                            <span class="required">

                                *

                            </span>

                        </label>

                        <input

                            type="password"

                            id="password"

                            name="password"

                            minlength="8"

                            required

                            autocomplete="new-password"

                        >

                        <span class="form-help">

                            Minimal 8 karakter.

                        </span>

                    </div>

                    <!-- KONFIRMASI PASSWORD -->

                    <div class="form-group">

                        <label for="password_confirm">

                            Konfirmasi Password

                            <span class="required">

                                *

                            </span>

                        </label>

                        <input

                            type="password"

                            id="password_confirm"

                            name="password_confirm"

                            minlength="8"

                            required

                            autocomplete="new-password"

                        >

                    </div>

                </div>

                <div class="security-note">

                    <strong>Catatan keamanan:</strong>

                    akun baru akan dibuat langsung di Keycloak dan diberi

                    Realm Role <strong>ADMIN</strong>. Password tidak disimpan

                    di database Portal Layanan SDM.

                </div>

                <div class="form-actions">

                    <a

                        href="index.php"

                        class="btn btn-secondary"

                    >

                        Kembali

                    </a>

                    <button

                        type="submit"

                        class="btn btn-primary"

                    >

                        + Tambah Admin

                    </button>

                </div>

            </form>

        </div>

    </div>

</main>

</body>

</html>