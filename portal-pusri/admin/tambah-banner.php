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
| ERROR
|--------------------------------------------------------------------------
*/

$error = "";


/*
|--------------------------------------------------------------------------
| HELPER PANJANG TEKS
|--------------------------------------------------------------------------
*/

function bannerTextLength(string $value): int
{
    if (function_exists("mb_strlen")) {
        return mb_strlen($value, "UTF-8");
    }

    return strlen($value);
}


/*
|--------------------------------------------------------------------------
| PROSES TAMBAH BANNER
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | VALIDASI CSRF
    |--------------------------------------------------------------------------
    */

    requireCsrf();


    /*
    |--------------------------------------------------------------------------
    | DATA FORM
    |--------------------------------------------------------------------------
    */

    $title =
        trim(
            (string) (
                $_POST["title"]
                ?? ""
            )
        );


    $subtitle =
        trim(
            (string) (
                $_POST["subtitle"]
                ?? ""
            )
        );


    $sortOrderInput =
        $_POST["sort_order"]
        ?? "0";


    $is_active =
        isset($_POST["is_active"])
            ? 1
            : 0;


    /*
    |--------------------------------------------------------------------------
    | DEFAULT
    |--------------------------------------------------------------------------
    */

    $sort_order = 0;

    $image = null;


    /*
    |--------------------------------------------------------------------------
    | VALIDASI JUDUL
    |--------------------------------------------------------------------------
    */

    if ($title === "") {

        $error =
            "Judul banner wajib diisi.";

    } elseif (
        bannerTextLength($title) > 255
    ) {

        $error =
            "Judul banner maksimal 255 karakter.";
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI SUBTITLE
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
        &&
        bannerTextLength($subtitle) > 5000
    ) {

        $error =
            "Deskripsi banner terlalu panjang.";
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI SORT ORDER
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if (
            filter_var(
                $sortOrderInput,
                FILTER_VALIDATE_INT
            ) === false
        ) {

            $error =
                "Urutan banner harus berupa angka bulat.";

        } else {

            $sort_order =
                (int) $sortOrderInput;


            if (
                $sort_order < 0
                ||
                $sort_order > 999999
            ) {

                $error =
                    "Urutan banner tidak valid.";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI GAMBAR WAJIB
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if (
            !isset($_FILES["image"])
            ||
            (
                $_FILES["image"]["error"]
                ?? UPLOAD_ERR_NO_FILE
            ) === UPLOAD_ERR_NO_FILE
        ) {

            $error =
                "Gambar banner wajib dipilih.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPLOAD GAMBAR KE MINIO
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        try {

            $image =
                uploadImageToMinio(
                    $s3,
                    $minioBucket,
                    $_FILES["image"],
                    "banners"
                );

        } catch (RuntimeException $e) {

            $error =
                $e->getMessage();

        } catch (Throwable $e) {

            error_log(
                "Portal Layanan SDM PUSRI Banner Upload Error: "
                . get_class($e)
                . ": "
                . $e->getMessage()
            );


            $error =
                "Gambar banner gagal diproses.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT DATABASE
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
        &&
        !empty($image)
    ) {

        try {

            $sql = "
                INSERT INTO banners
                (
                    title,
                    subtitle,
                    image,
                    is_active,
                    sort_order
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";


            $stmt =
                $conn->prepare(
                    $sql
                );


            $stmt->bind_param(
                "sssii",
                $title,
                $subtitle,
                $image,
                $is_active,
                $sort_order
            );


            $stmt->execute();

            $stmt->close();


            header(
                "Location: /portal-pusri/admin/banner.php?success=tambah",
                true,
                302
            );

            exit;

        } catch (Throwable $e) {

            error_log(
                "Portal Layanan SDM PUSRI Add Banner Database Error: "
                . get_class($e)
                . ": "
                . $e->getMessage()
            );


            /*
            |--------------------------------------------------------------------------
            | CLEANUP GAMBAR MINIO
            |--------------------------------------------------------------------------
            */

            if (!empty($image)) {

                try {

                    deleteFromMinio(
                        $s3,
                        $minioBucket,
                        $image
                    );

                } catch (Throwable $deleteException) {

                    error_log(
                        "Portal Layanan SDM PUSRI Add Banner MinIO Cleanup Error: "
                        . get_class($deleteException)
                        . ": "
                        . $deleteException->getMessage()
                    );
                }
            }


            $error =
                "Banner gagal disimpan. Silakan coba kembali.";
        }
    }
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
        Tambah Banner - Admin Layanan SDM
    </title>


    <style>

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

            background:
                #f5f7fa;

            color:
                #172033;
        }


        a {
            text-decoration: none;
        }


        /* =====================================================
           SIDEBAR SERAGAM - ADMIN LAYANAN SDM
        ===================================================== */

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
            padding: 4px 10px 25px;

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

            font-size: 20px;

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

            font-size: 11px;

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

            padding: 0 14px;

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

            font-size: 13px;

            font-weight: 500;

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
                0
                8px
                8px
                0;

            background: #f4c400;
        }


        .sidebar-user {
            margin-top: auto;

            padding: 18px 10px 0;

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

            font-size: 10px;
        }


        .sidebar-user strong {
            display: block;

            margin-top: 3px;

            color: #ffffff;

            font-size: 13px;

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

            font-size: 12px;

            font-weight: 700;

            transition:
                background 0.2s ease;
        }


        .sidebar-logout:hover {
            background: #e9eef5;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            margin-left: 230px;

            padding: 30px;

            min-height: 100vh;
        }


        .page-header {
            margin-bottom: 25px;
        }


        .page-header h1 {
            font-size: 28px;

            margin-bottom: 5px;
        }


        .page-header p {
            color: #6b7280;

            font-size: 14px;
        }


        /* =====================================================
           INFO
        ===================================================== */

        .info-box {
            max-width: 850px;

            padding: 14px 16px;

            margin-bottom: 20px;

            border:
                1px solid
                #b6d4fe;

            border-radius: 8px;

            background: #e7f1ff;

            color: #084298;

            font-size: 13px;

            line-height: 1.6;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .alert-error {
            max-width: 850px;

            padding: 14px 16px;

            margin-bottom: 20px;

            border:
                1px solid
                #f5c2c7;

            border-radius: 8px;

            background: #f8d7da;

            color: #842029;

            font-size: 14px;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .card {
            max-width: 850px;

            padding: 30px;

            background: #ffffff;

            border:
                1px solid
                #e7ebf0;

            border-radius: 14px;

            box-shadow:
                0
                5px
                20px
                rgba(
                    0,
                    0,
                    0,
                    0.05
                );
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {
            margin-bottom: 22px;
        }


        .form-group label {
            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 700;
        }


        .required {
            color: #dc3545;
        }


        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea {
            width: 100%;

            padding: 12px 14px;

            border:
                1px solid
                #d9dee5;

            border-radius: 8px;

            background: #ffffff;

            outline: none;

            font-size: 14px;
        }


        input:focus,
        textarea:focus {
            border-color: #28558a;

            box-shadow:
                0
                0
                0
                3px
                rgba(
                    40,
                    85,
                    138,
                    0.08
                );
        }


        textarea {
            min-height: 100px;

            resize: vertical;
        }


        .form-help {
            display: block;

            margin-top: 7px;

            color: #7b8493;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =====================================================
           UPLOAD
        ===================================================== */

        .upload-box {
            padding: 18px;

            background: #f8fafc;

            border:
                1px
                dashed
                #bfc8d4;

            border-radius: 10px;
        }


        /* =====================================================
           CHECKBOX
        ===================================================== */

        .checkbox {
            display: flex;

            align-items: center;

            gap: 10px;
        }


        .checkbox input {
            width: 17px;

            height: 17px;
        }


        .checkbox label {
            margin: 0;

            font-weight: normal;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .actions {
            display: flex;

            gap: 10px;

            flex-wrap: wrap;

            margin-top: 28px;
        }


        .btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 18px;

            border: none;

            border-radius: 8px;

            cursor: pointer;

            font-size: 14px;

            font-family: inherit;
        }


        .btn-primary {
            background: #17365d;

            color: #ffffff;
        }


        .btn-primary:hover {
            background: #28558a;
        }


        .btn-secondary {
            background: #e9ecef;

            color: #333333;
        }


        .btn-secondary:hover {
            background: #dfe3e6;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

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


            .actions {
                flex-direction: column;
            }


            .btn {
                width: 100%;
            }

        }

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


        <a
            href="banner.php"
            class="active"
        >

            Kelola Banner

        </a>


        <a href="kelola-admin.php">

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


        <h1>

            Tambah Banner

        </h1>


        <p>

            Tambahkan banner baru ke slider Portal Layanan SDM PUSRI.

        </p>


    </div>



    <div class="info-box">

        Gambar banner akan disimpan di

        <b>MinIO</b>.

        Database hanya menyimpan lokasi
        object gambar tersebut.

    </div>



    <?php if ($error !== ""): ?>


        <div class="alert-error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES | ENT_SUBSTITUTE,
                "UTF-8"
            ) ?>

        </div>


    <?php endif; ?>



    <div class="card">


        <form
            method="POST"
            action="tambah-banner.php"
            enctype="multipart/form-data"
        >


            <?= csrfField() ?>


            <div class="form-group">


                <label for="title">

                    Judul Banner

                    <span class="required">

                        *

                    </span>

                </label>


                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="255"
                    value="<?= htmlspecialchars(
                        $_POST["title"]
                        ?? "",
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        "UTF-8"
                    ) ?>"
                    required
                >


            </div>



            <div class="form-group">


                <label for="subtitle">

                    Deskripsi Banner

                </label>


                <textarea
                    id="subtitle"
                    name="subtitle"
                    maxlength="5000"
                    placeholder="Masukkan deskripsi banner..."
                ><?= htmlspecialchars(
                    $_POST["subtitle"]
                    ?? "",
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    "UTF-8"
                ) ?></textarea>


            </div>



            <div class="form-group">


                <label for="sort_order">

                    Urutan Banner

                </label>


                <input
                    type="number"
                    id="sort_order"
                    name="sort_order"
                    min="0"
                    max="999999"
                    step="1"
                    value="<?= isset(
                        $_POST["sort_order"]
                    )

                        ? htmlspecialchars(
                            (string)
                            $_POST["sort_order"],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            "UTF-8"
                        )

                        : "0"
                    ?>"
                >


                <span class="form-help">

                    Angka lebih kecil
                    akan ditampilkan lebih dahulu
                    di slider.

                </span>


            </div>



            <div class="form-group">


                <label for="image">

                    Gambar Banner

                    <span class="required">

                        *

                    </span>

                </label>


                <div class="upload-box">


                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        required
                    >


                    <span class="form-help">

                        Format JPG, PNG, atau WEBP.
                        Maksimal 2 MB.
                        File disimpan di MinIO.

                    </span>


                </div>


            </div>



            <div class="form-group">


                <label>

                    Status Banner

                </label>


                <div class="checkbox">


                    <input
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        value="1"

                        <?php

                        if (
                            $_SERVER["REQUEST_METHOD"]
                            !== "POST"
                            ||
                            isset(
                                $_POST["is_active"]
                            )
                        ) {

                            echo "checked";
                        }

                        ?>
                    >


                    <label for="is_active">

                        Aktifkan banner ini

                    </label>


                </div>


                <span class="form-help">

                    Banner aktif akan tampil
                    di slider portal utama.

                </span>


            </div>



            <div class="actions">


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Simpan Banner

                </button>


                <a
                    href="banner.php"
                    class="btn btn-secondary"
                >

                    Kembali

                </a>


            </div>


        </form>


    </div>


</main>


</body>

</html>