<?php
// api/config.php

// R8: display_errors apagado en producción para no contaminar respuestas JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Zona horaria
date_default_timezone_set("America/Argentina/Buenos_Aires");

// API Key de Resend (Email) - Leída por entorno o vacía para modo local
$resend_api_key = getenv('RESEND_API_KEY');
define("RESEND_API_KEY", $resend_api_key);

// Email para los avisos - Leído por entorno
$email_avisos = getenv('EMAIL_AVISOS');
define("EMAIL_AVISOS", $email_avisos);

// Credenciales de la Base de Datos (para entorno local o variables separadas)
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

// Función auxiliar para CORS controlado (R8)
function configurarCabecerasCORS($metodos = "GET, POST, PUT, DELETE, OPTIONS") {
    $origen = $_SERVER["HTTP_ORIGIN"] ?? "";
    $origenesPermitidos = [
        "https://app-clinica-odontologica.onrender.com",
        "http://localhost",
        "http://localhost:5500",
        "http://127.0.0.1:5500"
    ];

    if (in_array($origen, $origenesPermitidos, true)) {
        header("Access-Control-Allow-Origin: $origen");
        header("Vary: Origin");
    } elseif ($origen === "") {
        // Permite pruebas directas desde el navegador
        header("Access-Control-Allow-Origin: *");
    }

    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Methods: $metodos");
    header("Access-Control-Allow-Headers: Content-Type");

    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        http_response_code(200);
        exit();
    }
}