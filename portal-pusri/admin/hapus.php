<?php

/*
|--------------------------------------------------------------------------
| PROTEKSI ADMIN KEYCLOAK
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/auth.php";

requireRole("ADMIN");


/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/csrf.php";


/*
|--------------------------------------------------------------------------
| DATABASE + MINIO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/minio.php";
require_once __DIR__ . "/../includes/minio-functions.php";


/*
|--------------------------------------------------------------------------
| HANYA IZINKAN METHOD POST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    !== "POST"
) {

    header(
        "Allow: POST"
    );

    http_response_code(
        405
    );

    die(
        "405 - Method Not Allowed"
    );
}


/*
|--------------------------------------------------------------------------
| VALIDASI CSRF
|--------------------------------------------------------------------------
*/

requireCsrf();


/*
|--------------------------------------------------------------------------
| VALIDASI ID
|--------------------------------------------------------------------------
*/

$idInput =
    $_POST["id"]
    ?? null;


if (
    $idInput === null
    ||
    filter_var(
        $idInput,
        FILTER_VALIDATE_INT
    ) === false
) {

    header(
        "Location: /portal-pusri/admin/index.php",
        true,
        302
    );

    exit;
}


$id =
    (int) $idInput;


if (
    $id <= 0
) {

    header(
        "Location: /portal-pusri/admin/index.php",
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL OBJECT KEY ICON
|--------------------------------------------------------------------------
|
| Icon harus diketahui sebelum record database dihapus.
|
*/

$icon =
    null;


try {

    /*
    |--------------------------------------------------------------------------
    | PREPARED STATEMENT
    |--------------------------------------------------------------------------
    */

    $stmt =
        $conn->prepare(
            "
            SELECT
                icon
            FROM applications
            WHERE id = ?
            LIMIT 1
            "
        );


    $stmt->bind_param(
        "i",
        $id
    );


    $stmt->execute();


    /*
    |--------------------------------------------------------------------------
    | BIND RESULT
    |--------------------------------------------------------------------------
    */

    $databaseIcon =
        null;


    $stmt->bind_result(
        $databaseIcon
    );


    /*
    |--------------------------------------------------------------------------
    | DATA TIDAK DITEMUKAN
    |--------------------------------------------------------------------------
    */

    if (
        !$stmt->fetch()
    ) {

        $stmt->close();


        header(
            "Location: /portal-pusri/admin/index.php",
            true,
            302
        );

        exit;
    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | SIMPAN OBJECT KEY ICON
    |--------------------------------------------------------------------------
    */

    if (
        is_string(
            $databaseIcon
        )
        &&
        trim(
            $databaseIcon
        ) !== ""
    ) {

        $icon =
            trim(
                $databaseIcon
            );
    }


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DETAIL ERROR HANYA MASUK LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        "Portal Layanan SDM PUSRI Delete Application Load Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    http_response_code(
        500
    );


    die(
        "Data layanan tidak dapat diproses."
    );
}


/*
|--------------------------------------------------------------------------
| HAPUS DATA DARI DATABASE
|--------------------------------------------------------------------------
*/

try {

    $stmtDelete =
        $conn->prepare(
            "
            DELETE FROM applications
            WHERE id = ?
            "
        );


    $stmtDelete->bind_param(
        "i",
        $id
    );


    /*
    |--------------------------------------------------------------------------
    | EXECUTE DELETE
    |--------------------------------------------------------------------------
    */

    $stmtDelete->execute();


    /*
    |--------------------------------------------------------------------------
    | PASTIKAN RECORD BENAR-BENAR TERHAPUS
    |--------------------------------------------------------------------------
    */

    $affectedRows =
        $stmtDelete->affected_rows;


    $stmtDelete->close();


    if (
        $affectedRows < 1
    ) {

        /*
        |--------------------------------------------------------------------------
        | Record mungkin sudah dihapus dari request lain.
        |--------------------------------------------------------------------------
        */

        header(
            "Location: /portal-pusri/admin/index.php",
            true,
            302
        );

        exit;
    }


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | JANGAN TAMPILKAN DETAIL DATABASE KE BROWSER
    |--------------------------------------------------------------------------
    */

    error_log(
        "Portal Layanan SDM PUSRI Delete Application Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    http_response_code(
        500
    );


    die(
        "Layanan gagal dihapus. Silakan coba kembali."
    );
}


/*
|--------------------------------------------------------------------------
| HAPUS ICON DARI MINIO
|--------------------------------------------------------------------------
|
| Hanya dilakukan setelah database benar-benar berhasil dihapus.
|
| Jika MinIO gagal:
|
| - data database tetap dianggap sudah berhasil dihapus
| - error MinIO dicatat ke log
| - user tetap kembali ke dashboard
|
*/

if (
    !empty(
        $icon
    )
) {

    try {

        deleteFromMinio(
            $s3,
            $minioBucket,
            $icon
        );


    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | Jangan batalkan penghapusan database.
        |--------------------------------------------------------------------------
        */

        error_log(
            "Portal Layanan SDM PUSRI Delete Application MinIO Cleanup Error: "
            . get_class($e)
            . ": "
            . $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| KEMBALI KE DASHBOARD
|--------------------------------------------------------------------------
*/

header(
    "Location: /portal-pusri/admin/index.php?success=hapus",
    true,
    302
);

exit;