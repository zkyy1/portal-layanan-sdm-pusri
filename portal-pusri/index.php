<?php

/*
|--------------------------------------------------------------------------
| PORTAL SDM PUSRI
|--------------------------------------------------------------------------
|
| Halaman utama bersifat PUBLIC.
|
| Semua pengunjung dapat:
|
| - Melihat banner
| - Melihat daftar aplikasi
| - Mencari aplikasi
| - Filter berdasarkan kategori
| - Membuka aplikasi
|
| Login hanya digunakan oleh ADMIN.
|
*/


/*
|--------------------------------------------------------------------------
| SESSION / AUTH
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/auth.php";


/*
|--------------------------------------------------------------------------
| USER SAAT INI
|--------------------------------------------------------------------------
*/

$user =
    currentUser();


$isAdmin =
    hasRole(
        "ADMIN"
    );


/*
|--------------------------------------------------------------------------
| DATABASE + MINIO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/minio.php";
require_once __DIR__ . "/includes/minio-functions.php";


/*
|--------------------------------------------------------------------------
| HELPER ESCAPE
|--------------------------------------------------------------------------
*/

function portalEscape(
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
| HELPER URL APLIKASI
|--------------------------------------------------------------------------
*/

function portalSafeApplicationUrl(
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
| HELPER INITIAL ICON
|--------------------------------------------------------------------------
*/

function portalApplicationInitial(
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
| VERSION CSS + JS
|--------------------------------------------------------------------------
*/

$cssFile =
    __DIR__
    . "/assets/css/style.css";


$jsFile =
    __DIR__
    . "/assets/js/script.js";


$cssVersion =
    is_file(
        $cssFile
    )
        ? (int) filemtime(
            $cssFile
        )
        : 1;


$jsVersion =
    is_file(
        $jsFile
    )
        ? (int) filemtime(
            $jsFile
        )
        : 1;


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$banners = [];

$applications = [];

$categories = [];


/*
|--------------------------------------------------------------------------
| AMBIL BANNER AKTIF
|--------------------------------------------------------------------------
*/

try {

    $sqlBanner = "

        SELECT
            id,
            title,
            subtitle,
            image,
            sort_order

        FROM banners

        WHERE is_active = 1

        ORDER BY
            sort_order ASC,
            id ASC

    ";


    $resultBanner =
        $conn->query(
            $sqlBanner
        );


    if (
        !$resultBanner
    ) {

        throw new RuntimeException(
            "Query banner gagal."
        );
    }


    while (
        $banner =
            $resultBanner->fetch_assoc()
    ) {

        $bannerUrl = "";


        /*
        |--------------------------------------------------------------------------
        | MINIO URL
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $banner["image"]
            )
        ) {

            try {

                $bannerUrl =
                    getMinioUrl(
                        $s3,
                        $minioBucket,
                        $banner["image"]
                    );

            } catch (
                Throwable $e
            ) {

                error_log(
                    "Portal Banner MinIO Error: "
                    . get_class(
                        $e
                    )
                    . ": "
                    . $e->getMessage()
                );


                $bannerUrl = "";
            }
        }


        $banner["image_url"] =
            $bannerUrl;


        $banners[] =
            $banner;
    }


} catch (
    Throwable $e
) {

    error_log(
        "Portal Banner Error: "
        . get_class(
            $e
        )
        . ": "
        . $e->getMessage()
    );


    $banners = [];
}


/*
|--------------------------------------------------------------------------
| FALLBACK BANNER
|--------------------------------------------------------------------------
*/

if (
    count(
        $banners
    ) === 0
) {

    $banners[] = [

        "id" =>
            0,

        "title" =>
            "PORTAL SDM PUSRI",

        "subtitle" =>
            "Akses berbagai layanan SDM dalam satu portal.",

        "image" =>
            null,

        "image_url" =>
            "",

        "sort_order" =>
            0

    ];
}


/*
|--------------------------------------------------------------------------
| AMBIL APLIKASI AKTIF
|--------------------------------------------------------------------------
*/

try {

    $sqlApplications = "

        SELECT

            applications.id,
            applications.name,
            applications.description,
            applications.url,
            applications.category_id,
            applications.icon,
            applications.is_active,
            applications.sort_order,

            categories.name
                AS category_name

        FROM applications

        LEFT JOIN categories

            ON applications.category_id
            =
            categories.id

        WHERE applications.is_active = 1

        ORDER BY

            applications.sort_order ASC,
            applications.name ASC

    ";


    $resultApplications =
        $conn->query(
            $sqlApplications
        );


    if (
        !$resultApplications
    ) {

        throw new RuntimeException(
            "Query aplikasi gagal."
        );
    }


    while (
        $app =
            $resultApplications->fetch_assoc()
    ) {

        /*
        |--------------------------------------------------------------------------
        | ICON MINIO
        |--------------------------------------------------------------------------
        */

        $iconUrl = "";


        if (
            !empty(
                $app["icon"]
            )
        ) {

            try {

                $iconUrl =
                    getMinioUrl(
                        $s3,
                        $minioBucket,
                        $app["icon"]
                    );

            } catch (
                Throwable $e
            ) {

                error_log(
                    "Portal Application MinIO Error ID "
                    . (int) $app["id"]
                    . ": "
                    . get_class(
                        $e
                    )
                    . ": "
                    . $e->getMessage()
                );


                $iconUrl = "";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | URL AMAN
        |--------------------------------------------------------------------------
        */

        $app["safe_url"] =
            portalSafeApplicationUrl(
                $app["url"]
                ?? null
            );


        $app["icon_url"] =
            $iconUrl;


        /*
        |--------------------------------------------------------------------------
        | KATEGORI
        |--------------------------------------------------------------------------
        */

        $categoryName =
            trim(
                (string) (
                    $app["category_name"]
                    ?? ""
                )
            );


        if (
            $categoryName === ""
        ) {

            $categoryName =
                "Tanpa kategori";
        }


        $app["display_category"] =
            $categoryName;


        /*
        |--------------------------------------------------------------------------
        | SIMPAN KATEGORI
        |--------------------------------------------------------------------------
        */

        $categories[
            $categoryName
        ] =
            $categoryName;


        /*
        |--------------------------------------------------------------------------
        | SIMPAN APLIKASI
        |--------------------------------------------------------------------------
        */

        $applications[] =
            $app;
    }


    /*
    |--------------------------------------------------------------------------
    | URUTKAN KATEGORI
    |--------------------------------------------------------------------------
    */

    natcasesort(
        $categories
    );


} catch (
    Throwable $e
) {

    error_log(
        "Portal Application Error: "
        . get_class(
            $e
        )
        . ": "
        . $e->getMessage()
    );


    http_response_code(
        500
    );


    die(
        "Portal SDM PUSRI tidak dapat dimuat. Silakan coba kembali."
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


    <meta
        name="description"
        content="Portal SDM PUSRI - Portal layanan dan aplikasi SDM PT Pupuk Sriwidjaja Palembang."
    >


    <title>
        PORTAL SDM PUSRI
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css?v=<?= (int) $cssVersion ?>"
    >

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="navbar">


    <div class="container navbar-content">


        <!-- =================================================
             LOGO PUSRI PALEMBANG
        ================================================== -->

        <a
            href="index.php"
            class="navbar-brand"
            aria-label="PORTAL SDM PUSRI"
        >

            <img
                src="assets/images/logo-pusri-palembang.png"
                alt="PUSRI Palembang"
                class="navbar-logo"
            >

        </a>


        <!-- =================================================
             JUDUL
        ================================================== -->

        <div class="navbar-portal-title">

            PORTAL SDM PUSRI

        </div>


        <!-- =================================================
             ADMIN
        ================================================== -->

        <div class="navbar-right">


            <?php if (
                $isAdmin
            ): ?>


                <div class="navbar-user">


                    <span class="navbar-user-label">

                        Administrator

                    </span>


                    <strong>

                        <?= portalEscape(
                            $user["username"]
                            ?? "Admin"
                        ) ?>

                    </strong>


                </div>


                <a
                    href="admin/"
                    class="admin-button"
                >

                    Dashboard Admin

                </a>


                <a
                    href="logout.php"
                    class="logout-button"
                >

                    Logout

                </a>


            <?php else: ?>


                <a
                    href="login.php"
                    class="admin-button"
                    aria-label="Login administrator"
                >

                    Login Admin

                </a>


            <?php endif; ?>


        </div>


    </div>


</header>


<!-- =========================================================
     HERO / BANNER
========================================================= -->

<section
    class="hero-slider"
    id="heroSlider"
    tabindex="0"
    aria-label="Banner informasi PUSRI"
>


    <div
        class="hero-track"
        id="heroTrack"
    >


        <?php foreach (
            $banners
            as $index => $banner
        ): ?>


            <div
                class="hero-slide"
                data-slide-index="<?= (int) $index ?>"
            >


                <?php if (
                    !empty(
                        $banner["image_url"]
                    )
                ): ?>


                    <img
                        src="<?= portalEscape(
                            $banner["image_url"]
                        ) ?>"
                        alt="<?= portalEscape(
                            trim(
                                (string) (
                                    $banner["title"]
                                    ?? ""
                                )
                            ) !== ""
                                ? $banner["title"]
                                : "Banner PUSRI"
                        ) ?>"
                        class="hero-slide-image"
                        <?php if (
                            $index !== 0
                        ): ?>
                            loading="lazy"
                        <?php endif; ?>
                        decoding="async"
                    >


                    <div
                        class="hero-overlay"
                    ></div>


                <?php endif; ?>


                <div class="hero-slide-content">


                    <div class="container">


                        <?php if (
                            trim(
                                (string) (
                                    $banner["title"]
                                    ?? ""
                                )
                            ) !== ""
                        ): ?>


                            <h1>

                                <?= portalEscape(
                                    $banner["title"]
                                ) ?>

                            </h1>


                        <?php endif; ?>


                        <?php if (
                            trim(
                                (string) (
                                    $banner["subtitle"]
                                    ?? ""
                                )
                            ) !== ""
                        ): ?>


                            <p>

                                <?= portalEscape(
                                    $banner["subtitle"]
                                ) ?>

                            </p>


                        <?php endif; ?>


                    </div>


                </div>


            </div>


        <?php endforeach; ?>


    </div>


    <?php if (
        count(
            $banners
        ) > 1
    ): ?>


        <button
            type="button"
            class="
                hero-arrow
                hero-arrow-left
            "
            id="previousSlide"
            aria-label="Banner sebelumnya"
        >

            &#10094;

        </button>


        <button
            type="button"
            class="
                hero-arrow
                hero-arrow-right
            "
            id="nextSlide"
            aria-label="Banner berikutnya"
        >

            &#10095;

        </button>


        <div
            class="hero-dots"
            id="heroDots"
            aria-label="Navigasi banner"
        >


            <?php foreach (
                $banners
                as $index => $banner
            ): ?>


                <button
                    type="button"
                    class="
                        hero-dot
                        <?= $index === 0
                            ? "active"
                            : ""
                        ?>
                    "
                    data-slide="<?= (int) $index ?>"
                    aria-label="Buka banner <?= (int) $index + 1 ?>"
                    aria-current="<?= $index === 0
                        ? "true"
                        : "false"
                    ?>"
                ></button>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</section>


<!-- =========================================================
     SEARCH
========================================================= -->

<section class="search-section">


    <div class="container">


        <div class="search-panel">


            <!--
                Judul "Akses Cepat Layanan SDM"
                sengaja dihilangkan.
            -->


            <div class="search-input-wrapper">


                <span
                    class="search-icon"
                    aria-hidden="true"
                >

                    &#128269;

                </span>


                <input
                    type="search"
                    id="searchInput"
                    class="search-box"
                    placeholder="Cari layanan..."
                    autocomplete="off"
                    aria-label="Cari layanan SDM"
                >


                <button
                    type="button"
                    class="search-button"
                    id="searchButton"
                >

                    Cari

                </button>


            </div>


        </div>


    </div>


</section>


<!-- =========================================================
     DAFTAR APLIKASI
========================================================= -->

<section class="section">


    <div class="container">


        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="application-section-header">


            <div class="section-heading">


                <div
                    class="section-accent"
                ></div>


                <div>


                    <h2 class="section-title">

                        Daftar Aplikasi

                    </h2>


                    <p class="section-subtitle">

                        Akses aplikasi dan layanan SDM
                        melalui portal terpusat

                    </p>


                </div>


            </div>


            <div
                id="applicationCount"
                class="application-count"
                aria-live="polite"
            >

                <?= count(
                    $applications
                ) ?>

                layanan tersedia

            </div>


        </div>


        <!-- =================================================
             FILTER KATEGORI
        ================================================== -->

        <?php if (
            count(
                $categories
            ) > 0
        ): ?>


            <div
                class="category-filters"
                id="categoryFilters"
            >


                <button
                    type="button"
                    class="
                        category-filter
                        active
                    "
                    data-category-filter=""
                    aria-pressed="true"
                >

                    Semua

                </button>


                <?php foreach (
                    $categories
                    as $category
                ): ?>


                    <button
                        type="button"
                        class="category-filter"
                        data-category-filter="<?= portalEscape(
                            $category
                        ) ?>"
                        aria-pressed="false"
                    >

                        <?= portalEscape(
                            $category
                        ) ?>

                    </button>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


        <!-- =================================================
             APPLICATION GRID
        ================================================== -->

        <div
            class="application-grid"
            id="applicationGrid"
        >


            <?php if (
                count(
                    $applications
                ) > 0
            ): ?>


                <?php foreach (
                    $applications
                    as $appIndex => $app
                ): ?>


                    <?php

                    $applicationName =
                        trim(
                            (string) (
                                $app["name"]
                                ?? ""
                            )
                        );


                    $categoryName =
                        trim(
                            (string) (
                                $app["display_category"]
                                ?? "Tanpa kategori"
                            )
                        );


                    if (
                        function_exists(
                            "mb_strtolower"
                        )
                    ) {

                        $searchName =
                            mb_strtolower(
                                $applicationName,
                                "UTF-8"
                            );


                        $searchCategory =
                            mb_strtolower(
                                $categoryName,
                                "UTF-8"
                            );

                    } else {

                        $searchName =
                            strtolower(
                                $applicationName
                            );


                        $searchCategory =
                            strtolower(
                                $categoryName
                            );

                    }

                    ?>


                    <?php if (
                        $app["safe_url"]
                        !== null
                    ): ?>


                        <a
                            href="<?= portalEscape(
                                $app["safe_url"]
                            ) ?>"
                            class="application-card"
                            data-name="<?= portalEscape(
                                $searchName
                            ) ?>"
                            data-category="<?= portalEscape(
                                $searchCategory
                            ) ?>"
                            data-app-index="<?= (int) $appIndex ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Buka <?= portalEscape(
                                $applicationName
                            ) ?>"
                        >


                    <?php else: ?>


                        <div
                            class="application-card"
                            data-name="<?= portalEscape(
                                $searchName
                            ) ?>"
                            data-category="<?= portalEscape(
                                $searchCategory
                            ) ?>"
                            data-app-index="<?= (int) $appIndex ?>"
                            aria-disabled="true"
                        >


                    <?php endif; ?>


                            <!-- =========================================
                                 ICON
                            ========================================== -->

                            <div
                                class="application-icon"
                            >


                                <?php if (
                                    !empty(
                                        $app["icon_url"]
                                    )
                                ): ?>


                                    <img
                                        src="<?= portalEscape(
                                            $app["icon_url"]
                                        ) ?>"
                                        alt="<?= portalEscape(
                                            $applicationName
                                        ) ?>"
                                        loading="lazy"
                                        decoding="async"
                                    >


                                <?php else: ?>


                                    <span>

                                        <?= portalEscape(
                                            portalApplicationInitial(
                                                $applicationName
                                            )
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                            </div>


                            <!-- =========================================
                                 NAMA
                            ========================================== -->

                            <h3>

                                <?= portalEscape(
                                    $applicationName
                                ) ?>

                            </h3>


                            <!-- =========================================
                                 DESKRIPSI
                            ========================================== -->

                            <p>

                                <?= portalEscape(
                                    !empty(
                                        $app["description"]
                                    )
                                        ? $app["description"]
                                        : "Layanan internal PUSRI."
                                ) ?>

                            </p>


                            <!-- =========================================
                                 KATEGORI
                            ========================================== -->

                            <span class="category">

                                <?= portalEscape(
                                    $categoryName
                                ) ?>

                            </span>


                    <?php if (
                        $app["safe_url"]
                        !== null
                    ): ?>


                        </a>


                    <?php else: ?>


                        </div>


                    <?php endif; ?>


                <?php endforeach; ?>


            <?php else: ?>


                <div
                    class="empty-application"
                >


                    <h3>

                        Belum ada aplikasi

                    </h3>


                    <p>

                        Belum terdapat aplikasi aktif
                        pada PORTAL SDM PUSRI.

                    </p>


                </div>


            <?php endif; ?>


        </div>


        <!-- =================================================
             NAVIGASI HALAMAN APLIKASI
        ================================================== -->

        <div
            class="application-pagination"
            id="applicationPagination"
            aria-label="Navigasi halaman aplikasi"
        >


            <button
                type="button"
                class="application-page-arrow"
                id="previousApplicationPage"
                aria-label="Halaman aplikasi sebelumnya"
            >

                &#10094;

            </button>


            <div
                class="application-page-numbers"
                id="applicationPageNumbers"
            ></div>


            <button
                type="button"
                class="application-page-arrow"
                id="nextApplicationPage"
                aria-label="Halaman aplikasi berikutnya"
            >

                &#10095;

            </button>


        </div>


        <!-- =================================================
             SEARCH RESULT KOSONG
        ================================================== -->

        <div
            class="no-search-result"
            id="noSearchResult"
            style="display: none;"
            role="status"
            aria-live="polite"
        >

            Aplikasi yang dicari tidak ditemukan.

        </div>


    </div>


</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="portal-footer">


    <div class="container footer-content">


        <div>

            <strong>

                PORTAL SDM PUSRI

            </strong>

        </div>


        <div class="footer-right">

            &copy;

            <?= date(
                "Y"
            ) ?>

            PT Pupuk Sriwidjaja Palembang

        </div>


    </div>


</footer>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script
    src="assets/js/script.js?v=<?= (int) $jsVersion ?>"
></script>


</body>

</html>