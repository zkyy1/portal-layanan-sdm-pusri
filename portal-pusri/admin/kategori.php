<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('ADMIN');
$user = currentUser();

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_GET['hapus'])) {
    header('Allow: POST');
    http_response_code(405);
    die('405 - Method Not Allowed');
}

$error = '';
$success = '';
$edit_id = 0;
$edit_name = '';
$edit_description = '';
$add_name = '';
$add_description = '';
$categories = [];

function categoryTextLength(string $value): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf();

    $action = trim((string)($_POST['action'] ?? ''));

    /*
    |--------------------------------------------------------------------------
    | TAMBAH KATEGORI
    |--------------------------------------------------------------------------
    */

    if ($action === 'tambah') {

        $name =
            trim(
                (string)($_POST['name'] ?? '')
            );

        $description =
            trim(
                (string)($_POST['description'] ?? '')
            );

        $add_name =
            $name;

        $add_description =
            $description;


        if ($name === '') {

            $error =
                'Nama kategori wajib diisi.';

        } elseif (
            categoryTextLength($name) > 255
        ) {

            $error =
                'Nama kategori maksimal 255 karakter.';

        } elseif (
            categoryTextLength($description) > 5000
        ) {

            $error =
                'Deskripsi kategori terlalu panjang.';
        }


        if ($error === '') {

            try {

                $stmt =
                    $conn->prepare(
                        'SELECT id
                         FROM categories
                         WHERE name = ?
                         LIMIT 1'
                    );


                $stmt->bind_param(
                    's',
                    $name
                );


                $stmt->execute();


                $existingId =
                    null;


                $stmt->bind_result(
                    $existingId
                );


                $duplicate =
                    $stmt->fetch();


                $stmt->close();


                if ($duplicate) {

                    $error =
                        'Kategori dengan nama tersebut sudah ada.';

                } else {

                    $stmt =
                        $conn->prepare(
                            'INSERT INTO categories
                             (
                                name,
                                description
                             )
                             VALUES
                             (
                                ?,
                                ?
                             )'
                        );


                    $stmt->bind_param(
                        'ss',
                        $name,
                        $description
                    );


                    $stmt->execute();


                    $stmt->close();


                    header(
                        'Location: /portal-pusri/admin/kategori.php?success=tambah',
                        true,
                        302
                    );

                    exit;
                }


            } catch (Throwable $e) {

                error_log(
                    'Portal PUSRI Add Category Error: '
                    . get_class($e)
                    . ': '
                    . $e->getMessage()
                );


                $error =
                    'Kategori gagal ditambahkan. Silakan coba kembali.';
            }
        }


    /*
    |--------------------------------------------------------------------------
    | EDIT KATEGORI
    |--------------------------------------------------------------------------
    */

    } elseif (
        $action === 'edit'
    ) {

        $idInput =
            $_POST['id']
            ?? null;


        $id =
            0;


        if (
            $idInput !== null
            &&
            filter_var(
                $idInput,
                FILTER_VALIDATE_INT
            ) !== false
        ) {

            $id =
                (int)$idInput;
        }


        $name =
            trim(
                (string)($_POST['name'] ?? '')
            );


        $description =
            trim(
                (string)($_POST['description'] ?? '')
            );


        $edit_id =
            $id;


        $edit_name =
            $name;


        $edit_description =
            $description;


        if (
            $id <= 0
        ) {

            $error =
                'ID kategori tidak valid.';

            $edit_id =
                0;

        } elseif (
            $name === ''
        ) {

            $error =
                'Nama kategori wajib diisi.';

        } elseif (
            categoryTextLength($name) > 255
        ) {

            $error =
                'Nama kategori maksimal 255 karakter.';

        } elseif (
            categoryTextLength($description) > 5000
        ) {

            $error =
                'Deskripsi kategori terlalu panjang.';
        }


        if ($error === '') {

            try {

                $stmt =
                    $conn->prepare(
                        'SELECT id
                         FROM categories
                         WHERE id = ?
                         LIMIT 1'
                    );


                $stmt->bind_param(
                    'i',
                    $id
                );


                $stmt->execute();


                $foundId =
                    null;


                $stmt->bind_result(
                    $foundId
                );


                $exists =
                    $stmt->fetch();


                $stmt->close();


                if (!$exists) {

                    $error =
                        'Kategori tidak ditemukan.';

                    $edit_id =
                        0;

                } else {

                    $stmt =
                        $conn->prepare(
                            'SELECT id
                             FROM categories
                             WHERE name = ?
                             AND id <> ?
                             LIMIT 1'
                        );


                    $stmt->bind_param(
                        'si',
                        $name,
                        $id
                    );


                    $stmt->execute();


                    $duplicateId =
                        null;


                    $stmt->bind_result(
                        $duplicateId
                    );


                    $duplicate =
                        $stmt->fetch();


                    $stmt->close();


                    if ($duplicate) {

                        $error =
                            'Nama kategori tersebut sudah digunakan.';

                    } else {

                        $stmt =
                            $conn->prepare(
                                'UPDATE categories
                                 SET
                                    name = ?,
                                    description = ?
                                 WHERE id = ?'
                            );


                        $stmt->bind_param(
                            'ssi',
                            $name,
                            $description,
                            $id
                        );


                        $stmt->execute();


                        $stmt->close();


                        header(
                            'Location: /portal-pusri/admin/kategori.php?success=edit',
                            true,
                            302
                        );

                        exit;
                    }
                }


            } catch (Throwable $e) {

                error_log(
                    'Portal PUSRI Edit Category Error: '
                    . get_class($e)
                    . ': '
                    . $e->getMessage()
                );


                $error =
                    'Kategori gagal diperbarui. Silakan coba kembali.';
            }
        }


    /*
    |--------------------------------------------------------------------------
    | HAPUS KATEGORI
    |--------------------------------------------------------------------------
    */

    } elseif (
        $action === 'hapus'
    ) {

        $idInput =
            $_POST['id']
            ?? null;


        $delete_id =
            0;


        if (
            $idInput !== null
            &&
            filter_var(
                $idInput,
                FILTER_VALIDATE_INT
            ) !== false
        ) {

            $delete_id =
                (int)$idInput;
        }


        if (
            $delete_id <= 0
        ) {

            $error =
                'ID kategori tidak valid.';

        } else {

            $transactionStarted =
                false;


            try {

                $conn->begin_transaction();

                $transactionStarted =
                    true;


                $stmt =
                    $conn->prepare(
                        'SELECT id
                         FROM categories
                         WHERE id = ?
                         LIMIT 1
                         FOR UPDATE'
                    );


                $stmt->bind_param(
                    'i',
                    $delete_id
                );


                $stmt->execute();


                $lockedId =
                    null;


                $stmt->bind_result(
                    $lockedId
                );


                $exists =
                    $stmt->fetch();


                $stmt->close();


                if (!$exists) {

                    $conn->rollback();

                    $transactionStarted =
                        false;


                    $error =
                        'Kategori tidak ditemukan.';

                } else {

                    $stmt =
                        $conn->prepare(
                            'SELECT COUNT(*)
                             FROM applications
                             WHERE category_id = ?'
                        );


                    $stmt->bind_param(
                        'i',
                        $delete_id
                    );


                    $stmt->execute();


                    $totalUsage =
                        0;


                    $stmt->bind_result(
                        $totalUsage
                    );


                    $stmt->fetch();


                    $stmt->close();


                    $totalUsage =
                        (int)$totalUsage;


                    if (
                        $totalUsage > 0
                    ) {

                        $conn->rollback();

                        $transactionStarted =
                            false;


                        $error =
                            'Kategori tidak dapat dihapus karena masih digunakan oleh '
                            . $totalUsage
                            . ' aplikasi. Ubah kategori aplikasi terlebih dahulu.';

                    } else {

                        $stmt =
                            $conn->prepare(
                                'DELETE FROM categories
                                 WHERE id = ?'
                            );


                        $stmt->bind_param(
                            'i',
                            $delete_id
                        );


                        $stmt->execute();


                        $affected =
                            $stmt->affected_rows;


                        $stmt->close();


                        if (
                            $affected !== 1
                        ) {

                            throw new RuntimeException(
                                'Jumlah kategori yang terhapus tidak valid.'
                            );
                        }


                        $conn->commit();

                        $transactionStarted =
                            false;


                        header(
                            'Location: /portal-pusri/admin/kategori.php?success=hapus',
                            true,
                            302
                        );

                        exit;
                    }
                }


            } catch (Throwable $e) {

                if ($transactionStarted) {

                    try {

                        $conn->rollback();

                    } catch (
                        Throwable
                        $rollbackException
                    ) {

                        error_log(
                            'Portal PUSRI Category Rollback Error: '
                            . get_class(
                                $rollbackException
                            )
                            . ': '
                            . $rollbackException->getMessage()
                        );
                    }
                }


                error_log(
                    'Portal PUSRI Delete Category Error: '
                    . get_class($e)
                    . ': '
                    . $e->getMessage()
                );


                $error =
                    'Kategori gagal dihapus. Silakan coba kembali.';
            }
        }


    } else {

        $error =
            'Permintaan kategori tidak valid.';
    }
}


/*
|--------------------------------------------------------------------------
| GET EDIT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    &&
    isset($_GET['edit'])
) {

    $editInput =
        $_GET['edit'];


    if (
        filter_var(
            $editInput,
            FILTER_VALIDATE_INT
        ) === false
    ) {

        $error =
            'ID kategori tidak valid.';

        $edit_id =
            0;

    } else {

        $edit_id =
            (int)$editInput;


        if (
            $edit_id <= 0
        ) {

            $error =
                'ID kategori tidak valid.';

            $edit_id =
                0;

        } else {

            try {

                $stmt =
                    $conn->prepare(
                        'SELECT
                            name,
                            description
                         FROM categories
                         WHERE id = ?
                         LIMIT 1'
                    );


                $stmt->bind_param(
                    'i',
                    $edit_id
                );


                $stmt->execute();


                $dbName =
                    null;


                $dbDescription =
                    null;


                $stmt->bind_result(
                    $dbName,
                    $dbDescription
                );


                if (
                    $stmt->fetch()
                ) {

                    $edit_name =
                        (string)$dbName;


                    $edit_description =
                        (string)(
                            $dbDescription
                            ?? ''
                        );

                } else {

                    $error =
                        'Kategori tidak ditemukan.';

                    $edit_id =
                        0;
                }


                $stmt->close();


            } catch (Throwable $e) {

                error_log(
                    'Portal PUSRI Load Category Edit Error: '
                    . get_class($e)
                    . ': '
                    . $e->getMessage()
                );


                $error =
                    'Data kategori tidak dapat dimuat.';

                $edit_id =
                    0;
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

$successType =
    $_GET['success']
    ?? '';


if (
    $successType === 'tambah'
) {

    $success =
        'Kategori berhasil ditambahkan.';

} elseif (
    $successType === 'edit'
) {

    $success =
        'Kategori berhasil diperbarui.';

} elseif (
    $successType === 'hapus'
) {

    $success =
        'Kategori berhasil dihapus.';
}


/*
|--------------------------------------------------------------------------
| DAFTAR KATEGORI
|--------------------------------------------------------------------------
*/

try {

    $listSql = "
        SELECT
            categories.id,
            categories.name,
            categories.description,
            categories.created_at,
            COUNT(applications.id) AS total_applications
        FROM categories
        LEFT JOIN applications
            ON applications.category_id = categories.id
        GROUP BY
            categories.id,
            categories.name,
            categories.description,
            categories.created_at
        ORDER BY categories.name ASC
    ";


    $result =
        $conn->query(
            $listSql
        );


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $categories[] =
            $row;
    }


} catch (Throwable $e) {

    error_log(
        'Portal PUSRI Category List Error: '
        . get_class($e)
        . ': '
        . $e->getMessage()
    );


    http_response_code(
        500
    );


    die(
        'Daftar kategori tidak dapat dimuat.'
    );
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
        Kelola Kategori - Admin Layanan SDM
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
            text-decoration:
                none;
        }


        /* =====================================================
           SIDEBAR SERAGAM
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

            background:
                #17365d;

            color:
                #ffffff;
        }


        .sidebar-brand {
            padding:
                4px
                10px
                25px;

            border-bottom:
                1px
                solid
                rgba(
                    255,
                    255,
                    255,
                    0.12
                );

            margin-bottom:
                22px;
        }


        .sidebar-brand-title {
            display:
                block;

            color:
                #ffffff;

            font-size:
                20px;

            font-weight:
                700;

            line-height:
                1.25;
        }


        .sidebar-brand-subtitle {
            display:
                block;

            margin-top:
                6px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.66
                );

            font-size:
                11px;

            line-height:
                1.45;
        }


        .sidebar-menu {
            display:
                flex;

            flex-direction:
                column;

            gap:
                6px;
        }


        .sidebar-menu a {
            position:
                relative;

            min-height:
                44px;

            padding:
                0
                14px;

            display:
                flex;

            align-items:
                center;

            border-radius:
                9px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.88
                );

            font-size:
                13px;

            font-weight:
                500;

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

            color:
                #ffffff;
        }


        .sidebar-menu a.active {
            background:
                #28558a;

            color:
                #ffffff;

            font-weight:
                700;
        }


        .sidebar-menu a.active::before {
            content:
                "";

            position:
                absolute;

            left:
                0;

            top:
                9px;

            bottom:
                9px;

            width:
                4px;

            border-radius:
                0
                8px
                8px
                0;

            background:
                #f4c400;
        }


        .sidebar-user {
            margin-top:
                auto;

            padding:
                18px
                10px
                0;

            border-top:
                1px
                solid
                rgba(
                    255,
                    255,
                    255,
                    0.14
                );
        }


        .sidebar-user-label {
            display:
                block;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.58
                );

            font-size:
                10px;
        }


        .sidebar-user strong {
            display:
                block;

            margin-top:
                3px;

            color:
                #ffffff;

            font-size:
                13px;

            overflow-wrap:
                anywhere;
        }


        .sidebar-logout {
            width:
                100%;

            min-height:
                39px;

            margin-top:
                13px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                8px;

            background:
                #ffffff;

            color:
                #17365d;

            font-size:
                12px;

            font-weight:
                700;

            transition:
                background 0.2s ease;
        }


        .sidebar-logout:hover {
            background:
                #e9eef5;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            margin-left:
                230px;

            padding:
                30px;

            min-height:
                100vh;
        }


        .page-header {
            margin-bottom:
                25px;
        }


        .page-header h1 {
            font-size:
                28px;

            margin-bottom:
                5px;
        }


        .page-header p {
            color:
                #6b7280;

            font-size:
                14px;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content-grid {
            display:
                grid;

            grid-template-columns:
                360px
                minmax(
                    0,
                    1fr
                );

            gap:
                25px;

            align-items:
                start;
        }


        .card {
            background:
                #ffffff;

            border-radius:
                14px;

            padding:
                25px;

            border:
                1px
                solid
                #e7ebf0;

            box-shadow:
                0
                5px
                20px
                rgba(
                    0,
                    0,
                    0,
                    0.04
                );
        }


        .card-title {
            font-size:
                19px;

            margin-bottom:
                6px;
        }


        .card-description {
            color:
                #6b7280;

            font-size:
                13px;

            margin-bottom:
                22px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {
            margin-bottom:
                20px;
        }


        .form-group label {
            display:
                block;

            margin-bottom:
                8px;

            font-size:
                13px;

            font-weight:
                700;
        }


        .required {
            color:
                #dc3545;
        }


        input[type="text"],
        textarea {
            width:
                100%;

            border:
                1px
                solid
                #d9dee5;

            border-radius:
                8px;

            padding:
                12px
                13px;

            outline:
                none;

            font-size:
                14px;

            background:
                #ffffff;
        }


        input:focus,
        textarea:focus {
            border-color:
                #28558a;

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
            min-height:
                110px;

            resize:
                vertical;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .btn {
            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border:
                0;

            border-radius:
                8px;

            padding:
                10px
                15px;

            cursor:
                pointer;

            font-size:
                13px;

            font-family:
                inherit;

            white-space:
                nowrap;
        }


        .btn-primary {
            background:
                #17365d;

            color:
                #ffffff;
        }


        .btn-primary:hover {
            background:
                #28558a;
        }


        .btn-secondary {
            background:
                #e9ecef;

            color:
                #333333;
        }


        .btn-secondary:hover {
            background:
                #dfe3e6;
        }


        .btn-edit {
            background:
                #f0ad4e;

            color:
                #ffffff;
        }


        .btn-edit:hover {
            background:
                #d99a35;
        }


        .btn-delete {
            background:
                #dc3545;

            color:
                #ffffff;
        }


        .btn-delete:hover {
            background:
                #bb2d3b;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .alert {
            padding:
                13px
                16px;

            border-radius:
                8px;

            margin-bottom:
                20px;

            font-size:
                13px;

            line-height:
                1.5;
        }


        .alert-success {
            background:
                #d1e7dd;

            color:
                #0f5132;

            border:
                1px
                solid
                #badbcc;
        }


        .alert-error {
            background:
                #f8d7da;

            color:
                #842029;

            border:
                1px
                solid
                #f5c2c7;
        }


        /* =====================================================
           INFO
        ===================================================== */

        .info-box {
            background:
                #e7f1ff;

            color:
                #084298;

            border:
                1px
                solid
                #b6d4fe;

            padding:
                13px
                15px;

            border-radius:
                8px;

            font-size:
                12px;

            line-height:
                1.6;

            margin-bottom:
                20px;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrapper {
            overflow-x:
                auto;
        }


        table {
            width:
                100%;

            border-collapse:
                collapse;

            min-width:
                650px;
        }


        th {
            background:
                #f3f5f7;

            text-align:
                left;

            padding:
                13px;

            font-size:
                12px;
        }


        td {
            padding:
                13px;

            border-bottom:
                1px
                solid
                #edf0f3;

            font-size:
                13px;

            vertical-align:
                middle;
        }


        tr:hover td {
            background:
                #fafbfc;
        }


        .category-name {
            font-weight:
                700;
        }


        .category-description {
            color:
                #6b7280;

            max-width:
                280px;
        }


        .app-count {
            display:
                inline-block;

            background:
                #e7f1ff;

            color:
                #084298;

            padding:
                5px
                9px;

            border-radius:
                20px;

            font-size:
                11px;

            font-weight:
                700;
        }


        .actions {
            display:
                flex;

            gap:
                6px;

            flex-wrap:
                wrap;

            align-items:
                center;
        }


        .action-form {
            margin:
                0;

            padding:
                0;

            display:
                inline-flex;
        }


        @media (
            max-width:
                950px
        ) {

            .content-grid {
                grid-template-columns:
                    1fr;
            }
        }


        @media (
            max-width:
                768px
        ) {

            .sidebar {
                position:
                    relative;

                width:
                    100%;

                height:
                    auto;
            }


            .sidebar-user {
                margin-top:
                    25px;
            }


            .main {
                margin-left:
                    0;

                padding:
                    20px;
            }
        }

    </style>

</head>


<body>


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


        <a
            href="kategori.php"
            class="active"
        >

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
                $user['username']
                ?? 'Admin',
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
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


<main class="main">


    <div class="page-header">


        <h1>

            Kelola Kategori

        </h1>


        <p>

            Tambah, edit, dan hapus kategori aplikasi Portal PUSRI.

        </p>


    </div>



    <?php if ($success !== ''): ?>


        <div class="alert alert-success">

            <?= htmlspecialchars(
                $success,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) ?>

        </div>


    <?php endif; ?>



    <?php if ($error !== ''): ?>


        <div class="alert alert-error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) ?>

        </div>


    <?php endif; ?>



    <div class="info-box">

        <strong>Catatan:</strong>

        Kategori yang masih digunakan oleh aplikasi tidak dapat dihapus.

        Pindahkan terlebih dahulu aplikasi ke kategori lain sebelum menghapus kategori tersebut.

    </div>



    <div class="content-grid">


        <div class="card">


            <?php if ($edit_id > 0): ?>


                <h2 class="card-title">

                    Edit Kategori

                </h2>


                <p class="card-description">

                    Ubah informasi kategori.

                </p>


                <form
                    method="POST"
                    action="kategori.php"
                >


                    <?= csrfField() ?>


                    <input
                        type="hidden"
                        name="action"
                        value="edit"
                    >


                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)$edit_id ?>"
                    >


                    <div class="form-group">


                        <label for="edit_name">

                            Nama Kategori

                            <span class="required">

                                *

                            </span>

                        </label>


                        <input
                            type="text"
                            id="edit_name"
                            name="name"
                            maxlength="255"
                            value="<?= htmlspecialchars(
                                $edit_name,
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ) ?>"
                            required
                        >


                    </div>



                    <div class="form-group">


                        <label for="edit_description">

                            Deskripsi

                        </label>


                        <textarea
                            id="edit_description"
                            name="description"
                            maxlength="5000"
                        ><?= htmlspecialchars(
                            $edit_description,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ) ?></textarea>


                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        Simpan Perubahan

                    </button>


                    <a
                        href="kategori.php"
                        class="btn btn-secondary"
                    >

                        Batal

                    </a>


                </form>


            <?php else: ?>


                <h2 class="card-title">

                    Tambah Kategori

                </h2>


                <p class="card-description">

                    Tambahkan kategori baru untuk aplikasi.

                </p>


                <form
                    method="POST"
                    action="kategori.php"
                >


                    <?= csrfField() ?>


                    <input
                        type="hidden"
                        name="action"
                        value="tambah"
                    >


                    <div class="form-group">


                        <label for="name">

                            Nama Kategori

                            <span class="required">

                                *

                            </span>

                        </label>


                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="255"
                            placeholder="Contoh: Human Resource"
                            value="<?= htmlspecialchars(
                                $add_name,
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ) ?>"
                            required
                        >


                    </div>



                    <div class="form-group">


                        <label for="description">

                            Deskripsi

                        </label>


                        <textarea
                            id="description"
                            name="description"
                            maxlength="5000"
                            placeholder="Deskripsi kategori..."
                        ><?= htmlspecialchars(
                            $add_description,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ) ?></textarea>


                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        + Tambah Kategori

                    </button>


                </form>


            <?php endif; ?>


        </div>



        <div class="card">


            <h2 class="card-title">

                Daftar Kategori

            </h2>


            <p class="card-description">

                Daftar kategori yang tersedia pada Portal PUSRI.

            </p>


            <div class="table-wrapper">


                <table>


                    <thead>


                        <tr>


                            <th>

                                No

                            </th>


                            <th>

                                Nama

                            </th>


                            <th>

                                Deskripsi

                            </th>


                            <th>

                                Jumlah Aplikasi

                            </th>


                            <th>

                                Aksi

                            </th>


                        </tr>


                    </thead>


                    <tbody>


                    <?php if (
                        count($categories) > 0
                    ): ?>


                        <?php $no = 1; ?>


                        <?php foreach (
                            $categories as $row
                        ): ?>


                            <tr>


                                <td>

                                    <?= $no++; ?>

                                </td>


                                <td class="category-name">

                                    <?= htmlspecialchars(
                                        $row['name'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td class="category-description">

                                    <?= htmlspecialchars(
                                        !empty($row['description'])
                                            ? $row['description']
                                            : '-',
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td>


                                    <span class="app-count">

                                        <?= (int)(
                                            $row['total_applications']
                                            ?? 0
                                        ) ?>

                                        aplikasi

                                    </span>


                                </td>


                                <td>


                                    <div class="actions">


                                        <a
                                            href="kategori.php?edit=<?= (int)$row['id'] ?>"
                                            class="btn btn-edit"
                                        >

                                            Edit

                                        </a>


                                        <form
                                            method="POST"
                                            action="kategori.php"
                                            class="action-form"
                                            onsubmit="
                                                return confirm(
                                                    'Yakin ingin menghapus kategori ini?'
                                                );
                                            "
                                        >


                                            <?= csrfField() ?>


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="hapus"
                                            >


                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$row['id'] ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-delete"
                                            >

                                                Hapus

                                            </button>


                                        </form>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>


                            <td
                                colspan="5"
                                style="
                                    text-align:
                                        center;

                                    padding:
                                        40px;

                                    color:
                                        #6b7280;
                                "
                            >

                                Belum ada kategori.

                            </td>


                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </div>


</main>


</body>

</html>