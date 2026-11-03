"use strict";


/*
|--------------------------------------------------------------------------
| PORTAL SDM PUSRI
|--------------------------------------------------------------------------
|
| JavaScript utama:
|
| 1. Banner slider otomatis 5 detik
| 2. Tombol banner
| 3. Dot banner
| 4. Search aplikasi
| 5. Filter kategori
| 6. Pagination aplikasi
| 7. Counter aplikasi
|
*/


document.addEventListener(
    "DOMContentLoaded",
    function () {


        /*
        |--------------------------------------------------------------------------
        | REDUCED MOTION
        |--------------------------------------------------------------------------
        */

        const prefersReducedMotion =
            window.matchMedia(
                "(prefers-reduced-motion: reduce)"
            );



        /*
        |--------------------------------------------------------------------------
        | HERO SLIDER
        |--------------------------------------------------------------------------
        */

        const heroSlider =
            document.getElementById(
                "heroSlider"
            );


        const heroTrack =
            document.getElementById(
                "heroTrack"
            );


        const previousSlideButton =
            document.getElementById(
                "previousSlide"
            );


        const nextSlideButton =
            document.getElementById(
                "nextSlide"
            );


        const heroDots =
            document.getElementById(
                "heroDots"
            );


        const slides =
            heroTrack
                ? Array.from(
                    heroTrack.querySelectorAll(
                        ".hero-slide"
                    )
                )
                : [];


        const dots =
            heroDots
                ? Array.from(
                    heroDots.querySelectorAll(
                        ".hero-dot"
                    )
                )
                : [];


        /*
        |--------------------------------------------------------------------------
        | HERO STATE
        |--------------------------------------------------------------------------
        */

        let currentSlide =
            0;


        let sliderTimer =
            null;


        /*
        |--------------------------------------------------------------------------
        | 5000 ms = 5 DETIK
        |--------------------------------------------------------------------------
        */

        const sliderInterval =
            5000;



        /*
        |--------------------------------------------------------------------------
        | CEK SLIDER
        |--------------------------------------------------------------------------
        */

        function sliderIsAvailable()
        {

            return (
                heroSlider !== null
                &&
                heroTrack !== null
                &&
                slides.length > 0
            );

        }



        /*
        |--------------------------------------------------------------------------
        | NORMALISASI INDEX
        |--------------------------------------------------------------------------
        */

        function normalizeSlideIndex(
            index
        )
        {

            if (
                slides.length === 0
            ) {

                return 0;
            }


            if (
                index < 0
            ) {

                return (
                    slides.length - 1
                );
            }


            if (
                index >= slides.length
            ) {

                return 0;
            }


            return index;

        }



        /*
        |--------------------------------------------------------------------------
        | TAMPILKAN SLIDE
        |--------------------------------------------------------------------------
        */

        function showSlide(
            index
        )
        {

            if (
                !sliderIsAvailable()
            ) {

                return;
            }


            currentSlide =
                normalizeSlideIndex(
                    index
                );


            const translateValue =
                currentSlide * 100;


            heroTrack.style.transform =
                "translateX(-"
                +
                translateValue
                +
                "%)";


            /*
            |--------------------------------------------------------------------------
            | UPDATE DOT
            |--------------------------------------------------------------------------
            */

            dots.forEach(
                function (
                    dot,
                    dotIndex
                ) {

                    const isActive =
                        dotIndex === currentSlide;


                    dot.classList.toggle(
                        "active",
                        isActive
                    );


                    dot.setAttribute(
                        "aria-current",
                        isActive
                            ? "true"
                            : "false"
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | NEXT BANNER
        |--------------------------------------------------------------------------
        */

        function nextSlide()
        {

            showSlide(
                currentSlide + 1
            );

        }



        /*
        |--------------------------------------------------------------------------
        | PREVIOUS BANNER
        |--------------------------------------------------------------------------
        */

        function previousSlide()
        {

            showSlide(
                currentSlide - 1
            );

        }



        /*
        |--------------------------------------------------------------------------
        | STOP AUTO SLIDE
        |--------------------------------------------------------------------------
        */

        function stopAutoSlide()
        {

            if (
                sliderTimer !== null
            ) {

                window.clearInterval(
                    sliderTimer
                );


                sliderTimer =
                    null;

            }

        }



        /*
        |--------------------------------------------------------------------------
        | START AUTO SLIDE
        |--------------------------------------------------------------------------
        */

        function startAutoSlide()
        {

            stopAutoSlide();


            /*
            |--------------------------------------------------------------------------
            | SATU BANNER
            |--------------------------------------------------------------------------
            */

            if (
                slides.length <= 1
            ) {

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | TAB TIDAK AKTIF
            |--------------------------------------------------------------------------
            */

            if (
                document.hidden
            ) {

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | AUTO SLIDE 5 DETIK
            |--------------------------------------------------------------------------
            */

            sliderTimer =
                window.setInterval(
                    function () {

                        nextSlide();

                    },
                    sliderInterval
                );

        }



        /*
        |--------------------------------------------------------------------------
        | INITIALISASI BANNER
        |--------------------------------------------------------------------------
        */

        if (
            sliderIsAvailable()
        ) {

            showSlide(
                0
            );


            /*
            |--------------------------------------------------------------------------
            | NEXT
            |--------------------------------------------------------------------------
            */

            if (
                nextSlideButton
            ) {

                nextSlideButton.addEventListener(
                    "click",
                    function () {

                        nextSlide();

                        startAutoSlide();

                    }
                );

            }


            /*
            |--------------------------------------------------------------------------
            | PREVIOUS
            |--------------------------------------------------------------------------
            */

            if (
                previousSlideButton
            ) {

                previousSlideButton.addEventListener(
                    "click",
                    function () {

                        previousSlide();

                        startAutoSlide();

                    }
                );

            }


            /*
            |--------------------------------------------------------------------------
            | DOT
            |--------------------------------------------------------------------------
            */

            dots.forEach(
                function (
                    dot
                ) {

                    dot.addEventListener(
                        "click",
                        function () {

                            const slideIndex =
                                Number.parseInt(
                                    dot.dataset.slide,
                                    10
                                );


                            if (
                                !Number.isInteger(
                                    slideIndex
                                )
                            ) {

                                return;
                            }


                            showSlide(
                                slideIndex
                            );


                            startAutoSlide();

                        }
                    );

                }
            );


            /*
            |--------------------------------------------------------------------------
            | KEYBOARD
            |--------------------------------------------------------------------------
            */

            heroSlider.addEventListener(
                "keydown",
                function (
                    event
                ) {

                    if (
                        event.key
                        === "ArrowLeft"
                    ) {

                        event.preventDefault();

                        previousSlide();

                        startAutoSlide();

                    }


                    if (
                        event.key
                        === "ArrowRight"
                    ) {

                        event.preventDefault();

                        nextSlide();

                        startAutoSlide();

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | TAB VISIBILITY
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                "visibilitychange",
                function () {

                    if (
                        document.hidden
                    ) {

                        stopAutoSlide();

                    } else {

                        startAutoSlide();

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | MULAI AUTO SLIDE
            |--------------------------------------------------------------------------
            */

            startAutoSlide();

        }



        /*
        |--------------------------------------------------------------------------
        | SEARCH ELEMENT
        |--------------------------------------------------------------------------
        */

        const searchInput =
            document.getElementById(
                "searchInput"
            );


        const searchButton =
            document.getElementById(
                "searchButton"
            );


        const applicationGrid =
            document.getElementById(
                "applicationGrid"
            );


        const applicationCount =
            document.getElementById(
                "applicationCount"
            );


        const noSearchResult =
            document.getElementById(
                "noSearchResult"
            );



        /*
        |--------------------------------------------------------------------------
        | APPLICATION CARDS
        |--------------------------------------------------------------------------
        */

        const applicationCards =
            applicationGrid
                ? Array.from(
                    applicationGrid.querySelectorAll(
                        ".application-card"
                    )
                )
                : [];



        /*
        |--------------------------------------------------------------------------
        | CATEGORY BUTTON
        |--------------------------------------------------------------------------
        */

        const categoryFilters =
            Array.from(
                document.querySelectorAll(
                    ".category-filter"
                )
            );



        /*
        |--------------------------------------------------------------------------
        | PAGINATION ELEMENT
        |--------------------------------------------------------------------------
        */

        const applicationPagination =
            document.getElementById(
                "applicationPagination"
            );


        const applicationPageNumbers =
            document.getElementById(
                "applicationPageNumbers"
            );


        const previousApplicationPage =
            document.getElementById(
                "previousApplicationPage"
            );


        const nextApplicationPage =
            document.getElementById(
                "nextApplicationPage"
            );



        /*
        |--------------------------------------------------------------------------
        | PAGINATION SETTING
        |--------------------------------------------------------------------------
        |
        | Maksimal 8 aplikasi setiap halaman.
        |
        */

        const applicationsPerPage =
            8;


        let currentApplicationPage =
            1;


        let filteredApplications =
            [];



        /*
        |--------------------------------------------------------------------------
        | CATEGORY ACTIVE
        |--------------------------------------------------------------------------
        */

        let activeCategory =
            "";



        /*
        |--------------------------------------------------------------------------
        | NORMALIZE TEXT
        |--------------------------------------------------------------------------
        */

        function normalizeSearchText(
            value
        )
        {

            return String(
                value ?? ""
            )
                .toLocaleLowerCase(
                    "id-ID"
                )
                .trim();

        }



        /*
        |--------------------------------------------------------------------------
        | UPDATE CATEGORY BUTTON
        |--------------------------------------------------------------------------
        */

        function updateCategoryButtons()
        {

            categoryFilters.forEach(
                function (
                    button
                ) {

                    const buttonCategory =
                        normalizeSearchText(
                            button.dataset
                                .categoryFilter
                        );


                    const isActive =
                        buttonCategory
                        ===
                        activeCategory;


                    button.classList.toggle(
                        "active",
                        isActive
                    );


                    button.setAttribute(
                        "aria-pressed",
                        isActive
                            ? "true"
                            : "false"
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | HITUNG TOTAL HALAMAN
        |--------------------------------------------------------------------------
        */

        function getTotalApplicationPages()
        {

            if (
                filteredApplications.length
                ===
                0
            ) {

                return 1;
            }


            return Math.ceil(
                filteredApplications.length
                /
                applicationsPerPage
            );

        }



        /*
        |--------------------------------------------------------------------------
        | UPDATE COUNTER
        |--------------------------------------------------------------------------
        */

        function updateApplicationCount(
            totalCount
        )
        {

            if (
                !applicationCount
            ) {

                return;
            }


            applicationCount.textContent =
                totalCount
                +
                " layanan tersedia";

        }



        /*
        |--------------------------------------------------------------------------
        | BUAT NOMOR HALAMAN
        |--------------------------------------------------------------------------
        */

        function buildPagination()
        {

            if (
                !applicationPageNumbers
            ) {

                return;
            }


            applicationPageNumbers.innerHTML =
                "";


            const totalPages =
                getTotalApplicationPages();


            /*
            |--------------------------------------------------------------------------
            | SATU HALAMAN
            |--------------------------------------------------------------------------
            */

            if (
                totalPages <= 1
            ) {

                if (
                    applicationPagination
                ) {

                    applicationPagination.style.display =
                        "none";

                }


                return;
            }


            /*
            |--------------------------------------------------------------------------
            | TAMPILKAN PAGINATION
            |--------------------------------------------------------------------------
            */

            if (
                applicationPagination
            ) {

                applicationPagination.style.display =
                    "flex";

            }


            /*
            |--------------------------------------------------------------------------
            | NOMOR HALAMAN
            |--------------------------------------------------------------------------
            */

            for (
                let page = 1;
                page <= totalPages;
                page++
            ) {

                const pageButton =
                    document.createElement(
                        "button"
                    );


                pageButton.type =
                    "button";


                pageButton.className =
                    "application-page-number";


                pageButton.textContent =
                    String(
                        page
                    );


                pageButton.dataset.page =
                    String(
                        page
                    );


                pageButton.setAttribute(
                    "aria-label",
                    "Buka halaman aplikasi "
                    +
                    page
                );


                pageButton.setAttribute(
                    "aria-current",
                    page === currentApplicationPage
                        ? "true"
                        : "false"
                );


                if (
                    page === currentApplicationPage
                ) {

                    pageButton.classList.add(
                        "active"
                    );

                }


                pageButton.addEventListener(
                    "click",
                    function () {

                        const targetPage =
                            Number.parseInt(
                                pageButton.dataset.page,
                                10
                            );


                        if (
                            !Number.isInteger(
                                targetPage
                            )
                        ) {

                            return;
                        }


                        goToApplicationPage(
                            targetPage
                        );

                    }
                );


                applicationPageNumbers.appendChild(
                    pageButton
                );

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE ARROW
            |--------------------------------------------------------------------------
            */

            updatePaginationButtons();

        }



        /*
        |--------------------------------------------------------------------------
        | UPDATE PANAH PAGINATION
        |--------------------------------------------------------------------------
        */

        function updatePaginationButtons()
        {

            const totalPages =
                getTotalApplicationPages();


            if (
                previousApplicationPage
            ) {

                previousApplicationPage.disabled =
                    currentApplicationPage <= 1;

            }


            if (
                nextApplicationPage
            ) {

                nextApplicationPage.disabled =
                    currentApplicationPage >= totalPages;

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE ACTIVE NUMBER
            |--------------------------------------------------------------------------
            */

            const numberButtons =
                applicationPageNumbers
                    ? Array.from(
                        applicationPageNumbers
                            .querySelectorAll(
                                ".application-page-number"
                            )
                    )
                    : [];


            numberButtons.forEach(
                function (
                    button
                ) {

                    const page =
                        Number.parseInt(
                            button.dataset.page,
                            10
                        );


                    const isActive =
                        page
                        ===
                        currentApplicationPage;


                    button.classList.toggle(
                        "active",
                        isActive
                    );


                    button.setAttribute(
                        "aria-current",
                        isActive
                            ? "true"
                            : "false"
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | TAMPILKAN HALAMAN APLIKASI
        |--------------------------------------------------------------------------
        */

        function renderApplicationPage()
        {

            /*
            |--------------------------------------------------------------------------
            | SEMBUNYIKAN SEMUA CARD TERLEBIH DAHULU
            |--------------------------------------------------------------------------
            */

            applicationCards.forEach(
                function (
                    card
                ) {

                    card.hidden =
                        true;

                }
            );


            /*
            |--------------------------------------------------------------------------
            | HITUNG RANGE
            |--------------------------------------------------------------------------
            */

            const startIndex =
                (
                    currentApplicationPage
                    -
                    1
                )
                *
                applicationsPerPage;


            const endIndex =
                startIndex
                +
                applicationsPerPage;


            /*
            |--------------------------------------------------------------------------
            | TAMPILKAN CARD PADA HALAMAN AKTIF
            |--------------------------------------------------------------------------
            */

            const pageApplications =
                filteredApplications.slice(
                    startIndex,
                    endIndex
                );


            pageApplications.forEach(
                function (
                    card
                ) {

                    card.hidden =
                        false;

                }
            );


            /*
            |--------------------------------------------------------------------------
            | PAGINATION
            |--------------------------------------------------------------------------
            */

            buildPagination();


            /*
            |--------------------------------------------------------------------------
            | COUNTER
            |--------------------------------------------------------------------------
            */

            updateApplicationCount(
                filteredApplications.length
            );

        }



        /*
        |--------------------------------------------------------------------------
        | PINDAH HALAMAN
        |--------------------------------------------------------------------------
        */

        function goToApplicationPage(
            page
        )
        {

            const totalPages =
                getTotalApplicationPages();


            /*
            |--------------------------------------------------------------------------
            | BATASI HALAMAN
            |--------------------------------------------------------------------------
            */

            if (
                page < 1
            ) {

                page =
                    1;

            }


            if (
                page > totalPages
            ) {

                page =
                    totalPages;

            }


            currentApplicationPage =
                page;


            renderApplicationPage();


            /*
            |--------------------------------------------------------------------------
            | SCROLL KE BAGIAN DAFTAR
            |--------------------------------------------------------------------------
            */

            if (
                applicationGrid
            ) {

                const behavior =
                    prefersReducedMotion.matches
                        ? "auto"
                        : "smooth";


                applicationGrid.scrollIntoView(
                    {
                        behavior:
                            behavior,

                        block:
                            "start"
                    }
                );

            }

        }



        /*
        |--------------------------------------------------------------------------
        | FILTER APPLICATIONS
        |--------------------------------------------------------------------------
        */

        function filterApplications(
            resetToFirstPage = true
        )
        {

            const keyword =
                normalizeSearchText(
                    searchInput
                        ? searchInput.value
                        : ""
                );


            /*
            |--------------------------------------------------------------------------
            | HASIL FILTER
            |--------------------------------------------------------------------------
            */

            filteredApplications =
                applicationCards.filter(
                    function (
                        card
                    ) {

                        const cardName =
                            normalizeSearchText(
                                card.dataset.name
                            );


                        const cardCategory =
                            normalizeSearchText(
                                card.dataset.category
                            );


                        const cardText =
                            normalizeSearchText(
                                card.textContent
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | KEYWORD
                        |--------------------------------------------------------------------------
                        */

                        const matchesKeyword =
                            keyword === ""
                            ||
                            cardName.includes(
                                keyword
                            )
                            ||
                            cardCategory.includes(
                                keyword
                            )
                            ||
                            cardText.includes(
                                keyword
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | CATEGORY
                        |--------------------------------------------------------------------------
                        */

                        const matchesCategory =
                            activeCategory === ""
                            ||
                            cardCategory
                            ===
                            activeCategory;


                        return (
                            matchesKeyword
                            &&
                            matchesCategory
                        );

                    }
                );


            /*
            |--------------------------------------------------------------------------
            | RESET KE HALAMAN 1
            |--------------------------------------------------------------------------
            */

            if (
                resetToFirstPage
            ) {

                currentApplicationPage =
                    1;

            }


            /*
            |--------------------------------------------------------------------------
            | PASTIKAN HALAMAN VALID
            |--------------------------------------------------------------------------
            */

            const totalPages =
                getTotalApplicationPages();


            if (
                currentApplicationPage
                >
                totalPages
            ) {

                currentApplicationPage =
                    totalPages;

            }


            /*
            |--------------------------------------------------------------------------
            | TAMPILKAN HASIL
            |--------------------------------------------------------------------------
            */

            renderApplicationPage();


            /*
            |--------------------------------------------------------------------------
            | PESAN TIDAK DITEMUKAN
            |--------------------------------------------------------------------------
            */

            if (
                noSearchResult
            ) {

                const hasFilter =
                    keyword !== ""
                    ||
                    activeCategory !== "";


                noSearchResult.style.display =
                    hasFilter
                    &&
                    filteredApplications.length === 0
                        ? "block"
                        : "none";

            }

        }



        /*
        |--------------------------------------------------------------------------
        | FILTER KATEGORI
        |--------------------------------------------------------------------------
        */

        categoryFilters.forEach(
            function (
                button
            ) {

                button.addEventListener(
                    "click",
                    function () {

                        activeCategory =
                            normalizeSearchText(
                                button.dataset
                                    .categoryFilter
                            );


                        updateCategoryButtons();


                        filterApplications(
                            true
                        );

                    }
                );

            }
        );



        /*
        |--------------------------------------------------------------------------
        | SEARCH INPUT
        |--------------------------------------------------------------------------
        */

        if (
            searchInput
        ) {

            /*
            |--------------------------------------------------------------------------
            | LIVE SEARCH
            |--------------------------------------------------------------------------
            */

            searchInput.addEventListener(
                "input",
                function () {

                    filterApplications(
                        true
                    );

                }
            );


            /*
            |--------------------------------------------------------------------------
            | KEYBOARD
            |--------------------------------------------------------------------------
            */

            searchInput.addEventListener(
                "keydown",
                function (
                    event
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | ENTER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        event.key
                        ===
                        "Enter"
                    ) {

                        event.preventDefault();


                        filterApplications(
                            true
                        );


                        if (
                            applicationGrid
                        ) {

                            applicationGrid.scrollIntoView(
                                {
                                    behavior:
                                        prefersReducedMotion.matches
                                            ? "auto"
                                            : "smooth",

                                    block:
                                        "start"
                                }
                            );

                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | ESCAPE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        event.key
                        ===
                        "Escape"
                    ) {

                        searchInput.value =
                            "";


                        activeCategory =
                            "";


                        updateCategoryButtons();


                        filterApplications(
                            true
                        );

                    }

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | TOMBOL CARI
        |--------------------------------------------------------------------------
        */

        if (
            searchButton
        ) {

            searchButton.addEventListener(
                "click",
                function () {

                    filterApplications(
                        true
                    );


                    if (
                        applicationGrid
                    ) {

                        applicationGrid.scrollIntoView(
                            {
                                behavior:
                                    prefersReducedMotion.matches
                                        ? "auto"
                                        : "smooth",

                                block:
                                    "start"
                            }
                        );

                    }

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | TOMBOL HALAMAN SEBELUMNYA
        |--------------------------------------------------------------------------
        */

        if (
            previousApplicationPage
        ) {

            previousApplicationPage.addEventListener(
                "click",
                function () {

                    goToApplicationPage(
                        currentApplicationPage
                        -
                        1
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | TOMBOL HALAMAN BERIKUTNYA
        |--------------------------------------------------------------------------
        */

        if (
            nextApplicationPage
        ) {

            nextApplicationPage.addEventListener(
                "click",
                function () {

                    goToApplicationPage(
                        currentApplicationPage
                        +
                        1
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | INITIAL CATEGORY
        |--------------------------------------------------------------------------
        */

        updateCategoryButtons();


        /*
        |--------------------------------------------------------------------------
        | INITIAL APPLICATION DATA
        |--------------------------------------------------------------------------
        */

        filteredApplications =
            applicationCards.slice();


        /*
        |--------------------------------------------------------------------------
        | RENDER HALAMAN PERTAMA
        |--------------------------------------------------------------------------
        */

        renderApplicationPage();

    }
);