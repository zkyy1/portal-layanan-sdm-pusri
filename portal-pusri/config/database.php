<?php

/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION - PORTAL PUSRI
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| FILE SECRET
|--------------------------------------------------------------------------
|
| Credential database disimpan di luar web root:
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
| AMBIL KONFIGURASI MYSQL
|--------------------------------------------------------------------------
*/

$host =
    $secrets["mysql_host"]
    ?? null;

$username =
    $secrets["mysql_username"]
    ?? null;

$password =
    $secrets["mysql_password"]
    ?? null;

$database =
    $secrets["mysql_database"]
    ?? null;


/*
|--------------------------------------------------------------------------
| VALIDASI TIPE KONFIGURASI
|--------------------------------------------------------------------------
*/

if (
    !is_string($host)
    ||
    !is_string($username)
    ||
    !is_string($password)
    ||
    !is_string($database)
) {

    throw new RuntimeException(
        "Konfigurasi database Portal PUSRI tidak valid."
    );
}


/*
|--------------------------------------------------------------------------
| NORMALISASI NILAI
|--------------------------------------------------------------------------
*/

$host =
    trim($host);

$username =
    trim($username);

$database =
    trim($database);


/*
|--------------------------------------------------------------------------
| VALIDASI KONFIGURASI WAJIB
|--------------------------------------------------------------------------
*/

if (
    $host === ""
    ||
    $username === ""
    ||
    $password === ""
    ||
    $database === ""
) {

    throw new RuntimeException(
        "Konfigurasi database Portal PUSRI belum lengkap."
    );
}


/*
|--------------------------------------------------------------------------
| LARANG DATABASE USER ROOT
|--------------------------------------------------------------------------
|
| Aplikasi Portal PUSRI harus menggunakan user database khusus
| dengan hak minimum.
|
| Saat ini:
|
| portal_pusri_user
|
*/

if (
    strtolower($username)
    === "root"
) {

    throw new RuntimeException(
        "Portal PUSRI tidak diizinkan menggunakan akun database root."
    );
}


/*
|--------------------------------------------------------------------------
| MYSQLI ERROR MODE
|--------------------------------------------------------------------------
|
| Gunakan exception agar seluruh kegagalan koneksi/query level koneksi
| dapat ditangani secara konsisten.
|
*/

mysqli_report(
    MYSQLI_REPORT_ERROR
    |
    MYSQLI_REPORT_STRICT
);


/*
|--------------------------------------------------------------------------
| KONEKSI DATABASE
|--------------------------------------------------------------------------
*/

try {

    $conn =
        new mysqli(
            $host,
            $username,
            $password,
            $database
        );


    /*
    |--------------------------------------------------------------------------
    | CHARACTER SET
    |--------------------------------------------------------------------------
    */

    $conn->set_charset(
        "utf8mb4"
    );


} catch (mysqli_sql_exception $e) {

    /*
    |--------------------------------------------------------------------------
    | LOG DETAIL HANYA UNTUK ADMIN / DEVELOPER
    |--------------------------------------------------------------------------
    |
    | Password tidak pernah ditulis ke log.
    |
    */

    error_log(
        "Portal PUSRI Database Error: "
        . $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | RESPONSE AMAN KE USER
    |--------------------------------------------------------------------------
    */

    http_response_code(
        500
    );

    die(
        "Layanan database Portal PUSRI sedang tidak tersedia."
    );
}