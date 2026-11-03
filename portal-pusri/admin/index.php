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
| DATA ADMIN
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
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function e(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| URL AMAN
|--------------------------------------------------------------------------
*/

function getSafeApplicationUrl(
    mixed $value
): ?string {

    if (
        !is_string(
            $value
        )
    ) {

        return null;
    }


    $url =
        trim(
            $value
        );


    if (
        $url === ""
        ||
        strlen(
            $url
        ) > 2048
    ) {

        return null;
    }


    if (
        filter_var(
            $url,
            FILTER_VALIDATE_URL
        ) === false
    ) {

        return null;
    }


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

        return null;
    }


    return $url;
}


/*
|--------------------------------------------------------------------------
| INITIAL ICON
|--------------------------------------------------------------------------
*/

function getApplicationInitial(
    mixed $name
): string {

    $name =
        trim(
            (string) $name
        );


    if (
        $name === ""
    ) {

        return "?";
    }


    if (
        function_exists(
            "mb_substr"
        )
    ) {

        $initial =
            mb_substr(
                $name,
                0,
                1,
                "UTF-8"
            );

    } else {

        $initial =
            substr(
                $name,
                0,
                1
            );
    }


    if (
        function_exists(
            "mb_strtoupper"
        )
    ) {

        return mb_strtoupper(
            $initial,
            "UTF-8"
        );
    }


    return strtoupper(
        $initial
    );
}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$applications =
    [];

$categoryNames =
    [];


/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

$totalApps =
    0;

$totalActive =
    0;

$totalInactive =
    0;

$totalCategories =
    0;

$totalActiveBanners =
    0;


/*
|--------------------------------------------------------------------------
| AMBIL APLIKASI
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT
            applications.id,
            applications.name,
            applications.description,
            applications.url,
            applications.icon,
            applications.is_active,
            applications.sort_order,
            categories.name AS category_name

        FROM applications

        LEFT JOIN categories
            ON applications.category_id = categories.id

        ORDER BY
            applications.sort_order ASC,
            applications.id DESC
    ";


    $result =
        $conn->query(
            $sql
        );


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $applications[] =
            $row;


        $totalApps++;


        if (
            (int) (
                $row["is_active"]
                ?? 0
            ) === 1
        ) {

            $totalActive++;

        } else {

            $totalInactive++;
        }


        $categoryName =
            trim(
                (string) (
                    $row["category_name"]
                    ?? ""
                )
            );


        if (
            $categoryName !== ""
            &&
            !in_array(
                $categoryName,
                $categoryNames,
                true
            )
        ) {

            $categoryNames[] =
                $categoryName;
        }
    }


} catch (Throwable $e) {

    error_log(
        "Portal SDM Admin Dashboard Application Query Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    http_response_code(
        500
    );


    die(
        "Data layanan tidak dapat dimuat."
    );
}


/*
|--------------------------------------------------------------------------
| TOTAL KATEGORI
|--------------------------------------------------------------------------
*/

try {

    $categoryResult =
        $conn->query(
            "
            SELECT COUNT(*) AS total
            FROM categories
            "
        );


    $categoryRow =
        $categoryResult->fetch_assoc();


    $totalCategories =
        (int) (
            $categoryRow["total"]
            ?? 0
        );


} catch (Throwable $e) {

    error_log(
        "Portal SDM Admin Category Count Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    $totalCategories =
        0;
}


/*
|--------------------------------------------------------------------------
| BANNER AKTIF
|--------------------------------------------------------------------------
*/

try {

    $bannerResult =
        $conn->query(
            "
            SELECT COUNT(*) AS total
            FROM banners
            WHERE is_active = 1
            "
        );


    $bannerRow =
        $bannerResult->fetch_assoc();


    $totalActiveBanners =
        (int) (
            $bannerRow["total"]
            ?? 0
        );


} catch (Throwable $e) {

    error_log(
        "Portal SDM Admin Banner Count Error: "
        . get_class($e)
        . ": "
        . $e->getMessage()
    );


    $totalActiveBanners =
        0;
}


/*
|--------------------------------------------------------------------------
| URUTKAN KATEGORI UNTUK FILTER
|--------------------------------------------------------------------------
*/

natcasesort(
    $categoryNames
);


$categoryNames =
    array_values(
        $categoryNames
    );


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
        "Layanan berhasil ditambahkan.";

} elseif (
    $successType === "edit"
) {

    $successMessage =
        "Layanan berhasil diperbarui.";

} elseif (
    $successType === "hapus"
) {

    $successMessage =
        "Layanan berhasil dihapus.";

} elseif (
    $successType === "status"
) {

    $successMessage =
        "Status layanan berhasil diubah.";
}

/* DAFTAR ADMIN: role realm ADMIN, sesuai proses kelola-admin.php. */
$dashboardAdmins = [];
$dashboardAdminError = "";
$dashboardAdminHasNext = false;
$dashboardAdminPageSize = 20;
$dashboardAdminPage = filter_var(
    $_GET["admin_page"] ?? 1,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1, "max_range" => 100000]]
);
if ($dashboardAdminPage === false) {
    $dashboardAdminPage = 1;
}
$dashboardAdminOffset = ($dashboardAdminPage - 1) * $dashboardAdminPageSize;

try {
    require_once __DIR__ . "/../config/keycloak-admin.php";

    // Satu akun tambahan dipakai untuk mendeteksi halaman berikutnya.
    $dashboardAdminPath = "/admin/realms/"
        . rawurlencode($keycloakAdminRealm)
        . "/roles/ADMIN/users?"
        . http_build_query([
            "first" => $dashboardAdminOffset,
            "max" => $dashboardAdminPageSize + 1,
            "briefRepresentation" => "false",
        ]);

    $dashboardAdminResponse = keycloakAdminRequest("GET", $dashboardAdminPath);
    $dashboardAdminHttpStatus = (int) ($dashboardAdminResponse["status"] ?? 0);

    if ($dashboardAdminHttpStatus !== 200) {
        if ($dashboardAdminHttpStatus === 403) {
            $dashboardAdminError = "Daftar admin belum dapat ditampilkan karena akses membaca akun belum diizinkan.";
        } elseif ($dashboardAdminHttpStatus === 404) {
            $dashboardAdminError = "Daftar admin belum dapat ditampilkan karena sumber data admin tidak ditemukan.";
        }
        throw new RuntimeException("Permintaan daftar admin gagal.", $dashboardAdminHttpStatus);
    }

    $dashboardAdminPayload = $dashboardAdminResponse["json"] ?? null;
    $dashboardAdminRawBody = ltrim((string) ($dashboardAdminResponse["body"] ?? ""));
    if (!is_array($dashboardAdminPayload) || substr($dashboardAdminRawBody, 0, 1) !== "[") {
        throw new UnexpectedValueException("Format daftar admin tidak valid.");
    }

    foreach ($dashboardAdminPayload as $dashboardAdminRecord) {
        if (
            !is_array($dashboardAdminRecord)
            || !isset($dashboardAdminRecord["id"], $dashboardAdminRecord["username"])
            || !is_string($dashboardAdminRecord["id"])
            || !is_string($dashboardAdminRecord["username"])
        ) {
            throw new UnexpectedValueException("Data akun admin tidak valid.");
        }
    }

    $dashboardAdminHasNext = count($dashboardAdminPayload) > $dashboardAdminPageSize;
    $dashboardAdmins = array_slice($dashboardAdminPayload, 0, $dashboardAdminPageSize);
} catch (Throwable $dashboardAdminException) {
    $dashboardAdmins = [];
    $dashboardAdminHasNext = false;
    if ($dashboardAdminError === "") {
        $dashboardAdminError = "Daftar admin belum dapat dimuat. Silakan coba muat ulang halaman beberapa saat lagi.";
    }
    // Jangan mencatat token, credential, body respons, atau profil akun.
    error_log(
        "Portal PUSRI Dashboard Admin List: "
        . get_class($dashboardAdminException)
        . " (code " . (int) $dashboardAdminException->getCode() . ")"
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
        Admin Portal Layanan SDM PUSRI
    </title>


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            min-width: 320px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                #f5f7fb;

            color:
                #172033;

            line-height:
                1.5;
        }


        a {
            text-decoration: none;
        }


        button,
        input,
        select {
            font-family: inherit;
        }


        button {
            border: none;
        }


        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline:
                3px solid
                rgba(
                    13,
                    99,
                    216,
                    0.28
                );

            outline-offset: 2px;
        }



        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            position: fixed;

            top: 0;
            left: 0;

            width: 230px;
            height: 100vh;

            padding:
                24px
                14px;

            display: flex;

            flex-direction: column;

            z-index: 1000;

            overflow-y: auto;

            background:
                #17365d;

            color:
                #ffffff;
        }



        /* =====================================================
           SIDEBAR BRAND
        ===================================================== */

        .sidebar-brand {
            padding:
                4px
                10px
                25px;

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

            color:
                #ffffff;

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



        /* =====================================================
           SIDEBAR MENU
        ===================================================== */

        .sidebar-menu {
            display: flex;

            flex-direction: column;

            gap: 6px;
        }


        .sidebar-menu a {
            position: relative;

            min-height: 44px;

            padding:
                0
                14px;

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

            color:
                #ffffff;
        }


        .sidebar-menu a.active {
            background:
                #28558a;

            color:
                #ffffff;

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

            background:
                #f4c400;
        }



        /* =====================================================
           SIDEBAR USER
        ===================================================== */

        .sidebar-user {
            margin-top: auto;

            padding:
                18px
                10px
                0;

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

            color:
                #ffffff;

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

            background:
                #ffffff;

            color:
                #17365d;

            font-size: 12px;

            font-weight: 700;

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

            min-height:
                100vh;

            padding:
                30px;
        }



        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .breadcrumb {
            margin-bottom: 7px;

            color:
                #8a96a5;

            font-size: 11px;
        }


        .breadcrumb strong {
            color:
                #53667e;
        }



        /* =====================================================
           HEADER
        ===================================================== */

        .page-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 22px;
        }


        .page-header h1 {
            color:
                #172033;

            font-size: 29px;

            line-height: 1.2;
        }


        .page-header p {
            margin-top: 5px;

            color:
                #748196;

            font-size: 13px;
        }


        .btn-add {
            min-height: 42px;

            padding:
                0
                17px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            border-radius: 9px;

            background:
                #17365d;

            color:
                #ffffff;

            font-size: 12px;

            font-weight: 700;

            transition:
                background 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .btn-add:hover {
            background:
                #28558a;

            transform:
                translateY(-1px);

            box-shadow:
                0
                6px
                14px
                rgba(
                    23,
                    54,
                    93,
                    0.16
                );
        }



        /* =====================================================
           SUCCESS
        ===================================================== */

        .alert-success {
            margin-bottom: 20px;

            padding:
                13px
                16px;

            border:
                1px solid
                #b7ddcb;

            border-radius: 9px;

            background:
                #e6f5ed;

            color:
                #14633c;

            font-size: 13px;
        }



        /* =====================================================
           INFO
        ===================================================== */

        .info-box {
            margin-bottom: 22px;

            padding:
                13px
                16px;

            display: flex;

            align-items: flex-start;

            gap: 12px;

            border:
                1px solid
                #c8dcf5;

            border-radius: 10px;

            background:
                #edf5ff;
        }


        .info-icon {
            width: 30px;
            height: 30px;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            border-radius: 50%;

            background:
                #dbeafe;

            color:
                #174f91;

            font-size: 15px;

            font-weight: 700;
        }


        .info-content strong {
            display: block;

            margin-bottom: 3px;

            color:
                #174f91;

            font-size: 12px;
        }


        .info-content p {
            color:
                #4b6585;

            font-size: 12px;

            line-height: 1.55;
        }



        /* =====================================================
           STATISTICS
        ===================================================== */

        .statistics {
            display: grid;

            grid-template-columns:
                repeat(
                    5,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 14px;

            margin-bottom: 24px;
        }


        .stat-card {
            position: relative;

            min-height: 122px;

            padding: 18px;

            overflow: hidden;

            border:
                1px solid
                #e4e9ef;

            border-radius: 13px;

            background:
                #ffffff;

            box-shadow:
                0
                4px
                14px
                rgba(
                    15,
                    23,
                    42,
                    0.035
                );
        }


        .stat-card::before {
            content: "";

            position: absolute;

            top: 0;
            left: 0;
            right: 0;

            height: 4px;

            background:
                var(
                    --stat-color,
                    #17365d
                );
        }


        .stat-icon {
            width: 34px;
            height: 34px;

            margin-bottom: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background:
                var(
                    --stat-bg,
                    #eef4fa
                );

            color:
                var(
                    --stat-color,
                    #17365d
                );

            font-size: 15px;

            font-weight: 700;
        }


        .stat-card span {
            display: block;

            margin-bottom: 4px;

            color:
                #7c8796;

            font-size: 11px;
        }


        .stat-card strong {
            color:
                var(
                    --stat-color,
                    #17365d
                );

            font-size: 26px;
        }


        .stat-total {
            --stat-color:
                #17365d;

            --stat-bg:
                #edf3f9;
        }


        .stat-active {
            --stat-color:
                #16864f;

            --stat-bg:
                #e7f6ee;
        }


        .stat-inactive {
            --stat-color:
                #d83442;

            --stat-bg:
                #fdecee;
        }


        .stat-category {
            --stat-color:
                #7b52c0;

            --stat-bg:
                #f1ecfa;
        }


        .stat-banner {
            --stat-color:
                #b87800;

            --stat-bg:
                #fff5dc;
        }



        /* =====================================================
           CONTENT CARD
        ===================================================== */

        .card {
            border:
                1px solid
                #e5eaf0;

            border-radius: 15px;

            background:
                #ffffff;

            box-shadow:
                0
                5px
                18px
                rgba(
                    15,
                    23,
                    42,
                    0.04
                );

            overflow: hidden;
        }



        /* =====================================================
           CARD HEADER
        ===================================================== */

        .card-header {
            padding:
                22px
                24px
                17px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            border-bottom:
                1px solid
                #eef1f4;
        }


        .card-header h2 {
            color:
                #172033;

            font-size: 20px;
        }


        .card-header p {
            margin-top: 4px;

            color:
                #7a8798;

            font-size: 12px;
        }



        /* =====================================================
           FILTER BAR
        ===================================================== */

        .filter-bar {
            padding:
                14px
                24px;

            display: grid;

            grid-template-columns:
                minmax(
                    220px,
                    1fr
                )
                180px
                200px;

            gap: 10px;

            background:
                #fbfcfd;

            border-bottom:
                1px solid
                #eef1f4;
        }


        .search-wrapper {
            position: relative;
        }


        .search-wrapper::before {
            content: "⌕";

            position: absolute;

            left: 13px;
            top: 50%;

            transform:
                translateY(-50%);

            color:
                #789;

            font-size: 18px;

            pointer-events: none;
        }


        .filter-input,
        .filter-select {
            width: 100%;
            height: 40px;

            border:
                1px solid
                #dce3eb;

            border-radius: 8px;

            background:
                #ffffff;

            color:
                #26384d;

            font-size: 12px;

            outline: none;
        }


        .filter-input {
            padding:
                0
                13px
                0
                38px;
        }


        .filter-select {
            padding:
                0
                12px;
        }


        .filter-input:focus,
        .filter-select:focus {
            border-color:
                #87a9d0;

            box-shadow:
                0
                0
                0
                3px
                rgba(
                    40,
                    85,
                    138,
                    0.07
                );
        }



        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrapper {
            width: 100%;

            overflow-x: auto;
        }


        table {
            width: 100%;

            min-width: 990px;

            border-collapse: collapse;
        }


        thead th {
            position: sticky;

            top: 0;

            z-index: 3;

            padding:
                12px
                14px;

            background:
                #f6f8fa;

            color:
                #536174;

            text-align: left;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }


        tbody td {
            padding:
                14px;

            border-bottom:
                1px solid
                #eef1f4;

            color:
                #344257;

            font-size: 12px;

            vertical-align: middle;
        }


        tbody tr:last-child td {
            border-bottom: none;
        }


        tbody tr:hover td {
            background:
                #fbfcfe;
        }



        /* =====================================================
           TABLE ICON
        ===================================================== */

        .app-icon {
            width: 52px;
            height: 52px;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

            border:
                1px solid
                #e2e9f0;

            border-radius: 11px;

            background:
                #edf3f9;

            color:
                #56708f;

            font-size: 20px;

            font-weight: 700;
        }


        .app-icon img {
            width: 100% !important;
            height: 100% !important;

            object-fit: contain;

            padding: 5px;

            background:
                #ffffff;
        }



        /* =====================================================
           SERVICE NAME
        ===================================================== */

        .app-name {
            min-width: 210px;
        }


        .app-name strong {
            display: block;

            margin-bottom: 4px;

            color:
                #17365d;

            font-size: 13px;
        }


        .app-description {
            display: block;

            max-width: 310px;

            margin-bottom: 5px;

            color:
                #7a8797;

            font-size: 11px;

            line-height: 1.45;
        }


        .sort-order {
            display: inline-block;

            color:
                #9aa5b2;

            font-size: 10px;
        }



        /* =====================================================
           CATEGORY
        ===================================================== */

        .category-badge {
            display: inline-flex;

            max-width: 160px;

            padding:
                5px
                9px;

            border-radius: 20px;

            background:
                #edf3f9;

            color:
                #536b87;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }



        /* =====================================================
           STATUS
        ===================================================== */

        .status {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding:
                5px
                9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .status::before {
            content: "";

            width: 6px;
            height: 6px;

            border-radius: 50%;

            background:
                currentColor;
        }


        .status-active {
            background:
                #e5f4ec;

            color:
                #137448;
        }


        .status-inactive {
            background:
                #fbe9eb;

            color:
                #c32938;
        }



        /* =====================================================
           ACTION
        ===================================================== */

        .action-buttons {
            min-width: 275px;

            display: flex;

            align-items: center;

            gap: 5px;

            flex-wrap: wrap;
        }


        .action-form {
            margin: 0;

            display: inline-flex;
        }


        .btn {
            min-height: 32px;

            padding:
                0
                10px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            cursor: pointer;

            transition:
                background 0.18s ease,
                color 0.18s ease,
                border-color 0.18s ease;
        }


        .btn-view {
            border:
                1px solid
                #bcd2e7;

            background:
                #edf5fc;

            color:
                #195792;
        }


        .btn-view:hover {
            background:
                #dbeafa;
        }


        .btn-edit {
            border:
                1px solid
                #d3dce6;

            background:
                #ffffff;

            color:
                #465a72;
        }


        .btn-edit:hover {
            background:
                #f1f4f7;
        }


        .btn-disable {
            background:
                #667585;

            color:
                #ffffff;
        }


        .btn-disable:hover {
            background:
                #53606d;
        }


        .btn-enable {
            background:
                #18854f;

            color:
                #ffffff;
        }


        .btn-enable:hover {
            background:
                #116c3f;
        }


        .btn-delete {
            border:
                1px solid
                #efc2c7;

            background:
                #fff3f4;

            color:
                #cc2637;
        }


        .btn-delete:hover {
            background:
                #fce4e7;
        }


        .btn-disabled-link {
            border:
                1px solid
                #dde2e8;

            background:
                #f2f4f6;

            color:
                #9aa2ad;

            cursor:
                not-allowed;
        }



        /* =====================================================
           TABLE RESULT
        ===================================================== */

        .table-result {
            padding:
                11px
                24px;

            display: flex;

            align-items: center;

            justify-content: flex-end;

            border-top:
                1px solid
                #eef1f4;

            background:
                #fbfcfd;

            color:
                #8490a0;

            font-size: 10px;
        }



        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-data {
            padding:
                50px
                20px
                !important;

            text-align: center;

            color:
                #7c8795;
        }



        /* =====================================================
           RESPONSIVE 1200
        ===================================================== */

        @media(
            max-width: 1200px
        ) {

            .statistics {
                grid-template-columns:
                    repeat(
                        3,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }
        }



        /* =====================================================
           RESPONSIVE 900
        ===================================================== */

        @media(
            max-width: 900px
        ) {

            .statistics {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }


            .filter-bar {
                grid-template-columns:
                    1fr
                    1fr;
            }


            .search-wrapper {
                grid-column:
                    1 / -1;
            }
        }



        /* =====================================================
           MOBILE
        ===================================================== */

        @media(
            max-width: 768px
        ) {

            .sidebar {
                position: relative;

                width: 100%;
                height: auto;
            }


            .sidebar-user {
                margin-top: 24px;
            }


            .main {
                margin-left: 0;

                padding: 20px;
            }


            .page-header {
                align-items: flex-start;

                flex-direction: column;
            }


            .statistics {
                grid-template-columns:
                    1fr;
            }


            .filter-bar {
                grid-template-columns:
                    1fr;
            }


            .search-wrapper {
                grid-column:
                    auto;
            }
        }

        /* DAFTAR ADMIN */
        .dashboard-admin-card { margin-top: 24px; }
        .dashboard-admin-card .card-header { flex-wrap: wrap; }
        .dashboard-admin-table { min-width: 680px; }
        .dashboard-admin-table td { overflow-wrap: anywhere; }
        .dashboard-admin-table td:first-child { width: 56px; }
        .dashboard-admin-table td:last-child { white-space: nowrap; }
        .dashboard-admin-username { color: #17365d; font-weight: 700; }
        .dashboard-admin-self {
            display: inline-block; margin-left: 6px; padding: 2px 7px;
            border-radius: 20px; background: #edf3f9; color: #28558a;
            font-size: 10px; font-weight: 600;
        }
        .dashboard-admin-message { padding: 22px 24px; color: #748196; font-size: 13px; }
        .dashboard-admin-error {
            margin: 20px 24px; padding: 14px 16px; border: 1px solid #f1d89b;
            border-radius: 9px; background: #fff9eb; color: #77540b; font-size: 13px;
        }
        .dashboard-admin-footer {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px; padding: 14px 24px;
            border-top: 1px solid #eef1f4; color: #748196; font-size: 12px;
        }
        .dashboard-admin-pages { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
        .dashboard-admin-page-link {
            display: inline-flex; align-items: center; min-height: 36px;
            padding: 6px 12px; border: 1px solid #dce5f0; border-radius: 8px;
            background: #fff; color: #17365d; font-weight: 600;
        }
        .dashboard-admin-page-link:hover { background: #edf3f9; }

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


        <a
            href="index.php"
            class="active"
        >

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

            <?= e(
                $user["username"]
                ?? "Admin"
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


    <!-- SUCCESS -->

    <?php if (
        $successMessage !== ""
    ): ?>


        <div class="alert-success">

            <?= e(
                $successMessage
            ) ?>

        </div>


    <?php endif; ?>



    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

    <div class="breadcrumb">

        Portal Layanan SDM
        /
        <strong>
            Dashboard
        </strong>

    </div>



    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="page-header">


        <div>


            <h1>

                Dashboard

            </h1>


            <p>

                Selamat datang,
                <strong>

                    <?= e(
                        $user["username"]
                        ?? "Admin"
                    ) ?>

                </strong>.

                Kelola layanan pada Portal Layanan SDM PUSRI.

            </p>


        </div>


        <a
            href="tambah.php"
            class="btn-add"
        >

            + Tambah Layanan

        </a>


    </div>



    <!-- =====================================================
         INFO
    ====================================================== -->

    <div class="info-box">


        <div class="info-icon">

            i

        </div>


        <div class="info-content">


            <strong>

                Pengelolaan Layanan

            </strong>


            <p>

                Gunakan <b>Nonaktifkan</b>
                jika layanan hanya dihentikan sementara.

                Gunakan <b>Hapus</b>
                hanya untuk data yang benar-benar
                tidak diperlukan lagi.

            </p>


        </div>


    </div>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="statistics">


        <!-- TOTAL -->

        <div class="stat-card stat-total">


            <div class="stat-icon">

                Σ

            </div>


            <span>

                Total Layanan

            </span>


            <strong>

                <?= (int)
                    $totalApps
                ?>

            </strong>


        </div>



        <!-- ACTIVE -->

        <div class="stat-card stat-active">


            <div class="stat-icon">

                ✓

            </div>


            <span>

                Layanan Aktif

            </span>


            <strong>

                <?= (int)
                    $totalActive
                ?>

            </strong>


        </div>



        <!-- INACTIVE -->

        <div class="stat-card stat-inactive">


            <div class="stat-icon">

                !

            </div>


            <span>

                Tidak Aktif

            </span>


            <strong>

                <?= (int)
                    $totalInactive
                ?>

            </strong>


        </div>



        <!-- CATEGORY -->

        <div class="stat-card stat-category">


            <div class="stat-icon">

                #

            </div>


            <span>

                Total Kategori

            </span>


            <strong>

                <?= (int)
                    $totalCategories
                ?>

            </strong>


        </div>



        <!-- BANNER -->

        <div class="stat-card stat-banner">


            <div class="stat-icon">

                B

            </div>


            <span>

                Banner Aktif

            </span>


            <strong>

                <?= (int)
                    $totalActiveBanners
                ?>

            </strong>


        </div>


    </div>



    <!-- =====================================================
         TABLE CARD
    ====================================================== -->

    <div class="card">


        <!-- HEADER -->

        <div class="card-header">


            <div>


                <h2>

                    Daftar Layanan

                </h2>


                <p>

                    Seluruh layanan yang tersimpan
                    pada Portal Layanan SDM PUSRI.

                </p>


            </div>


        </div>



        <!-- =================================================
             FILTER
        ================================================== -->

        <div class="filter-bar">


            <!-- SEARCH -->

            <div class="search-wrapper">


                <input
                    type="text"
                    id="adminSearch"
                    class="filter-input"
                    placeholder="Cari nama, kategori, atau deskripsi..."
                    autocomplete="off"
                >


            </div>



            <!-- STATUS -->

            <select
                id="statusFilter"
                class="filter-select"
            >


                <option value="">

                    Semua Status

                </option>


                <option value="aktif">

                    Aktif

                </option>


                <option value="nonaktif">

                    Tidak Aktif

                </option>


            </select>



            <!-- CATEGORY -->

            <select
                id="categoryFilter"
                class="filter-select"
            >


                <option value="">

                    Semua Kategori

                </option>


                <?php foreach (
                    $categoryNames
                    as $categoryName
                ): ?>


                    <option
                        value="<?= e(
                            strtolower(
                                $categoryName
                            )
                        ) ?>"
                    >

                        <?= e(
                            $categoryName
                        ) ?>

                    </option>


                <?php endforeach; ?>


            </select>


        </div>



        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="table-wrapper">


            <table>


                <thead>


                    <tr>

                        <th>
                            No
                        </th>

                        <th>
                            Icon
                        </th>

                        <th>
                            Layanan
                        </th>

                        <th>
                            Kategori
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Aksi
                        </th>

                    </tr>


                </thead>



                <tbody id="applicationTableBody">


                <?php if (
                    count(
                        $applications
                    ) > 0
                ): ?>


                    <?php
                    $no = 1;
                    ?>


                    <?php foreach (
                        $applications
                        as $row
                    ): ?>


                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | ICON MINIO
                        |--------------------------------------------------------------------------
                        */

                        $iconSrc = "";


                        if (
                            !empty(
                                $row["icon"]
                            )
                        ) {

                            try {

                                $iconSrc =
                                    getMinioUrl(
                                        $s3,
                                        $minioBucket,
                                        $row["icon"]
                                    );


                            } catch (Throwable $e) {

                                error_log(
                                    "Portal SDM Dashboard Icon Error ID "
                                    . (int)
                                    $row["id"]
                                    . ": "
                                    . get_class($e)
                                    . ": "
                                    . $e->getMessage()
                                );


                                $iconSrc = "";
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | SAFE URL
                        |--------------------------------------------------------------------------
                        */

                        $safeApplicationUrl =
                            getSafeApplicationUrl(
                                $row["url"]
                                ?? null
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | CATEGORY
                        |--------------------------------------------------------------------------
                        */

                        $rowCategory =
                            !empty(
                                $row["category_name"]
                            )
                                ? (string)
                                    $row["category_name"]
                                : "Tanpa Kategori";


                        /*
                        |--------------------------------------------------------------------------
                        | STATUS
                        |--------------------------------------------------------------------------
                        */

                        $rowStatus =
                            (int) (
                                $row["is_active"]
                                ?? 0
                            ) === 1
                                ? "aktif"
                                : "nonaktif";


                        /*
                        |--------------------------------------------------------------------------
                        | SEARCH TEXT
                        |--------------------------------------------------------------------------
                        */

                        $searchText =
                            strtolower(
                                trim(
                                    (string) (
                                        $row["name"]
                                        ?? ""
                                    )
                                    . " "
                                    . (string) (
                                        $row["description"]
                                        ?? ""
                                    )
                                    . " "
                                    . $rowCategory
                                )
                            );

                        ?>


                        <tr
                            class="application-row"
                            data-search="<?= e(
                                $searchText
                            ) ?>"
                            data-category="<?= e(
                                strtolower(
                                    $rowCategory
                                )
                            ) ?>"
                            data-status="<?= e(
                                $rowStatus
                            ) ?>"
                        >


                            <!-- NUMBER -->

                            <td>

                                <?= $no++; ?>

                            </td>



                            <!-- ICON -->

                            <td>


                                <div class="app-icon">


                                    <?php if (
                                        $iconSrc !== ""
                                    ): ?>


                                        <img
                                            src="<?= e(
                                                $iconSrc
                                            ) ?>"
                                            alt="<?= e(
                                                $row["name"]
                                                ?? ""
                                            ) ?>"
                                            loading="lazy"
                                            decoding="async"
                                        >


                                    <?php else: ?>


                                        <?= e(
                                            getApplicationInitial(
                                                $row["name"]
                                                ?? ""
                                            )
                                        ) ?>


                                    <?php endif; ?>


                                </div>


                            </td>



                            <!-- SERVICE -->

                            <td class="app-name">


                                <strong>

                                    <?= e(
                                        $row["name"]
                                        ?? ""
                                    ) ?>

                                </strong>


                                <span class="app-description">

                                    <?= e(
                                        !empty(
                                            $row["description"]
                                        )
                                            ? $row["description"]
                                            : "Tidak ada deskripsi."
                                    ) ?>

                                </span>


                                <span class="sort-order">

                                    Urutan:

                                    <?= (int) (
                                        $row["sort_order"]
                                        ?? 0
                                    ) ?>

                                </span>


                            </td>



                            <!-- CATEGORY -->

                            <td>


                                <span class="category-badge">

                                    <?= e(
                                        $rowCategory
                                    ) ?>

                                </span>


                            </td>



                            <!-- STATUS -->

                            <td>


                                <?php if (
                                    $rowStatus === "aktif"
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



                            <!-- ACTION -->

                            <td>


                                <div class="action-buttons">



                                    <!-- OPEN -->

                                    <?php if (
                                        $safeApplicationUrl
                                        !== null
                                    ): ?>


                                        <a
                                            href="<?= e(
                                                $safeApplicationUrl
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="
                                                btn
                                                btn-view
                                            "
                                        >

                                            Buka

                                        </a>


                                    <?php else: ?>


                                        <span
                                            class="
                                                btn
                                                btn-disabled-link
                                            "
                                            title="URL layanan tidak valid"
                                        >

                                            URL Invalid

                                        </span>


                                    <?php endif; ?>



                                    <!-- EDIT -->

                                    <a
                                        href="edit.php?id=<?= (int)
                                            $row["id"]
                                        ?>"
                                        class="
                                            btn
                                            btn-edit
                                        "
                                    >

                                        Edit

                                    </a>



                                    <!-- STATUS -->

                                    <?php if (
                                        $rowStatus === "aktif"
                                    ): ?>


                                        <form
                                            method="POST"
                                            action="status.php"
                                            class="action-form"
                                            onsubmit="
                                                return confirm(
                                                    'Nonaktifkan layanan ini? Layanan tidak akan tampil di portal tetapi datanya tetap tersimpan.'
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
                                                class="
                                                    btn
                                                    btn-disable
                                                "
                                            >

                                                Nonaktifkan

                                            </button>


                                        </form>


                                    <?php else: ?>


                                        <form
                                            method="POST"
                                            action="status.php"
                                            class="action-form"
                                            onsubmit="
                                                return confirm(
                                                    'Aktifkan kembali layanan ini?'
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
                                                class="
                                                    btn
                                                    btn-enable
                                                "
                                            >

                                                Aktifkan

                                            </button>


                                        </form>


                                    <?php endif; ?>



                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        action="hapus.php"
                                        class="action-form"
                                        onsubmit="
                                            return confirm(
                                                'PERINGATAN: Layanan akan dihapus permanen. Jika hanya sementara tidak digunakan, pilih Nonaktifkan. Tetap hapus layanan ini?'
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
                                            class="
                                                btn
                                                btn-delete
                                            "
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
                            colspan="6"
                            class="empty-data"
                        >

                            Belum ada layanan
                            yang tersimpan.

                        </td>


                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>



        <!-- =================================================
             RESULT COUNT
        ================================================== -->

        <div class="table-result">

            <span id="tableResultCount">

                <?= (int)
                    count(
                        $applications
                    )
                ?>
                layanan ditampilkan

            </span>

        </div>


    </div>


    <!-- DAFTAR ADMIN -->
    <section class="card dashboard-admin-card" id="daftar-admin" aria-labelledby="dashboardAdminTitle">
        <div class="card-header">
            <div>
                <h2 id="dashboardAdminTitle">Daftar Admin</h2>
                <p>Akun administrator Portal Layanan SDM PUSRI.</p>
            </div>
            <a href="kelola-admin.php" class="btn-add">+ Tambah Admin</a>
        </div>

        <?php if ($dashboardAdminError !== ""): ?>
            <div class="dashboard-admin-error" role="alert"><?= e($dashboardAdminError) ?></div>
            <div class="dashboard-admin-footer">
                <span>Data layanan tetap dapat digunakan.</span>
                <a class="dashboard-admin-page-link" href="index.php?admin_page=<?= (int) $dashboardAdminPage ?>#daftar-admin">Coba lagi</a>
            </div>
        <?php else: ?>
            <?php if (count($dashboardAdmins) > 0): ?>
                <div class="table-wrapper">
                    <table class="dashboard-admin-table" aria-labelledby="dashboardAdminTitle">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Username</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Email</th>
                                <th scope="col">Status Akun</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboardAdmins as $dashboardAdminIndex => $dashboardAdmin): ?>
                                <?php
                                $dashboardAdminName = trim(
                                    (string) ($dashboardAdmin["firstName"] ?? "") . " "
                                    . (string) ($dashboardAdmin["lastName"] ?? "")
                                );
                                $dashboardAdminEmail = trim((string) ($dashboardAdmin["email"] ?? ""));
                                $dashboardAdminIsSelf = !empty($user["sub"])
                                    && (string) $user["sub"] === $dashboardAdmin["id"];
                                $dashboardAdminEnabled = $dashboardAdmin["enabled"] ?? null;
                                ?>
                                <tr>
                                    <td><?= (int) ($dashboardAdminOffset + $dashboardAdminIndex + 1) ?></td>
                                    <td>
                                        <span class="dashboard-admin-username"><?= e($dashboardAdmin["username"]) ?></span>
                                        <?php if ($dashboardAdminIsSelf): ?><span class="dashboard-admin-self">Anda</span><?php endif; ?>
                                    </td>
                                    <td><?= e($dashboardAdminName !== "" ? $dashboardAdminName : "—") ?></td>
                                    <td><?= e($dashboardAdminEmail !== "" ? $dashboardAdminEmail : "—") ?></td>
                                    <td>
                                        <?php if ($dashboardAdminEnabled === true): ?>
                                            <span class="status status-active">Aktif</span>
                                        <?php elseif ($dashboardAdminEnabled === false): ?>
                                            <span class="status status-inactive">Tidak Aktif</span>
                                        <?php else: ?>
                                            <span class="category-badge">Tidak diketahui</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="dashboard-admin-message">
                    <?= $dashboardAdminPage === 1 ? "Belum ada admin yang terdaftar." : "Tidak ada admin pada halaman ini." ?>
                </p>
            <?php endif; ?>

            <div class="dashboard-admin-footer">
                <span><?= count($dashboardAdmins) ?> admin ditampilkan pada halaman ini</span>
                <nav class="dashboard-admin-pages" aria-label="Halaman daftar admin">
                    <?php if ($dashboardAdminPage > 1): ?>
                        <a class="dashboard-admin-page-link" href="index.php?admin_page=<?= (int) ($dashboardAdminPage - 1) ?>#daftar-admin">Sebelumnya</a>
                    <?php endif; ?>
                    <span>Halaman <?= (int) $dashboardAdminPage ?></span>
                    <?php if ($dashboardAdminHasNext): ?>
                        <a class="dashboard-admin-page-link" href="index.php?admin_page=<?= (int) ($dashboardAdminPage + 1) ?>#daftar-admin">Berikutnya</a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </section>

</main>



<!-- =========================================================
     FILTER JAVASCRIPT
========================================================= -->

<script>

"use strict";


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchInput =
            document.getElementById(
                "adminSearch"
            );


        const statusFilter =
            document.getElementById(
                "statusFilter"
            );


        const categoryFilter =
            document.getElementById(
                "categoryFilter"
            );


        const resultCount =
            document.getElementById(
                "tableResultCount"
            );


        const rows =
            Array.from(
                document.querySelectorAll(
                    ".application-row"
                )
            );


        function normalizeText(
            value
        ) {

            return String(
                value
                ?? ""
            )
                .toLocaleLowerCase(
                    "id-ID"
                )
                .trim();
        }


        function filterRows() {

            const keyword =
                normalizeText(
                    searchInput
                        ? searchInput.value
                        : ""
                );


            const status =
                normalizeText(
                    statusFilter
                        ? statusFilter.value
                        : ""
                );


            const category =
                normalizeText(
                    categoryFilter
                        ? categoryFilter.value
                        : ""
                );


            let visible =
                0;


            rows.forEach(
                function (row) {


                    const rowSearch =
                        normalizeText(
                            row.dataset.search
                        );


                    const rowStatus =
                        normalizeText(
                            row.dataset.status
                        );


                    const rowCategory =
                        normalizeText(
                            row.dataset.category
                        );


                    const matchesSearch =
                        keyword === ""
                        ||
                        rowSearch.includes(
                            keyword
                        );


                    const matchesStatus =
                        status === ""
                        ||
                        rowStatus
                        === status;


                    const matchesCategory =
                        category === ""
                        ||
                        rowCategory
                        === category;


                    const show =
                        matchesSearch
                        &&
                        matchesStatus
                        &&
                        matchesCategory;


                    row.hidden =
                        !show;


                    if (
                        show
                    ) {

                        visible++;
                    }
                }
            );


            if (
                resultCount
            ) {

                resultCount.textContent =
                    visible
                    +
                    " layanan ditampilkan";
            }
        }


        if (
            searchInput
        ) {

            searchInput.addEventListener(
                "input",
                filterRows
            );
        }


        if (
            statusFilter
        ) {

            statusFilter.addEventListener(
                "change",
                filterRows
            );
        }


        if (
            categoryFilter
        ) {

            categoryFilter.addEventListener(
                "change",
                filterRows
            );
        }


        filterRows();


    }
);

</script>


</body>

</html>