<?php

/*
|--------------------------------------------------------------------------
| MINIO FUNCTIONS - PORTAL PUSRI
|--------------------------------------------------------------------------
|
| Fungsi:
|
| - Upload gambar secara aman
| - Hapus object MinIO
| - Membuat presigned URL
|
*/


/*
|--------------------------------------------------------------------------
| NORMALISASI FOLDER MINIO
|--------------------------------------------------------------------------
*/

function normalizeMinioFolder(
    string $folder
): string {

    $folder =
        trim(
            str_replace(
                "\\",
                "/",
                $folder
            ),
            "/"
        );


    if ($folder === "") {

        throw new RuntimeException(
            "Folder penyimpanan MinIO tidak valid."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | BLOK PATH MENCURIGAKAN
    |--------------------------------------------------------------------------
    */

    if (
        str_contains(
            $folder,
            ".."
        )
        ||
        str_contains(
            $folder,
            "\0"
        )
    ) {

        throw new RuntimeException(
            "Folder penyimpanan MinIO tidak valid."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | KARAKTER FOLDER YANG DIPERBOLEHKAN
    |--------------------------------------------------------------------------
    |
    | Contoh:
    |
    | applications/icons
    | banners
    |
    */

    if (
        !preg_match(
            '#^[a-zA-Z0-9/_-]+$#',
            $folder
        )
    ) {

        throw new RuntimeException(
            "Nama folder MinIO tidak valid."
        );
    }


    return $folder;
}


/*
|--------------------------------------------------------------------------
| NORMALISASI OBJECT KEY
|--------------------------------------------------------------------------
*/

function normalizeMinioObjectKey(
    string $objectKey
): string {

    $objectKey =
        trim(
            str_replace(
                "\\",
                "/",
                $objectKey
            ),
            "/"
        );


    if ($objectKey === "") {
        return "";
    }


    /*
    |--------------------------------------------------------------------------
    | BLOK PATH MENCURIGAKAN
    |--------------------------------------------------------------------------
    */

    if (
        str_contains(
            $objectKey,
            ".."
        )
        ||
        str_contains(
            $objectKey,
            "\0"
        )
    ) {

        throw new RuntimeException(
            "Object key MinIO tidak valid."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | WHITELIST OBJECT KEY
    |--------------------------------------------------------------------------
    |
    | Mengizinkan:
    |
    | huruf
    | angka
    | /
    | _
    | -
    | .
    |
    | Contoh:
    |
    | applications/icons/img_xxx.jpg
    | banners/img_xxx.webp
    |
    */

    if (
        !preg_match(
            '#^[a-zA-Z0-9/_\.-]+$#',
            $objectKey
        )
    ) {

        throw new RuntimeException(
            "Object key MinIO tidak valid."
        );
    }


    return $objectKey;
}


/*
|--------------------------------------------------------------------------
| VALIDASI BUCKET
|--------------------------------------------------------------------------
*/

function validateMinioBucket(
    string $bucket
): string {

    $bucket =
        trim(
            $bucket
        );


    if ($bucket === "") {

        throw new RuntimeException(
            "Bucket MinIO tidak valid."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Format aman nama bucket S3 / MinIO.
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            '#^[a-z0-9][a-z0-9.-]*[a-z0-9]$#',
            $bucket
        )
    ) {

        throw new RuntimeException(
            "Nama bucket MinIO tidak valid."
        );
    }


    return $bucket;
}


/*
|--------------------------------------------------------------------------
| UPLOAD GAMBAR KE MINIO
|--------------------------------------------------------------------------
*/

function uploadImageToMinio(
    $s3,
    $bucket,
    $file,
    $folder = "applications/icons"
): string {

    /*
    |--------------------------------------------------------------------------
    | VALIDASI STRUKTUR FILE
    |--------------------------------------------------------------------------
    */

    if (
        !is_array(
            $file
        )
        ||
        !isset(
            $file["error"]
        )
        ||
        !isset(
            $file["tmp_name"]
        )
        ||
        !isset(
            $file["size"]
        )
    ) {

        throw new RuntimeException(
            "Data upload gambar tidak valid."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI ERROR UPLOAD PHP
    |--------------------------------------------------------------------------
    */

    if (
        $file["error"]
        === UPLOAD_ERR_NO_FILE
    ) {

        throw new RuntimeException(
            "Tidak ada gambar yang dipilih."
        );
    }


    if (
        $file["error"]
        !== UPLOAD_ERR_OK
    ) {

        $uploadErrors = [

            UPLOAD_ERR_INI_SIZE =>
                "Ukuran file melebihi batas server.",

            UPLOAD_ERR_FORM_SIZE =>
                "Ukuran file melebihi batas form.",

            UPLOAD_ERR_PARTIAL =>
                "File hanya terupload sebagian.",

            UPLOAD_ERR_NO_TMP_DIR =>
                "Folder temporary server tidak tersedia.",

            UPLOAD_ERR_CANT_WRITE =>
                "Server gagal menyimpan file temporary.",

            UPLOAD_ERR_EXTENSION =>
                "Upload dihentikan oleh konfigurasi PHP."

        ];


        $message =
            $uploadErrors[
                $file["error"]
            ]
            ??
            "File gagal diupload.";


        throw new RuntimeException(
            $message
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PASTIKAN BENAR-BENAR HTTP UPLOAD
    |--------------------------------------------------------------------------
    */

    $tmpName =
        (string)
        $file["tmp_name"];


    if (
        !is_uploaded_file(
            $tmpName
        )
    ) {

        throw new RuntimeException(
            "Sumber file upload tidak valid."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TEMP FILE HARUS ADA DAN DAPAT DIBACA
    |--------------------------------------------------------------------------
    */

    if (
        !is_file(
            $tmpName
        )
        ||
        !is_readable(
            $tmpName
        )
    ) {

        throw new RuntimeException(
            "File temporary tidak dapat dibaca."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI UKURAN FILE
    |--------------------------------------------------------------------------
    |
    | Maksimal:
    |
    | 2 MB
    |
    */

    $maxSize =
        2 * 1024 * 1024;


    /*
    |--------------------------------------------------------------------------
    | Jangan hanya percaya nilai $_FILES["size"].
    |
    | Cek ukuran file temporary yang benar-benar tersimpan.
    |--------------------------------------------------------------------------
    */

    $actualFileSize =
        filesize(
            $tmpName
        );


    if (
        $actualFileSize === false
    ) {

        throw new RuntimeException(
            "Ukuran file tidak dapat dibaca."
        );
    }


    $actualFileSize =
        (int)
        $actualFileSize;


    if (
        $actualFileSize <= 0
    ) {

        throw new RuntimeException(
            "File gambar kosong atau rusak."
        );
    }


    if (
        $actualFileSize > $maxSize
    ) {

        throw new RuntimeException(
            "Ukuran gambar maksimal 2 MB."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MIME TYPE SEBENARNYA
    |--------------------------------------------------------------------------
    |
    | Jangan percaya:
    |
    | $_FILES["type"]
    | ekstensi file
    | nama file user
    |
    */

    $finfo =
        new finfo(
            FILEINFO_MIME_TYPE
        );


    $mimeType =
        $finfo->file(
            $tmpName
        );


    if (
        !is_string(
            $mimeType
        )
    ) {

        throw new RuntimeException(
            "Tipe file gambar tidak dapat dikenali."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | WHITELIST MIME
    |--------------------------------------------------------------------------
    |
    | SVG sengaja tidak diperbolehkan.
    |
    */

    $allowedTypes = [

        "image/jpeg" =>
            "jpg",

        "image/png" =>
            "png",

        "image/webp" =>
            "webp"

    ];


    if (
        !isset(
            $allowedTypes[
                $mimeType
            ]
        )
    ) {

        throw new RuntimeException(
            "Format gambar harus JPG, PNG, atau WEBP."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI STRUKTUR GAMBAR
    |--------------------------------------------------------------------------
    */

    $imageInfo =
        @getimagesize(
            $tmpName
        );


    if (
        $imageInfo === false
        ||
        empty(
            $imageInfo[0]
        )
        ||
        empty(
            $imageInfo[1]
        )
    ) {

        throw new RuntimeException(
            "File bukan gambar yang valid atau file rusak."
        );
    }


    $width =
        (int)
        $imageInfo[0];


    $height =
        (int)
        $imageInfo[1];


    /*
    |--------------------------------------------------------------------------
    | BATAS DIMENSI
    |--------------------------------------------------------------------------
    */

    $maxWidth =
        8000;

    $maxHeight =
        8000;

    $maxPixels =
        40000000;


    if (
        $width > $maxWidth
        ||
        $height > $maxHeight
        ||
        (
            $width * $height
        ) > $maxPixels
    ) {

        throw new RuntimeException(
            "Dimensi gambar terlalu besar."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI MIME DARI STRUKTUR GAMBAR
    |--------------------------------------------------------------------------
    */

    $detectedMime =
        $imageInfo["mime"]
        ?? "";


    if (
        $detectedMime !== ""
        &&
        $detectedMime !== $mimeType
    ) {

        throw new RuntimeException(
            "Tipe file gambar tidak konsisten."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EXTENSION BERDASARKAN MIME
    |--------------------------------------------------------------------------
    */

    $extension =
        $allowedTypes[
            $mimeType
        ];


    /*
    |--------------------------------------------------------------------------
    | NAMA OBJECT ACAK
    |--------------------------------------------------------------------------
    |
    | 16 byte = 128-bit randomness.
    |
    */

    $fileName =
        "img_"
        .
        bin2hex(
            random_bytes(
                16
            )
        )
        .
        "."
        .
        $extension;


    /*
    |--------------------------------------------------------------------------
    | VALIDASI FOLDER
    |--------------------------------------------------------------------------
    */

    $safeFolder =
        normalizeMinioFolder(
            (string)
            $folder
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDASI BUCKET
    |--------------------------------------------------------------------------
    */

    $safeBucket =
        validateMinioBucket(
            (string)
            $bucket
        );


    /*
    |--------------------------------------------------------------------------
    | OBJECT KEY
    |--------------------------------------------------------------------------
    */

    $objectKey =
        $safeFolder
        .
        "/"
        .
        $fileName;


    $objectKey =
        normalizeMinioObjectKey(
            $objectKey
        );


    /*
    |--------------------------------------------------------------------------
    | UPLOAD KE MINIO
    |--------------------------------------------------------------------------
    */

    try {

        $s3->putObject([

            "Bucket" =>
                $safeBucket,

            "Key" =>
                $objectKey,

            "SourceFile" =>
                $tmpName,

            "ContentType" =>
                $mimeType,

            /*
            |--------------------------------------------------------------------------
            | Jangan biarkan browser menebak tipe file.
            |--------------------------------------------------------------------------
            */

            "ContentDisposition" =>
                "inline",

            /*
            |--------------------------------------------------------------------------
            | Bucket private + presigned URL.
            |--------------------------------------------------------------------------
            */

            "CacheControl" =>
                "private, max-age=3600"

        ]);


    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | Detail untuk developer.
        |--------------------------------------------------------------------------
        */

        error_log(
            "Portal PUSRI MinIO Upload Error: "
            .
            get_class(
                $e
            )
            .
            ": "
            .
            $e->getMessage()
        );


        /*
        |--------------------------------------------------------------------------
        | Jangan bocorkan detail MinIO/AWS ke browser.
        |--------------------------------------------------------------------------
        */

        throw new RuntimeException(
            "Gagal menyimpan gambar ke penyimpanan."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DATABASE HANYA MENYIMPAN OBJECT KEY
    |--------------------------------------------------------------------------
    */

    return $objectKey;
}


/*
|--------------------------------------------------------------------------
| HAPUS OBJECT DARI MINIO
|--------------------------------------------------------------------------
*/

function deleteFromMinio(
    $s3,
    $bucket,
    $objectKey
): void {

    if (
        empty(
            $objectKey
        )
    ) {
        return;
    }


    $safeBucket =
        validateMinioBucket(
            (string)
            $bucket
        );


    $safeObjectKey =
        normalizeMinioObjectKey(
            (string)
            $objectKey
        );


    if (
        $safeObjectKey === ""
    ) {
        return;
    }


    try {

        $s3->deleteObject([

            "Bucket" =>
                $safeBucket,

            "Key" =>
                $safeObjectKey

        ]);


    } catch (Throwable $e) {

        error_log(
            "Portal PUSRI MinIO Delete Error: "
            .
            get_class(
                $e
            )
            .
            ": "
            .
            $e->getMessage()
        );


        throw new RuntimeException(
            "Gagal menghapus gambar dari penyimpanan."
        );
    }
}


/*
|--------------------------------------------------------------------------
| MEMBUAT PRESIGNED URL MINIO
|--------------------------------------------------------------------------
*/

function getMinioUrl(
    $s3,
    $bucket,
    $objectKey
): string {

    if (
        empty(
            $objectKey
        )
    ) {
        return "";
    }


    $safeBucket =
        validateMinioBucket(
            (string)
            $bucket
        );


    $safeObjectKey =
        normalizeMinioObjectKey(
            (string)
            $objectKey
        );


    if (
        $safeObjectKey === ""
    ) {
        return "";
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | GET OBJECT COMMAND
        |--------------------------------------------------------------------------
        */

        $command =
            $s3->getCommand(
                "GetObject",
                [

                    "Bucket" =>
                        $safeBucket,

                    "Key" =>
                        $safeObjectKey

                ]
            );


        /*
        |--------------------------------------------------------------------------
        | PRESIGNED URL
        |--------------------------------------------------------------------------
        |
        | Berlaku 1 jam.
        |
        */

        $request =
            $s3->createPresignedRequest(
                $command,
                "+1 hour"
            );


        return
            (string)
            $request->getUri();


    } catch (Throwable $e) {

        error_log(
            "Portal PUSRI MinIO URL Error: "
            .
            get_class(
                $e
            )
            .
            ": "
            .
            $e->getMessage()
        );


        throw new RuntimeException(
            "URL gambar tidak dapat dibuat."
        );
    }
}