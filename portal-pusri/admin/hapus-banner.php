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
        "Location: /portal-pusri/admin/banner.php",
        true,
        302
    );

    exit;
}


$id =
    (int)
    $idInput;


if (
    $id <= 0
) {

    header(
        "Location: /portal-pusri/admin/banner.php",
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL OBJECT KEY GAMBAR BANNER
|--------------------------------------------------------------------------
|
| Object key harus diketahui sebelum record database dihapus.
|
*/

$image =
    null;


try {

    /*
    |--------------------------------------------------------------------------
    | PREPARED SELECT
    |--------------------------------------------------------------------------
    */

    $stmt =
        $conn->prepare(
            "
            SELECT
                image
            FROM banners
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

    $databaseImage =
        null;


    $stmt->bind_result(
        $databaseImage
    );


    /*
    |--------------------------------------------------------------------------
    | BANNER TIDAK DITEMUKAN
    |--------------------------------------------------------------------------
    */

    if (
        !$stmt->fetch()
    ) {

        $stmt->close();


        header(
            "Location: /portal-pusri/admin/banner.php",
            true,
            302
        );

        exit;
    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | SIMPAN OBJECT KEY
    |--------------------------------------------------------------------------
    */

    if (
        is_string(
            $databaseImage
        )
        &&
        trim(
            $databaseImage
        ) !== ""
    ) {

        $image =
            trim(
                $databaseImage
            );
    }


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DETAIL ERROR HANYA MASUK LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        "Portal Layanan SDM PUSRI Delete Banner Load Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    http_response_code(
        500
    );


    die(
        "Data banner tidak dapat diproses."
    );
}


/*
|--------------------------------------------------------------------------
| HAPUS BANNER DARI DATABASE
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | PREPARED DELETE
    |--------------------------------------------------------------------------
    */

    $stmtDelete =
        $conn->prepare(
            "
            DELETE FROM banners
            WHERE id = ?
            "
        );


    $stmtDelete->bind_param(
        "i",
        $id
    );


    /*
    |--------------------------------------------------------------------------
    | EXECUTE
    |--------------------------------------------------------------------------
    */

    $stmtDelete->execute();


    /*
    |--------------------------------------------------------------------------
    | CEK ROW YANG TERHAPUS
    |--------------------------------------------------------------------------
    */

    $affectedRows =
        $stmtDelete->affected_rows;


    $stmtDelete->close();


    /*
    |--------------------------------------------------------------------------
    | TIDAK ADA DATA YANG TERHAPUS
    |--------------------------------------------------------------------------
    |
    | Bisa terjadi jika record sudah dihapus oleh request lain.
    |--------------------------------------------------------------------------
    */

    if (
        $affectedRows !== 1
    ) {

        header(
            "Location: /portal-pusri/admin/banner.php",
            true,
            302
        );

        exit;
    }


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE GAGAL
    |--------------------------------------------------------------------------
    |
    | Jangan hapus file MinIO jika database gagal.
    |--------------------------------------------------------------------------
    */

    error_log(
        "Portal Layanan SDM PUSRI Delete Banner Database Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    http_response_code(
        500
    );


    die(
        "Banner gagal dihapus. Silakan coba kembali."
    );
}


/*
|--------------------------------------------------------------------------
| HAPUS GAMBAR DARI MINIO
|--------------------------------------------------------------------------
|
| Hanya setelah DELETE database benar-benar berhasil.
|
*/

if (
    !empty(
        $image
    )
) {

    try {

        deleteFromMinio(
            $s3,
            $minioBucket,
            $image
        );


    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | Database sudah berhasil dihapus.
        |
        | Jangan membatalkan proses hanya karena cleanup MinIO gagal.
        | Detail disimpan ke log untuk dibersihkan kemudian.
        |--------------------------------------------------------------------------
        */

        error_log(
            "Portal Layanan SDM PUSRI Delete Banner MinIO Cleanup Error ID "
            . $id
            . ": "
            . get_class($e)
            . ": "
            . $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| BERHASIL
|--------------------------------------------------------------------------
*/

header(
    "Location: /portal-pusri/admin/banner.php?success=hapus",
    true,
    302
);

exit;