<?php

/*
|--------------------------------------------------------------------------
| CONTOH FILE SECRETS - PORTAL LAYANAN SDM PUSRI
|--------------------------------------------------------------------------
| 1. Salin file ini ke: C:\xampp\secure\portal-pusri-secrets.php
| 2. Isi setiap nilai sesuai konfigurasi Keycloak, MinIO, dan MySQL.
| File asli tidak disimpan di repository karena berisi kredensial.
*/

return [

    // KEYCLOAK
    "keycloak_client_secret"       => "",   // Client "portal-pusri" -> tab Credentials
    "keycloak_admin_client_id"     => "portal-pusri-admin-api",
    "keycloak_admin_client_secret" => "",   // Client "portal-pusri-admin-api" -> tab Credentials

    // MINIO
    "minio_access_key" => "",
    "minio_secret_key" => "",

    // MYSQL
    "mysql_host"     => "localhost",
    "mysql_username" => "portal_pusri_user",
    "mysql_password" => "",
    "mysql_database" => "portal_pusri",

];
