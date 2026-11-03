<?php

require_once __DIR__ . "/../includes/auth.php";

requireRole("ADMIN");

$user =
    currentUser();

require_once __DIR__ . "/../includes/csrf.php";

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/minio.php";
require_once __DIR__ . "/../includes/minio-functions.php";

$idInput =
    $_GET["id"]
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

if ($id <= 0) {

    header(
        "Location: /portal-pusri/admin/index.php",
        true,
        302
    );

    exit;
}

try {

    $sqlApplication = "
        SELECT
            id,
            name,
            description,
            url,
            category_id,
            icon,
            is_active,
            sort_order
        FROM applications
        WHERE id = ?
        LIMIT 1
    ";

    $stmtApplication =
        $conn->prepare(
            $sqlApplication
        );

    $stmtApplication->bind_param(
        "i",
        $id
    );

    $stmtApplication->execute();

    $stmtApplication->bind_result(
        $appId,
        $appName,
        $appDescription,
        $appUrl,
        $appCategoryId,
        $appIcon,
        $appIsActive,
        $appSortOrder
    );

    if (
        !$stmtApplication->fetch()
    ) {

        $stmtApplication->close();

        header(
            "Location: /portal-pusri/admin/index.php",
            true,
            302
        );

        exit;
    }

    $stmtApplication->close();

    $application = [

        "id" =>
            $appId,

        "name" =>
            $appName,

        "description" =>
            $appDescription,

        "url" =>
            $appUrl,

        "category_id" =>
            $appCategoryId,

        "icon" =>
            $appIcon,

        "is_active" =>
            $appIsActive,

        "sort_order" =>
            $appSortOrder

    ];

} catch (Throwable $e) {

    error_log(
        "Portal PUSRI Edit Application Load Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );

    http_response_code(
        500
    );

    die(
        "Data aplikasi tidak dapat dimuat."
    );
}

$categories = [];

try {

    $sqlCategories = "
        SELECT
            id,
            name
        FROM categories
        ORDER BY name ASC
    ";

    $resultCategories =
        $conn->query(
            $sqlCategories
        );

    while (
        $category =
            $resultCategories->fetch_assoc()
    ) {

        $categories[] =
            $category;
    }

} catch (Throwable $e) {

    error_log(
        "Portal PUSRI Edit Category Query Error: "
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

$currentIconUrl =
    "";

if (
    !empty(
        $application["icon"]
    )
) {

    try {

        $currentIconUrl =
            getMinioUrl(
                $s3,
                $minioBucket,
                $application["icon"]
            );

    } catch (Throwable $e) {

        error_log(
            "Portal PUSRI Current Icon URL Error: "
            . get_class($e)
            . ": "
            . $e->getMessage()
        );

        $currentIconUrl =
            "";
    }
}

$error =
    "";

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    requireCsrf();

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

    $category_id =
        null;

    $sort_order =
        0;

    if (
        $name === ""
    ) {

        $error =
            "Nama aplikasi wajib diisi.";

    } elseif (
        mb_strlen(
            $name,
            "UTF-8"
        ) > 255
    ) {

        $error =
            "Nama aplikasi maksimal 255 karakter.";
    }

    if (
        $error === ""
        &&
        mb_strlen(
            $description,
            "UTF-8"
        ) > 5000
    ) {

        $error =
            "Deskripsi aplikasi terlalu panjang.";
    }

    if (
        $error === ""
        &&
        $url === ""
    ) {

        $error =
            "URL aplikasi wajib diisi.";

    } elseif (
        $error === ""
        &&
        strlen(
            $url
        ) > 2048
    ) {

        $error =
            "URL aplikasi terlalu panjang.";

    } elseif (
        $error === ""
        &&
        filter_var(
            $url,
            FILTER_VALIDATE_URL
        ) === false
    ) {

        $error =
            "Format URL aplikasi tidak valid.";

    } elseif (
        $error === ""
    ) {

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
                "URL aplikasi harus menggunakan HTTP atau HTTPS.";
        }
    }

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

    if (
        $error === ""
        &&
        $category_id !== null
    ) {

        try {

            $categoryCheck =
                $conn->prepare(
                    "
                    SELECT id
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
                "Portal PUSRI Edit Category Validation Error: "
                . get_class($e)
                . ": "
                . $e->getMessage()
            );

            $error =
                "Kategori tidak dapat divalidasi.";
        }
    }

    $oldIcon =
        $application["icon"]
        ?? null;

    $icon =
        $oldIcon;

    $uploadedNewIcon =
        null;

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

            $uploadedNewIcon =
                uploadImageToMinio(
                    $s3,
                    $minioBucket,
                    $_FILES["icon"],
                    "applications/icons"
                );

            $icon =
                $uploadedNewIcon;

        } catch (Throwable $e) {

            $error =
                $e->getMessage();
        }
    }

    if (
        $error === ""
    ) {

        try {

            $sqlUpdate = "
                UPDATE applications

                SET
                    name = ?,
                    description = ?,
                    url = ?,
                    category_id = ?,
                    icon = ?,
                    is_active = ?,
                    sort_order = ?

                WHERE id = ?
            ";

            $stmtUpdate =
                $conn->prepare(
                    $sqlUpdate
                );

            $stmtUpdate->bind_param(
                "sssisiii",
                $name,
                $description,
                $url,
                $category_id,
                $icon,
                $is_active,
                $sort_order,
                $id
            );

            $stmtUpdate->execute();

            $stmtUpdate->close();

            if (
                !empty(
                    $uploadedNewIcon
                )
                &&
                !empty(
                    $oldIcon
                )
                &&
                $oldIcon
                !==
                $uploadedNewIcon
            ) {

                try {

                    deleteFromMinio(
                        $s3,
                        $minioBucket,
                        $oldIcon
                    );

                } catch (Throwable $deleteException) {

                    error_log(
                        "Portal PUSRI Old Icon Cleanup Error: "
                        . get_class(
                            $deleteException
                        )
                        . ": "
                        . $deleteException->getMessage()
                    );
                }
            }

            header(
                "Location: /portal-pusri/admin/index.php?success=edit",
                true,
                302
            );

            exit;

        } catch (Throwable $e) {

            error_log(
                "Portal PUSRI Edit Application Error: "
                . get_class($e)
                . ": "
                . $e->getMessage()
            );

            if (
                !empty(
                    $uploadedNewIcon
                )
            ) {

                try {

                    deleteFromMinio(
                        $s3,
                        $minioBucket,
                        $uploadedNewIcon
                    );

                } catch (Throwable $deleteException) {

                    error_log(
                        "Portal PUSRI New Icon Cleanup Error: "
                        . get_class(
                            $deleteException
                        )
                        . ": "
                        . $deleteException->getMessage()
                    );
                }
            }

            $error =
                "Aplikasi gagal diperbarui. Silakan coba kembali.";
        }
    }
}

$formName =
    $_POST["name"]
    ??
    $application["name"]
    ??
    "";

$formDescription =
    $_POST["description"]
    ??
    $application["description"]
    ??
    "";

$formUrl =
    $_POST["url"]
    ??
    $application["url"]
    ??
    "";

$formCategory =
    array_key_exists(
        "category_id",
        $_POST
    )
        ? $_POST["category_id"]
        : (
            $application["category_id"]
            ?? ""
        );

$formSortOrder =
    $_POST["sort_order"]
    ??
    $application["sort_order"]
    ??
    0;

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
        Edit Aplikasi - PUSRI Admin
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

        .sidebar {
            position: fixed;

            top: 0;
            left: 0;

            width: 240px;
            height: 100vh;

            background: #17365d;
            color: white;

            padding: 25px 15px;

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

            padding: 13px 15px;

            margin-bottom: 8px;

            border-radius: 8px;

            font-size: 14px;

            transition: 0.2s;
        }

        .sidebar-menu a:hover {
            background: #28558a;
        }

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

            padding: 10px 14px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: white;

            color: #17365d !important;

            border-radius: 8px;

            font-size: 13px;
            font-weight: bold;
        }

        .sidebar-logout:hover {
            background: #e7edf5;
        }

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

        .info-box {
            max-width: 820px;

            background: #e7f1ff;

            border:
                1px solid
                #b6d4fe;

            color: #084298;

            padding: 14px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 13px;

            line-height: 1.6;
        }

        .alert-error {
            max-width: 820px;

            background: #f8d7da;

            color: #842029;

            border:
                1px solid
                #f5c2c7;

            padding: 14px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 14px;
        }

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

            padding: 12px 14px;

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

        .current-icon-box {
            display: flex;

            align-items: center;

            gap: 18px;

            margin-bottom: 15px;

            padding: 15px;

            background: #f8fafc;

            border:
                1px solid
                #e3e8ef;

            border-radius: 10px;
        }

        .current-icon {
            width: 75px;
            height: 75px;

            min-width: 75px;
            min-height: 75px;

            max-width: 75px;
            max-height: 75px;

            display: flex;

            align-items: center;
            justify-content: center;

            overflow: hidden;

            background: #eef3f8;

            border-radius: 14px;

            color: #17365d;

            font-size: 28px;

            font-weight: bold;
        }

        .current-icon img {
            width: 100% !important;
            height: 100% !important;

            object-fit: contain;

            padding: 5px;

            background: white;
        }

        .current-icon-info strong {
            display: block;

            margin-bottom: 5px;

            font-size: 14px;
        }

        .current-icon-info span {
            color: #6b7280;

            font-size: 12px;
        }

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

        .form-actions {
            display: flex;

            gap: 10px;

            margin-top: 30px;
        }

        .btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 11px 18px;

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

            .current-icon-box {
                align-items: flex-start;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

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
            transition: background 0.2s ease, color 0.2s ease;
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

        <a href="tambah.php">

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
            Edit Aplikasi
        </h1>

        <p>
            Perbarui informasi aplikasi Portal PUSRI.
        </p>

    </div>

    <div class="info-box">

        Jika tidak memilih icon baru,
        sistem akan tetap menggunakan icon yang sekarang.

        Jika memilih icon baru,
        file baru disimpan di MinIO dan icon lama
        baru dihapus setelah database berhasil diperbarui.

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
            action="edit.php?id=<?= (int) $id ?>"
            enctype="multipart/form-data"
        >

            <!-- =================================================
                 CSRF
            ================================================== -->

            <?= csrfField() ?>

            <!-- =================================================
                 NAMA
            ================================================== -->

            <div class="form-group">

                <label for="name">

                    Nama Aplikasi

                    <span class="required">
                        *
                    </span>

                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="255"
                    value="<?= htmlspecialchars(
                        $formName,
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
                ><?= htmlspecialchars(
                    $formDescription,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    "UTF-8"
                ) ?></textarea>

            </div>

            <!-- =================================================
                 URL
            ================================================== -->

            <div class="form-group">

                <label for="url">

                    URL Aplikasi

                    <span class="required">
                        *
                    </span>

                </label>

                <input
                    type="url"
                    id="url"
                    name="url"
                    maxlength="2048"
                    value="<?= htmlspecialchars(
                        $formUrl,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        "UTF-8"
                    ) ?>"
                    required
                >

                <span class="form-help">

                    Gunakan alamat HTTP atau HTTPS.

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
                            <?= (
                                (string)
                                $formCategory
                                ===
                                (string)
                                $category["id"]
                            )
                                ? "selected"
                                : ""
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
                 ICON SEKARANG
            ================================================== -->

            <div class="form-group">

                <label>
                    Icon Saat Ini
                </label>

                <div class="current-icon-box">

                    <div class="current-icon">

                        <?php if (
                            $currentIconUrl !== ""
                        ): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $currentIconUrl,
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    "UTF-8"
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $application["name"],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    "UTF-8"
                                ) ?>"
                            >

                        <?php else: ?>

                            <?= htmlspecialchars(
                                strtoupper(
                                    substr(
                                        (string)
                                        $application["name"],
                                        0,
                                        1
                                    )
                                ),
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                "UTF-8"
                            ) ?>

                        <?php endif; ?>

                    </div>

                    <div class="current-icon-info">

                        <strong>

                            Icon aplikasi sekarang

                        </strong>

                        <?php if (
                            !empty(
                                $application["icon"]
                            )
                        ): ?>

                            <span>

                                File tersimpan di MinIO.

                            </span>

                        <?php else: ?>

                            <span>

                                Aplikasi ini belum memiliki icon.

                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <!-- =================================================
                 ICON BARU
            ================================================== -->

            <div class="form-group">

                <label for="icon">

                    Ganti Icon

                </label>

                <div class="upload-box">

                    <strong>

                        Pilih icon baru

                    </strong>

                    <input
                        type="file"
                        id="icon"
                        name="icon"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <span class="form-help">

                        Kosongkan jika tidak ingin mengganti icon.
                        Format JPG, PNG, atau WEBP.
                        Maksimal 2 MB.

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
                    value="<?= htmlspecialchars(
                        (string)
                        $formSortOrder,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        "UTF-8"
                    ) ?>"
                >

                <span class="form-help">

                    Angka lebih kecil akan
                    ditampilkan lebih dahulu.

                </span>

            </div>

            <!-- =================================================
                 STATUS
            ================================================== -->

            <div class="form-group">

                <label>
                    Status Aplikasi
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
                            === "POST"
                        ) {

                            if (
                                isset(
                                    $_POST["is_active"]
                                )
                            ) {

                                echo "checked";
                            }

                        } else {

                            if (
                                (int)
                                $application["is_active"]
                                === 1
                            ) {

                                echo "checked";
                            }
                        }

                        ?>
                    >

                    <label for="is_active">

                        Aktifkan aplikasi

                    </label>

                </div>

                <span class="form-help">

                    Aplikasi aktif akan tampil
                    di portal utama.

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

                    Simpan Perubahan

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
