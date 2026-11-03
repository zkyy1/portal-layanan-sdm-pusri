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

| USER LOGIN

|--------------------------------------------------------------------------

*/

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

| DATA BANNER

|--------------------------------------------------------------------------

*/

$banners = [];

/*

|--------------------------------------------------------------------------

| AMBIL BANNER

|--------------------------------------------------------------------------

*/

try {

    $sql = "

        SELECT

            id,

            title,

            subtitle,

            image,

            is_active,

            sort_order

        FROM banners

        ORDER BY

            sort_order ASC,

            id ASC

    ";

    $result =

        $conn->query(

            $sql

        );

    while (

        $row =

            $result->fetch_assoc()

    ) {

        $banners[] =

            $row;

    }

} catch (Throwable $e) {

    /*

    |--------------------------------------------------------------------------

    | DETAIL ERROR HANYA MASUK LOG

    |--------------------------------------------------------------------------

    */

    error_log(

        "Portal Layanan SDM PUSRI Banner List Error: "

        . get_class($e)

        . ": "

        . $e->getMessage()

    );

    http_response_code(

        500

    );

    die(

        "Data banner tidak dapat dimuat."

    );

}

/*

|--------------------------------------------------------------------------

| SUCCESS MESSAGE

|--------------------------------------------------------------------------

*/

$successMessage =

    "";

$successType =

    $_GET["success"]

    ?? "";

if (

    $successType === "tambah"

) {

    $successMessage =

        "Banner berhasil ditambahkan.";

} elseif (

    $successType === "edit"

) {

    $successMessage =

        "Banner berhasil diperbarui.";

} elseif (

    $successType === "hapus"

) {

    $successMessage =

        "Banner berhasil dihapus.";

} elseif (

    $successType === "status"

) {

    $successMessage =

        "Status banner berhasil diubah.";

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

        Kelola Banner - Admin Layanan SDM

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

           SIDEBAR

        ===================================================== */

        .sidebar {

            position:

                fixed;

            top:

                0;

            left:

                0;

            width:

                240px;

            height:

                100vh;

            background:

                #17365d;

            color:

                white;

            padding:

                25px

                15px;

            overflow-y:

                auto;

            z-index:

                1000;

            display:

                flex;

            flex-direction:

                column;

        }

        .sidebar-title {

            text-align:

                center;

            font-size:

                24px;

            font-weight:

                bold;

            margin-bottom:

                35px;

        }

        .sidebar-subtitle {

            display:

                block;

            margin-top:

                5px;

            font-size:

                11px;

            font-weight:

                normal;

            opacity:

                0.7;

        }

        .sidebar-menu a {

            display:

                block;

            color:

                white;

            padding:

                13px

                15px;

            margin-bottom:

                8px;

            border-radius:

                8px;

            font-size:

                14px;

            transition:

                0.2s;

        }

        .sidebar-menu a:hover,

        .sidebar-menu a.active {

            background:

                #28558a;

        }

        /* =====================================================

           USER

        ===================================================== */

        .sidebar-user {

            margin-top:

                auto;

            padding:

                20px

                15px

                5px;

            border-top:

                1px

                solid

                rgba(

                    255,

                    255,

                    255,

                    0.18

                );

            display:

                flex;

            flex-direction:

                column;

            gap:

                5px;

        }

        .sidebar-user-label {

            color:

                rgba(

                    255,

                    255,

                    255,

                    0.65

                );

            font-size:

                11px;

        }

        .sidebar-user strong {

            color:

                white;

            font-size:

                14px;

            word-break:

                break-word;

        }

        .sidebar-logout {

            margin-top:

                10px;

            padding:

                10px

                14px;

            display:

                flex;

            align-items:

                center;

            justify-content:

                center;

            border-radius:

                8px;

            background:

                white;

            color:

                #17365d

                !important;

            font-size:

                13px;

            font-weight:

                700;

            transition:

                0.2s;

        }

        .sidebar-logout:hover {

            background:

                #e7edf5

                !important;

            color:

                #17365d

                !important;

        }

        /* =====================================================

           MAIN

        ===================================================== */

        .main {

            margin-left:

                240px;

            padding:

                30px;

            min-height:

                100vh;

        }

        /* =====================================================

           HEADER

        ===================================================== */

        .page-header {

            display:

                flex;

            justify-content:

                space-between;

            align-items:

                center;

            gap:

                20px;

            margin-bottom:

                25px;

        }

        .page-header h1 {

            font-size:

                28px;

            margin-bottom:

                6px;

        }

        .page-header p {

            color:

                #6b7280;

            font-size:

                14px;

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

            padding:

                10px

                15px;

            border:

                none;

            border-radius:

                8px;

            cursor:

                pointer;

            font-family:

                inherit;

            font-size:

                13px;

            white-space:

                nowrap;

            transition:

                0.2s;

        }

        .btn-primary {

            background:

                #17365d;

            color:

                white;

        }

        .btn-primary:hover {

            background:

                #28558a;

        }

        .btn-edit {

            background:

                #f0ad4e;

            color:

                white;

        }

        .btn-edit:hover {

            background:

                #d99a35;

        }

        .btn-disable {

            background:

                #6c757d;

            color:

                white;

        }

        .btn-disable:hover {

            background:

                #565e64;

        }

        .btn-enable {

            background:

                #0d6efd;

            color:

                white;

        }

        .btn-enable:hover {

            background:

                #0b5ed7;

        }

        .btn-delete {

            background:

                #dc3545;

            color:

                white;

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

                14px

                16px;

            border-radius:

                8px;

            margin-bottom:

                20px;

            font-size:

                14px;

        }

        .alert-success {

            background:

                #d1e7dd;

            border:

                1px

                solid

                #badbcc;

            color:

                #0f5132;

        }

        /* =====================================================

           CARD

        ===================================================== */

        .card {

            background:

                white;

            border-radius:

                14px;

            padding:

                25px;

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

           TABLE

        ===================================================== */

        .table-wrapper {

            width:

                100%;

            overflow-x:

                auto;

        }

        table {

            width:

                100%;

            border-collapse:

                collapse;

            min-width:

                1050px;

        }

        th {

            padding:

                14px;

            text-align:

                left;

            background:

                #f3f5f7;

            color:

                #495364;

            font-size:

                12px;

        }

        td {

            padding:

                14px;

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

        /* =====================================================

           PREVIEW

        ===================================================== */

        .banner-image {

            width:

                180px;

            height:

                90px;

            border-radius:

                10px;

            background:

                #eef3f8;

            overflow:

                hidden;

            display:

                flex;

            align-items:

                center;

            justify-content:

                center;

            color:

                #6b7280;

            font-size:

                12px;

        }

        .banner-image img {

            width:

                100%;

            height:

                100%;

            object-fit:

                cover;

            display:

                block;

        }

        /* =====================================================

           TEXT

        ===================================================== */

        .banner-title {

            min-width:

                130px;

            font-weight:

                600;

        }

        .banner-subtitle {

            min-width:

                190px;

            max-width:

                280px;

            color:

                #6b7280;

            line-height:

                1.5;

        }

        /* =====================================================

           STATUS

        ===================================================== */

        .status {

            display:

                inline-block;

            padding:

                6px

                10px;

            border-radius:

                20px;

            font-size:

                11px;

            font-weight:

                bold;

            white-space:

                nowrap;

        }

        .status-active {

            background:

                #d1e7dd;

            color:

                #0f5132;

        }

        .status-inactive {

            background:

                #f8d7da;

            color:

                #842029;

        }

        /* =====================================================

           ACTION

        ===================================================== */

        .action-buttons {

            display:

                flex;

            align-items:

                center;

            gap:

                6px;

            flex-wrap:

                wrap;

            min-width:

                290px;

        }

        .action-form {

            margin:

                0;

            padding:

                0;

            display:

                inline-flex;

            align-items:

                center;

        }

        /* =====================================================

           EMPTY

        ===================================================== */

        .empty-data {

            text-align:

                center;

            padding:

                50px;

            color:

                #6b7280;

        }

        /* =====================================================

           RESPONSIVE

        ===================================================== */

        @media(

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

            .page-header {

                flex-direction:

                    column;

                align-items:

                    flex-start;

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

        <a href="tambah.php">

            + Tambah Layanan

        </a>

        <a href="kategori.php">

            Kelola Kategori

        </a>

        <a href="banner.php" class="active">

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

    <!-- =====================================================

         NOTIFIKASI

    ====================================================== -->

    <?php if (

        $successMessage !== ""

    ): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars(

                $successMessage,

                ENT_QUOTES | ENT_SUBSTITUTE,

                "UTF-8"

            ) ?>

        </div>

    <?php endif; ?>

    <!-- =====================================================

         HEADER

    ====================================================== -->

    <div class="page-header">

        <div>

            <h1>

                Kelola Banner

            </h1>

            <p>

                Atur banner slider untuk hero Portal Layanan SDM PUSRI.

            </p>

        </div>

        <a

            href="tambah-banner.php"

            class="btn btn-primary"

        >

            + Tambah Banner

        </a>

    </div>

    <!-- =====================================================

         TABLE

    ====================================================== -->

    <div class="card">

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>

                            No

                        </th>

                        <th>

                            Preview

                        </th>

                        <th>

                            Judul

                        </th>

                        <th>

                            Subtitle

                        </th>

                        <th>

                            Urutan

                        </th>

                        <th>

                            Status

                        </th>

                        <th>

                            Aksi

                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (

                    count(

                        $banners

                    ) > 0

                ): ?>

                    <?php

                    $no = 1;

                    ?>

                    <?php foreach (

                        $banners as $row

                    ): ?>

                        <?php

                        /*

                        |--------------------------------------------------------------------------

                        | PRESIGNED URL MINIO

                        |--------------------------------------------------------------------------

                        */

                        $bannerUrl =

                            "";

                        if (

                            !empty(

                                $row["image"]

                            )

                        ) {

                            try {

                                $bannerUrl =

                                    getMinioUrl(

                                        $s3,

                                        $minioBucket,

                                        $row["image"]

                                    );

                            } catch (Throwable $e) {

                                /*

                                |--------------------------------------------------------------------------

                                | Jangan rusak seluruh halaman hanya karena

                                | satu gambar MinIO gagal.

                                |--------------------------------------------------------------------------

                                */

                                error_log(

                                    "Portal Layanan SDM PUSRI Banner Image URL Error ID "

                                    . (int)

                                    $row["id"]

                                    . ": "

                                    . get_class($e)

                                    . ": "

                                    . $e->getMessage()

                                );

                                $bannerUrl =

                                    "";

                            }

                        }

                        ?>

                        <tr>

                            <!-- NO -->

                            <td>

                                <?= $no++; ?>

                            </td>

                            <!-- PREVIEW -->

                            <td>

                                <div class="banner-image">

                                    <?php if (

                                        $bannerUrl !== ""

                                    ): ?>

                                        <img

                                            src="<?= htmlspecialchars(

                                                $bannerUrl,

                                                ENT_QUOTES | ENT_SUBSTITUTE,

                                                "UTF-8"

                                            ) ?>"

                                            alt="<?= htmlspecialchars(

                                                $row["title"],

                                                ENT_QUOTES | ENT_SUBSTITUTE,

                                                "UTF-8"

                                            ) ?>"

                                            loading="lazy"

                                            decoding="async"

                                        >

                                    <?php else: ?>

                                        Tidak ada gambar

                                    <?php endif; ?>

                                </div>

                            </td>

                            <!-- JUDUL -->

                            <td class="banner-title">

                                <?= htmlspecialchars(

                                    $row["title"],

                                    ENT_QUOTES | ENT_SUBSTITUTE,

                                    "UTF-8"

                                ) ?>

                            </td>

                            <!-- SUBTITLE -->

                            <td class="banner-subtitle">

                                <?= htmlspecialchars(

                                    !empty(

                                        $row["subtitle"]

                                    )

                                        ? $row["subtitle"]

                                        : "-",

                                    ENT_QUOTES | ENT_SUBSTITUTE,

                                    "UTF-8"

                                ) ?>

                            </td>

                            <!-- URUTAN -->

                            <td>

                                <?= (int) (

                                    $row["sort_order"]

                                    ?? 0

                                ) ?>

                            </td>

                            <!-- STATUS -->

                            <td>

                                <?php if (

                                    (int) (

                                        $row["is_active"]

                                        ?? 0

                                    ) === 1

                                ): ?>

                                    <span

                                        class="

                                            status

                                            status-active

                                        "

                                    >

                                        Aktif

                                    </span>

                                <?php else: ?>

                                    <span

                                        class="

                                            status

                                            status-inactive

                                        "

                                    >

                                        Tidak Aktif

                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- AKSI -->

                            <td>

                                <div class="action-buttons">

                                    <!-- EDIT -->

                                    <a

                                        href="edit-banner.php?id=<?= (int)

                                            $row["id"]

                                        ?>"

                                        class="btn btn-edit"

                                    >

                                        Edit

                                    </a>

                                    <!-- =====================================

                                         STATUS - POST + CSRF

                                    ====================================== -->

                                    <?php if (

                                        (int) (

                                            $row["is_active"]

                                            ?? 0

                                        ) === 1

                                    ): ?>

                                        <form

                                            method="POST"

                                            action="status-banner.php"

                                            class="action-form"

                                            onsubmit="

                                                return confirm(

                                                    'Nonaktifkan banner ini? Banner tidak akan tampil di portal.'

                                                );

                                            "

                                        >

                                            <?= csrfField() ?>

                                            <input

                                                type="hidden"

                                                name="id"

                                                value="<?= (int)

                                                    $row["id"]

                                                ?>"

                                            >

                                            <button

                                                type="submit"

                                                class="btn btn-disable"

                                            >

                                                Nonaktifkan

                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <form

                                            method="POST"

                                            action="status-banner.php"

                                            class="action-form"

                                            onsubmit="

                                                return confirm(

                                                    'Aktifkan kembali banner ini?'

                                                );

                                            "

                                        >

                                            <?= csrfField() ?>

                                            <input

                                                type="hidden"

                                                name="id"

                                                value="<?= (int)

                                                    $row["id"]

                                                ?>"

                                            >

                                            <button

                                                type="submit"

                                                class="btn btn-enable"

                                            >

                                                Aktifkan

                                            </button>

                                        </form>

                                    <?php endif; ?>

                                    <!-- =====================================

                                         HAPUS - POST + CSRF

                                    ====================================== -->

                                    <form

                                        method="POST"

                                        action="hapus-banner.php"

                                        class="action-form"

                                        onsubmit="

                                            return confirm(

                                                'PERINGATAN: Banner akan dihapus permanen beserta gambarnya. Apakah Anda yakin ingin menghapus banner ini?'

                                            );

                                        "

                                    >

                                        <?= csrfField() ?>

                                        <input

                                            type="hidden"

                                            name="id"

                                            value="<?= (int)

                                                $row["id"]

                                            ?>"

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

                            colspan="7"

                            class="empty-data"

                        >

                            Belum ada banner.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

</body>

</html>
