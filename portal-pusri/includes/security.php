<?php

/*
|--------------------------------------------------------------------------
| SECURITY HEADERS - PORTAL PUSRI
|--------------------------------------------------------------------------
*/

if (!headers_sent()) {

    /*
    |--------------------------------------------------------------------------
    | HILANGKAN INFORMASI PHP DARI HEADER
    |--------------------------------------------------------------------------
    */

    header_remove("X-Powered-By");


    /*
    |--------------------------------------------------------------------------
    | MIME TYPE SNIFFING
    |--------------------------------------------------------------------------
    */

    header(
        "X-Content-Type-Options: nosniff"
    );


    /*
    |--------------------------------------------------------------------------
    | CLICKJACKING
    |--------------------------------------------------------------------------
    */

    header(
        "X-Frame-Options: SAMEORIGIN"
    );


    /*
    |--------------------------------------------------------------------------
    | REFERRER POLICY
    |--------------------------------------------------------------------------
    */

    header(
        "Referrer-Policy: strict-origin-when-cross-origin"
    );


    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS POLICY
    |--------------------------------------------------------------------------
    */

    header(
        "Permissions-Policy: camera=(), microphone=(), geolocation=()"
    );


    /*
    |--------------------------------------------------------------------------
    | LEGACY XSS FILTER
    |--------------------------------------------------------------------------
    */

    header(
        "X-XSS-Protection: 0"
    );


    /*
    |--------------------------------------------------------------------------
    | CONTENT SECURITY POLICY
    |--------------------------------------------------------------------------
    |
    | CSP sekarang AKTIF.
    |
    | Portal masih menggunakan beberapa CSS dan JavaScript inline,
    | sehingga unsafe-inline sementara masih diperlukan.
    |
    */

    $csp = implode(
        "; ",
        [

            /*
            | Semua resource secara default
            | hanya dari Portal PUSRI sendiri.
            */
            "default-src 'self'",


            /*
            | JavaScript.
            */
            "script-src 'self' 'unsafe-inline'",


            /*
            | CSS.
            */
            "style-src 'self' 'unsafe-inline'",


            /*
            | Gambar Portal + MinIO.
            */
            "img-src 'self' data: blob: http://127.0.0.1:9000 http://localhost:9000",


            /*
            | Font.
            */
            "font-src 'self' data:",


            /*
            | Fetch / AJAX / koneksi aplikasi.
            */
            "connect-src 'self' http://127.0.0.1:9000 http://localhost:9000",


            /*
            | Plugin / object seperti Flash dilarang.
            */
            "object-src 'none'",


            /*
            | Mencegah perubahan base URL.
            */
            "base-uri 'self'",


            /*
            | Form admin hanya boleh POST
            | ke Portal PUSRI sendiri.
            */
            "form-action 'self'",


            /*
            | Portal hanya boleh ditempatkan
            | dalam iframe dari origin yang sama.
            */
            "frame-ancestors 'self'"

        ]
    );


    /*
    |--------------------------------------------------------------------------
    | AKTIFKAN CSP
    |--------------------------------------------------------------------------
    */

    header(
        "Content-Security-Policy: "
        .
        $csp
    );
}