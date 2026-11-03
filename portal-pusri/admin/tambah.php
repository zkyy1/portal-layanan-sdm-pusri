<?php



/*

|--------------------------------------------------------------------------

| PROTEKSI ADMIN

|--------------------------------------------------------------------------

*/



require_once __DIR__ . "/../includes/auth.php";



requireRole("ADMIN");



$user =

    currentUser();





/*

|--------------------------------------------------------------------------

| CSRF

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

| DATA KATEGORI

|--------------------------------------------------------------------------

*/



$categories = [];





/*

|--------------------------------------------------------------------------

| AMBIL KATEGORI

|--------------------------------------------------------------------------

*/



try {



    $queryCategories = "

        SELECT

            id,

            name

        FROM categories

        ORDER BY name ASC

    ";



    $resultCategories =

        $conn->query(

            $queryCategories

        );



    while (

        $category =

            $resultCategories->fetch_assoc()

    ) {



        $categories[] =

            $category;

    }



} catch (Throwable $e) {



    /*

    |--------------------------------------------------------------------------

    | DETAIL ERROR HANYA MASUK LOG

    |--------------------------------------------------------------------------

    */



    error_log(

        "Portal Layanan SDM PUSRI Category Query Error: "

        . get_class($e)

        . ": "

        . $e->getMessage()

    );



    http_response_code(

        500

    );



    die(

        "Data kategori tidak dapat dimuat."

    );

}





/*

|--------------------------------------------------------------------------

| PROSES TAMBAH LAYANAN

|--------------------------------------------------------------------------

*/



if (

    $_SERVER["REQUEST_METHOD"]

    === "POST"

) {



    /*

    |--------------------------------------------------------------------------

    | VALIDASI CSRF

    |--------------------------------------------------------------------------

    */



    requireCsrf();





    /*

    |--------------------------------------------------------------------------

    | AMBIL DATA FORM

    |--------------------------------------------------------------------------

    */



    $name =

        trim(

            (string) (

                $_POST["name"]

                ?? ""

            )

        );



    $description =

        trim(

            (string) (

                $_POST["description"]

                ?? ""

            )

        );



    $url =

        trim(

            (string) (

                $_POST["url"]

                ?? ""

            )

        );



    $categoryInput =

        $_POST["category_id"]

        ?? "";



    $sortOrderInput =

        $_POST["sort_order"]

        ?? "0";



    $is_active =

        isset(

            $_POST["is_active"]

        )

            ? 1

            : 0;





    /*

    |--------------------------------------------------------------------------

    | NILAI DEFAULT

    |--------------------------------------------------------------------------

    */



    $category_id =

        null;



    $sort_order =

        0;



    $icon =

        null;





    /*

    |--------------------------------------------------------------------------

    | VALIDASI NAMA LAYANAN

    |--------------------------------------------------------------------------

    */



    if (

        $name === ""

    ) {



        $error =

            "Nama layanan wajib diisi.";



    } elseif (

        mb_strlen(

            $name,

            "UTF-8"

        ) > 255

    ) {



        $error =

            "Nama layanan maksimal 255 karakter.";

    }





    /*

    |--------------------------------------------------------------------------

    | VALIDASI DESKRIPSI

    |--------------------------------------------------------------------------

    */



    if (

        $error === ""

        &&

        mb_strlen(

            $description,

            "UTF-8"

        ) > 5000

    ) {



        $error =

            "Deskripsi layanan terlalu panjang.";

    }





    /*

    |--------------------------------------------------------------------------

    | VALIDASI URL

    |--------------------------------------------------------------------------

    */



    if (

        $error === ""

        &&

        $url === ""

    ) {



        $error =

            "URL layanan wajib diisi.";



    } elseif (

        $error === ""

        &&

        strlen(

            $url

        ) > 2048

    ) {



        $error =

            "URL layanan terlalu panjang.";



    } elseif (

        $error === ""

        &&

        filter_var(

            $url,

            FILTER_VALIDATE_URL

        ) === false

    ) {



        $error =

            "Format URL layanan tidak valid.";



    } elseif (

        $error === ""

    ) {



        /*

        |--------------------------------------------------------------------------

        | HANYA IZINKAN HTTP / HTTPS

        |--------------------------------------------------------------------------

        */



        $scheme =

            strtolower(

                (string)

                parse_url(

                    $url,

                    PHP_URL_SCHEME

                )

            );



        if (

            !in_array(

                $scheme,

                [

                    "http",

                    "https"

                ],

                true

            )

        ) {



            $error =

                "URL layanan harus menggunakan HTTP atau HTTPS.";

        }

    }





    /*

    |--------------------------------------------------------------------------

    | VALIDASI SORT ORDER

    |--------------------------------------------------------------------------

    */



    if (

        $error === ""

    ) {



        if (

            filter_var(

                $sortOrderInput,

                FILTER_VALIDATE_INT

            ) === false

        ) {



            $error =

                "Urutan tampilan harus berupa angka bulat.";



        } else {



            $sort_order =

                (int)

                $sortOrderInput;



            /*

            |--------------------------------------------------------------------------

            | BATAS URUTAN

            |--------------------------------------------------------------------------

            */



            if (

                $sort_order < 0

                ||

                $sort_order > 999999

            ) {



                $error =

                    "Urutan tampilan tidak valid.";

            }

        }

    }





    /*

    |--------------------------------------------------------------------------

    | VALIDASI CATEGORY ID

    |--------------------------------------------------------------------------

    */



    if (

        $error === ""

        &&

        $categoryInput !== ""

    ) {



        if (

            filter_var(

                $categoryInput,

                FILTER_VALIDATE_INT

            ) === false

        ) {



            $error =

                "Kategori tidak valid.";



        } else {



            $category_id =

                (int)

                $categoryInput;



            if (

                $category_id <= 0

            ) {



                $error =

                    "Kategori tidak valid.";

            }

        }

    }





    /*

    |--------------------------------------------------------------------------

    | PASTIKAN KATEGORI BENAR-BENAR ADA

    |--------------------------------------------------------------------------

    */



    if (

        $error === ""

        &&

        $category_id !== null

    ) {



        try {



            $categoryCheck =

                $conn->prepare(

                    "

                    SELECT

                        id

                    FROM categories

                    WHERE id = ?

                    LIMIT 1

                    "

                );



            $categoryCheck->bind_param(

                "i",

                $category_id

            );



            $categoryCheck->execute();



            /*

            |--------------------------------------------------------------------------

            | BIND HASIL

            |--------------------------------------------------------------------------

            */



            $foundCategoryId =

                null;



            $categoryCheck->bind_result(

                $foundCategoryId

            );



            $categoryExists =

                $categoryCheck->fetch();



            $categoryCheck->close();



            if (

                !$categoryExists

            ) {



                $error =

                    "Kategori yang dipilih tidak ditemukan.";

            }



        } catch (Throwable $e) {



            error_log(

                "Portal Layanan SDM PUSRI Category Validation Error: "

                . get_class($e)

                . ": "

                . $e->getMessage()

            );



            $error =

                "Kategori tidak dapat divalidasi.";

        }

    }





    /*

    |--------------------------------------------------------------------------

    | UPLOAD ICON KE MINIO

    |--------------------------------------------------------------------------

    |

    | Upload hanya dilakukan setelah input lain valid.

    |--------------------------------------------------------------------------

    */



    if (

        $error === ""

        &&

        isset(

            $_FILES["icon"]

        )

        &&

        (

            $_FILES["icon"]["error"]

            ?? UPLOAD_ERR_NO_FILE

        )

        !== UPLOAD_ERR_NO_FILE

    ) {



        try {



            $icon =

                uploadImageToMinio(

                    $s3,

                    $minioBucket,

                    $_FILES["icon"],

                    "applications/icons"

                );



        } catch (Throwable $e) {



            /*

            |--------------------------------------------------------------------------

            | Helper upload hanya memberikan pesan validasi aman.

            |--------------------------------------------------------------------------

            */



            $error =

                $e->getMessage();

        }

    }





    /*

    |--------------------------------------------------------------------------

    | SIMPAN KE DATABASE

    |--------------------------------------------------------------------------

    */



    if (

        $error === ""

    ) {



        try {



            /*

            |--------------------------------------------------------------------------

            | PREPARED STATEMENT

            |--------------------------------------------------------------------------

            */



            $sql = "

                INSERT INTO applications

                (

                    name,

                    description,

                    url,

                    category_id,

                    icon,

                    is_active,

                    sort_order

                )

                VALUES

                (

                    ?,

                    ?,

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



            /*

            |--------------------------------------------------------------------------

            | BIND PARAMETER

            |--------------------------------------------------------------------------

            |

            | s = string

            | i = integer

            |--------------------------------------------------------------------------

            */



            $stmt->bind_param(

                "sssisii",

                $name,

                $description,

                $url,

                $category_id,

                $icon,

                $is_active,

                $sort_order

            );



            /*

            |--------------------------------------------------------------------------

            | EXECUTE

            |--------------------------------------------------------------------------

            */



            $stmt->execute();



            $stmt->close();



            /*

            |--------------------------------------------------------------------------

            | BERHASIL

            |--------------------------------------------------------------------------

            */



            header(

                "Location: /portal-pusri/admin/index.php?success=tambah",

                true,

                302

            );



            exit;



        } catch (Throwable $e) {



            /*

            |--------------------------------------------------------------------------

            | DATABASE ERROR

            |--------------------------------------------------------------------------

            |

            | Detail tidak ditampilkan kepada user.

            |--------------------------------------------------------------------------

            */



            error_log(

                "Portal Layanan SDM PUSRI Add Application Error: "

                . get_class($e)

                . ": "

                . $e->getMessage()

            );



            /*

            |--------------------------------------------------------------------------

            | CLEANUP ICON MINIO

            |--------------------------------------------------------------------------

            |

            | Kalau icon sudah berhasil masuk MinIO tetapi INSERT database gagal,

            | hapus icon agar tidak menjadi object yatim/orphan.

            |--------------------------------------------------------------------------

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



                } catch (Throwable $deleteException) {



                    /*

                    |--------------------------------------------------------------------------

                    | Cleanup gagal juga hanya masuk log.

                    |--------------------------------------------------------------------------

                    */



                    error_log(

                        "Portal Layanan SDM PUSRI MinIO Cleanup Error: "

                        . get_class(

                            $deleteException

                        )

                        . ": "

                        . $deleteException->getMessage()

                    );

                }

            }



            $error =

                "Layanan gagal disimpan. Silakan coba kembali.";

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

        Tambah Layanan - Admin Layanan SDM

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



            background: #f5f7fa;



            color: #172033;

        }



        a {

            text-decoration: none;

        }



        /* =====================================================

           SIDEBAR

        ===================================================== */



        .sidebar {

            position: fixed;



            top: 0;

            left: 0;



            width: 240px;



            height: 100vh;



            background: #17365d;



            color: white;



            padding:

                25px

                15px;



            overflow-y: auto;



            display: flex;



            flex-direction: column;

        }



        .sidebar-title {

            text-align: center;



            font-size: 24px;



            font-weight: bold;



            margin-bottom: 35px;

        }



        .sidebar-subtitle {

            display: block;



            margin-top: 5px;



            font-size: 11px;



            font-weight: normal;



            opacity: 0.7;

        }



        .sidebar-menu a {

            display: block;



            color: white;



            padding:

                13px

                15px;



            margin-bottom: 8px;



            border-radius: 8px;



            font-size: 14px;



            transition: 0.2s;

        }



        .sidebar-menu a:hover,

        .sidebar-menu a.active {

            background: #28558a;

        }



        /* =====================================================

           USER LOGIN

        ===================================================== */



        .sidebar-user {

            margin-top: auto;



            padding:

                20px

                15px

                5px;



            border-top:

                1px solid

                rgba(255,255,255,0.18);



            display: flex;



            flex-direction: column;



            gap: 5px;

        }



        .sidebar-user-label {

            color:

                rgba(255,255,255,0.65);



            font-size: 11px;

        }



        .sidebar-user strong {

            color: white;



            font-size: 14px;



            word-break: break-word;

        }



        .sidebar-logout {

            margin-top: 10px;



            padding:

                10px

                14px;



            display: flex;



            align-items: center;



            justify-content: center;



            border-radius: 8px;



            background: white;



            color: #17365d !important;



            font-size: 13px;



            font-weight: bold;

        }



        .sidebar-logout:hover {

            background: #e7edf5;

        }



        /* =====================================================

           MAIN

        ===================================================== */



        .main {

            margin-left: 240px;



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

            max-width: 820px;



            background: #e7f1ff;



            border:

                1px solid

                #b6d4fe;



            color: #084298;



            padding:

                14px

                16px;



            border-radius: 8px;



            margin-bottom: 20px;



            font-size: 13px;



            line-height: 1.6;

        }



        /* =====================================================

           ERROR

        ===================================================== */



        .alert-error {

            max-width: 820px;



            background: #f8d7da;



            color: #842029;



            border:

                1px solid

                #f5c2c7;



            padding:

                14px

                16px;



            border-radius: 8px;



            margin-bottom: 20px;



            font-size: 14px;

        }



        /* =====================================================

           CARD

        ===================================================== */



        .card {

            max-width: 820px;



            background: white;



            border-radius: 14px;



            padding: 30px;



            border:

                1px solid

                #e7ebf0;



            box-shadow:

                0 5px 20px

                rgba(0,0,0,0.05);

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



            font-weight: bold;

        }



        .required {

            color: #dc3545;

        }



        input[type="text"],

        input[type="url"],

        input[type="number"],

        input[type="file"],

        textarea,

        select {



            width: 100%;



            padding:

                12px

                14px;



            border:

                1px solid

                #d9dee5;



            border-radius: 8px;



            outline: none;



            background: white;



            font-size: 14px;

        }



        input:focus,

        textarea:focus,

        select:focus {



            border-color: #28558a;



            box-shadow:

                0 0 0 3px

                rgba(40,85,138,0.08);

        }



        textarea {

            min-height: 120px;



            resize: vertical;

        }



        .form-help {

            display: block;



            margin-top: 6px;



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

                1px dashed

                #bfc8d4;



            border-radius: 10px;

        }



        .upload-box strong {

            display: block;



            margin-bottom: 7px;



            font-size: 13px;

        }



        /* =====================================================

           CHECKBOX

        ===================================================== */



        .checkbox-wrapper {

            display: flex;



            align-items: center;



            gap: 10px;

        }



        .checkbox-wrapper input {

            width: 17px;



            height: 17px;

        }



        .checkbox-wrapper label {

            margin: 0;



            font-weight: normal;

        }



        /* =====================================================

           BUTTON

        ===================================================== */



        .form-actions {

            display: flex;



            gap: 10px;



            margin-top: 30px;

        }



        .btn {

            display: inline-flex;



            align-items: center;



            justify-content: center;



            padding:

                11px

                18px;



            border-radius: 8px;



            border: none;



            cursor: pointer;



            font-size: 14px;



            font-family: inherit;

        }



        .btn-primary {

            background: #17365d;



            color: white;

        }



        .btn-primary:hover {

            background: #28558a;

        }



        .btn-secondary {

            background: #e9ecef;



            color: #333;

        }



        .btn-secondary:hover {

            background: #dfe3e6;

        }



        /* =====================================================

           MOBILE

        ===================================================== */



        @media(max-width: 768px) {



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



            .form-actions {

                flex-direction: column;

            }



            .btn {

                width: 100%;

            }



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

            border-bottom: 1px solid rgba(255, 255, 255, 0.12);

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

            color: rgba(255, 255, 255, 0.66);

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

            margin-bottom: 0;

            border-radius: 9px;

            color: rgba(255, 255, 255, 0.88);

            font-size: 13px;

            font-weight: 500;

            transition:

                background 0.2s ease,

                color 0.2s ease;

        }



        .sidebar-menu a:hover {

            background: rgba(255, 255, 255, 0.10);

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

            border-radius: 0 8px 8px 0;

            background: #f4c400;

        }



        .sidebar-user {

            margin-top: auto;

            padding: 18px 10px 0;

            border-top: 1px solid rgba(255, 255, 255, 0.14);

        }



        .sidebar-user-label {

            display: block;

            color: rgba(255, 255, 255, 0.58);

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

            transition: background 0.2s ease;

        }



        .sidebar-logout:hover {

            background: #e9eef5;

        }



        .main {

            margin-left: 230px;

        }



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





        <a href="tambah.php" class="active">



            + Tambah Layanan



        </a>





        <a href="kategori.php">



            Kelola Kategori



        </a>





        <a href="banner.php">



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

            Tambah Layanan

        </h1>





        <p>

            Tambahkan layanan baru ke Portal Layanan SDM PUSRI.

        </p>





    </div>







    <div class="info-box">



        Icon layanan akan disimpan langsung

        ke <b>MinIO</b>.



        Database MySQL hanya menyimpan

        lokasi object icon tersebut.



    </div>







    <?php if (

        $error !== ""

    ): ?>





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

            action="tambah.php"

            enctype="multipart/form-data"

        >





            <!-- =================================================

                 CSRF TOKEN

            ================================================== -->



            <?= csrfField() ?>







            <!-- =================================================

                 NAMA

            ================================================== -->



            <div class="form-group">





                <label for="name">



                    Nama Layanan



                    <span class="required">

                        *

                    </span>



                </label>





                <input

                    type="text"

                    id="name"

                    name="name"

                    maxlength="255"

                    placeholder="Contoh: Sistem Kepegawaian"

                    value="<?= htmlspecialchars(

                        $_POST["name"]

                        ?? "",

                        ENT_QUOTES | ENT_SUBSTITUTE,

                        "UTF-8"

                    ) ?>"

                    required

                >





            </div>







            <!-- =================================================

                 DESKRIPSI

            ================================================== -->



            <div class="form-group">





                <label for="description">



                    Deskripsi



                </label>





                <textarea

                    id="description"

                    name="description"

                    maxlength="5000"

                    placeholder="Masukkan deskripsi layanan..."

                ><?= htmlspecialchars(

                    $_POST["description"]

                    ?? "",

                    ENT_QUOTES | ENT_SUBSTITUTE,

                    "UTF-8"

                ) ?></textarea>





            </div>







            <!-- =================================================

                 URL

            ================================================== -->



            <div class="form-group">





                <label for="url">



                    URL Layanan



                    <span class="required">

                        *

                    </span>



                </label>





                <input

                    type="url"

                    id="url"

                    name="url"

                    maxlength="2048"

                    placeholder="https://contoh.pusri.co.id"

                    value="<?= htmlspecialchars(

                        $_POST["url"]

                        ?? "",

                        ENT_QUOTES | ENT_SUBSTITUTE,

                        "UTF-8"

                    ) ?>"

                    required

                >





                <span class="form-help">



                    Masukkan alamat lengkap layanan

                    menggunakan HTTP atau HTTPS.



                </span>





            </div>







            <!-- =================================================

                 KATEGORI

            ================================================== -->



            <div class="form-group">





                <label for="category_id">



                    Kategori



                </label>





                <select

                    id="category_id"

                    name="category_id"

                >





                    <option value="">



                        -- Pilih Kategori --



                    </option>





                    <?php foreach (

                        $categories as $category

                    ): ?>





                        <option

                            value="<?= (int)

                                $category["id"]

                            ?>"



                            <?php



                            if (

                                isset(

                                    $_POST["category_id"]

                                )

                                &&

                                (string)

                                $_POST["category_id"]

                                ===

                                (string)

                                $category["id"]

                            ) {



                                echo "selected";

                            }



                            ?>

                        >



                            <?= htmlspecialchars(

                                $category["name"],

                                ENT_QUOTES | ENT_SUBSTITUTE,

                                "UTF-8"

                            ) ?>



                        </option>





                    <?php endforeach; ?>





                </select>





            </div>







            <!-- =================================================

                 ICON

            ================================================== -->



            <div class="form-group">





                <label for="icon">



                    Icon Layanan



                </label>





                <div class="upload-box">





                    <strong>



                        Upload Icon



                    </strong>





                    <input

                        type="file"

                        id="icon"

                        name="icon"

                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"

                    >





                    <span class="form-help">



                        Format JPG, PNG, atau WEBP.

                        Ukuran maksimal 2 MB.



                    </span>





                </div>





            </div>







            <!-- =================================================

                 URUTAN

            ================================================== -->



            <div class="form-group">





                <label for="sort_order">



                    Urutan Tampilan



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



                    Angka yang lebih kecil

                    akan tampil lebih dahulu.



                </span>





            </div>







            <!-- =================================================

                 STATUS

            ================================================== -->



            <div class="form-group">





                <label>



                    Status Layanan



                </label>





                <div class="checkbox-wrapper">





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



                        Aktifkan layanan



                    </label>





                </div>





                <span class="form-help">



                    Layanan aktif akan

                    ditampilkan di portal utama.



                </span>





            </div>







            <!-- =================================================

                 ACTION

            ================================================== -->



            <div class="form-actions">





                <button

                    type="submit"

                    class="btn btn-primary"

                >



                    Simpan Layanan



                </button>





                <a

                    href="index.php"

                    class="btn btn-secondary"

                >



                    Batal



                </a>





            </div>





        </form>





    </div>





</main>





</body>



</html>