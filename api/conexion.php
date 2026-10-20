<?php
require_once "config.php";

$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        // Conexión en Render mediante DATABASE_URL
        $url = parse_url($db_url);
        $dsn = "pgsql:host=" . $url["host"] . ";port=" . ($url["port"] ?? 5432) . ";dbname=" . ltrim($url["path"], '/');
        $user = $url["user"];
        $pass = $url["pass"];
    } else {
        // Fallback para entorno local
        $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
        $user = $db_user;
        $pass = $db_pass;
    }

    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "Error de conexión con la base de datos"]);
    exit();
}
