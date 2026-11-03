<?php

/*
|--------------------------------------------------------------------------
| COMPOSER AUTOLOAD
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../vendor/autoload.php";


use Aws\S3\S3Client;


/*
|--------------------------------------------------------------------------
| KONFIGURASI MINIO
|--------------------------------------------------------------------------
|
| Endpoint dan bucket bukan credential rahasia.
|
*/

$minioEndpoint =
    "http://127.0.0.1:9000";

$minioBucket =
    "portal-pusri";


/*
|--------------------------------------------------------------------------
| FILE SECRET
|--------------------------------------------------------------------------
|
| Credential MinIO disimpan di luar web root:
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
| AMBIL CREDENTIAL MINIO
|--------------------------------------------------------------------------
*/

$minioAccessKey =
    $secrets["minio_access_key"]
    ?? null;

$minioSecretKey =
    $secrets["minio_secret_key"]
    ?? null;


/*
|--------------------------------------------------------------------------
| VALIDASI ACCESS KEY
|--------------------------------------------------------------------------
*/

if (
    !is_string($minioAccessKey)
    ||
    trim($minioAccessKey) === ""
) {

    throw new RuntimeException(
        "MinIO Access Key belum dikonfigurasi."
    );
}


/*
|--------------------------------------------------------------------------
| VALIDASI SECRET KEY
|--------------------------------------------------------------------------
*/

if (
    !is_string($minioSecretKey)
    ||
    $minioSecretKey === ""
) {

    throw new RuntimeException(
        "MinIO Secret Key belum dikonfigurasi."
    );
}


$minioAccessKey =
    trim($minioAccessKey);


/*
|--------------------------------------------------------------------------
| CLIENT MINIO
|--------------------------------------------------------------------------
*/

$s3 =
    new S3Client([

        "version" =>
            "latest",

        "region" =>
            "us-east-1",

        "endpoint" =>
            $minioEndpoint,

        "use_path_style_endpoint" =>
            true,

        "credentials" => [

            "key" =>
                $minioAccessKey,

            "secret" =>
                $minioSecretKey

        ]

    ]);