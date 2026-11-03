<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| KEYCLOAK ADMIN REST API
|--------------------------------------------------------------------------
|
| File ini digunakan Portal PUSRI untuk melakukan pengelolaan
| administrator melalui Keycloak Admin REST API.
|
| Client yang digunakan:
|
| portal-pusri-admin-api
|
*/


/*
|--------------------------------------------------------------------------
| KONFIGURASI DASAR KEYCLOAK
|--------------------------------------------------------------------------
*/

$keycloakAdminBaseUrl =
    "http://localhost:8080";


$keycloakAdminRealm =
    "pusri";


/*
|--------------------------------------------------------------------------
| FILE SECRET
|--------------------------------------------------------------------------
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
| VALIDASI SECRET
|--------------------------------------------------------------------------
*/

if (
    !is_array($secrets)
) {

    throw new RuntimeException(
        "Konfigurasi secret Portal PUSRI tidak valid."
    );
}


/*
|--------------------------------------------------------------------------
| ADMIN CLIENT ID
|--------------------------------------------------------------------------
*/

$keycloakAdminClientId =
    $secrets["keycloak_admin_client_id"]
    ?? null;


/*
|--------------------------------------------------------------------------
| ADMIN CLIENT SECRET
|--------------------------------------------------------------------------
*/

$keycloakAdminClientSecret =
    $secrets["keycloak_admin_client_secret"]
    ?? null;


/*
|--------------------------------------------------------------------------
| VALIDASI CLIENT ID
|--------------------------------------------------------------------------
*/

if (
    !is_string($keycloakAdminClientId)
    ||
    trim($keycloakAdminClientId) === ""
) {

    throw new RuntimeException(
        "Keycloak Admin Client ID belum dikonfigurasi."
    );
}


/*
|--------------------------------------------------------------------------
| VALIDASI CLIENT SECRET
|--------------------------------------------------------------------------
*/

if (
    !is_string($keycloakAdminClientSecret)
    ||
    trim($keycloakAdminClientSecret) === ""
) {

    throw new RuntimeException(
        "Keycloak Admin Client Secret belum dikonfigurasi."
    );
}


/*
|--------------------------------------------------------------------------
| NORMALISASI
|--------------------------------------------------------------------------
*/

$keycloakAdminClientId =
    trim(
        $keycloakAdminClientId
    );


$keycloakAdminClientSecret =
    trim(
        $keycloakAdminClientSecret
    );


$keycloakAdminBaseUrl =
    rtrim(
        $keycloakAdminBaseUrl,
        "/"
    );


$keycloakAdminRealm =
    trim(
        $keycloakAdminRealm
    );


if (
    $keycloakAdminRealm === ""
) {

    throw new RuntimeException(
        "Keycloak Admin Realm belum dikonfigurasi."
    );
}


/*
|--------------------------------------------------------------------------
| AMBIL ACCESS TOKEN SERVICE ACCOUNT
|--------------------------------------------------------------------------
*/

function keycloakAdminGetAccessToken(): string
{

    static $accessToken =
        null;


    /*
    |--------------------------------------------------------------------------
    | GUNAKAN TOKEN YANG SUDAH ADA
    |--------------------------------------------------------------------------
    */

    if (
        is_string($accessToken)
        &&
        trim($accessToken) !== ""
    ) {

        return $accessToken;
    }


    global
        $keycloakAdminBaseUrl,
        $keycloakAdminRealm,
        $keycloakAdminClientId,
        $keycloakAdminClientSecret;


    /*
    |--------------------------------------------------------------------------
    | TOKEN URL
    |--------------------------------------------------------------------------
    */

    $tokenUrl =
        $keycloakAdminBaseUrl
        .
        "/realms/"
        .
        rawurlencode(
            $keycloakAdminRealm
        )
        .
        "/protocol/openid-connect/token";


    /*
    |--------------------------------------------------------------------------
    | CURL
    |--------------------------------------------------------------------------
    */

    $curl =
        curl_init(
            $tokenUrl
        );


    if (
        $curl === false
    ) {

        throw new RuntimeException(
            "cURL Keycloak tidak dapat dibuat."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST TOKEN
    |--------------------------------------------------------------------------
    */

    curl_setopt_array(
        $curl,
        [

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_POST =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                10,

            CURLOPT_TIMEOUT =>
                30,

            CURLOPT_HTTPHEADER => [

                "Accept: application/json",

                "Content-Type: application/x-www-form-urlencoded"

            ],

            CURLOPT_POSTFIELDS =>
                http_build_query(
                    [

                        "grant_type" =>
                            "client_credentials",

                        "client_id" =>
                            $keycloakAdminClientId,

                        "client_secret" =>
                            $keycloakAdminClientSecret

                    ]
                )

        ]
    );


    /*
    |--------------------------------------------------------------------------
    | EXECUTE
    |--------------------------------------------------------------------------
    */

    $response =
        curl_exec(
            $curl
        );


    $curlError =
        curl_error(
            $curl
        );


    $httpCode =
        (int)
        curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );


    curl_close(
        $curl
    );


    /*
    |--------------------------------------------------------------------------
    | CURL ERROR
    |--------------------------------------------------------------------------
    */

    if (
        $response === false
    ) {

        throw new RuntimeException(
            "Gagal meminta access token Keycloak."
            .
            (
                $curlError !== ""
                    ? " " . $curlError
                    : ""
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DECODE JSON
    |--------------------------------------------------------------------------
    */

    $data =
        json_decode(
            $response,
            true
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDASI RESPONSE
    |--------------------------------------------------------------------------
    */

    if (
        $httpCode < 200
        ||
        $httpCode >= 300
        ||
        !is_array($data)
        ||
        !isset(
            $data["access_token"]
        )
        ||
        !is_string(
            $data["access_token"]
        )
    ) {

        throw new RuntimeException(
            "Keycloak tidak memberikan access token Service Account."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN TOKEN
    |--------------------------------------------------------------------------
    */

    $accessToken =
        trim(
            $data["access_token"]
        );


    if (
        $accessToken === ""
    ) {

        throw new RuntimeException(
            "Access token Keycloak kosong."
        );
    }


    return $accessToken;
}


/*
|--------------------------------------------------------------------------
| REQUEST KE KEYCLOAK ADMIN REST API
|--------------------------------------------------------------------------
*/

function keycloakAdminRequest(
    string $method,
    string $path,
    ?array $jsonBody = null
): array {

    global
        $keycloakAdminBaseUrl;


    /*
    |--------------------------------------------------------------------------
    | ACCESS TOKEN
    |--------------------------------------------------------------------------
    */

    $accessToken =
        keycloakAdminGetAccessToken();


    /*
    |--------------------------------------------------------------------------
    | URL
    |--------------------------------------------------------------------------
    */

    $url =
        $keycloakAdminBaseUrl
        .
        $path;


    /*
    |--------------------------------------------------------------------------
    | CURL
    |--------------------------------------------------------------------------
    */

    $curl =
        curl_init(
            $url
        );


    if (
        $curl === false
    ) {

        throw new RuntimeException(
            "cURL Keycloak tidak dapat dibuat."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HEADER
    |--------------------------------------------------------------------------
    */

    $headers = [

        "Accept: application/json",

        "Authorization: Bearer "
        .
        $accessToken

    ];


    /*
    |--------------------------------------------------------------------------
    | CURL OPTIONS
    |--------------------------------------------------------------------------
    */

    $options = [

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CUSTOMREQUEST =>
            strtoupper(
                $method
            ),

        CURLOPT_CONNECTTIMEOUT =>
            10,

        CURLOPT_TIMEOUT =>
            30,

        CURLOPT_HEADER =>
            true,

        CURLOPT_HTTPHEADER =>
            $headers

    ];


    /*
    |--------------------------------------------------------------------------
    | JSON BODY
    |--------------------------------------------------------------------------
    */

    if (
        $jsonBody !== null
    ) {

        $options[
            CURLOPT_HTTPHEADER
        ][] =
            "Content-Type: application/json";


        $options[
            CURLOPT_POSTFIELDS
        ] =
            json_encode(
                $jsonBody,
                JSON_UNESCAPED_UNICODE
                |
                JSON_UNESCAPED_SLASHES
                |
                JSON_THROW_ON_ERROR
            );
    }


    /*
    |--------------------------------------------------------------------------
    | SET CURL
    |--------------------------------------------------------------------------
    */

    curl_setopt_array(
        $curl,
        $options
    );


    /*
    |--------------------------------------------------------------------------
    | EXECUTE
    |--------------------------------------------------------------------------
    */

    $response =
        curl_exec(
            $curl
        );


    $curlError =
        curl_error(
            $curl
        );


    $httpCode =
        (int)
        curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );


    $headerSize =
        (int)
        curl_getinfo(
            $curl,
            CURLINFO_HEADER_SIZE
        );


    curl_close(
        $curl
    );


    /*
    |--------------------------------------------------------------------------
    | CURL ERROR
    |--------------------------------------------------------------------------
    */

    if (
        $response === false
    ) {

        throw new RuntimeException(
            "Request ke Keycloak gagal."
            .
            (
                $curlError !== ""
                    ? " " . $curlError
                    : ""
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PISAH HEADER DAN BODY
    |--------------------------------------------------------------------------
    */

    $rawHeaders =
        substr(
            $response,
            0,
            $headerSize
        );


    $rawBody =
        substr(
            $response,
            $headerSize
        );


    /*
    |--------------------------------------------------------------------------
    | PARSE HEADER
    |--------------------------------------------------------------------------
    */

    $headersMap =
        [];


    foreach (
        preg_split(
            "/\r\n|\n|\r/",
            trim(
                $rawHeaders
            )
        ) as $headerLine
    ) {

        if (
            !is_string($headerLine)
            ||
            !str_contains(
                $headerLine,
                ":"
            )
        ) {

            continue;
        }


        [
            $headerName,
            $headerValue
        ] =
            explode(
                ":",
                $headerLine,
                2
            );


        $headersMap[
            strtolower(
                trim(
                    $headerName
                )
            )
        ] =
            trim(
                $headerValue
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DECODE JSON
    |--------------------------------------------------------------------------
    */

    $decodedBody =
        null;


    if (
        trim(
            $rawBody
        ) !== ""
    ) {

        $decodedBody =
            json_decode(
                $rawBody,
                true
            );
    }


    /*
    |--------------------------------------------------------------------------
    | RETURN RESPONSE
    |--------------------------------------------------------------------------
    */

    return [

        "status" =>
            $httpCode,

        "headers" =>
            $headersMap,

        "body" =>
            $rawBody,

        "json" =>
            $decodedBody

    ];
}