<?php
// api/tratamientos.php
require "conexion.php";

configurarCabecerasCORS("GET, POST, OPTIONS");

$metodo = $_SERVER["REQUEST_METHOD"];

// ----- GET: listar tratamientos de un paciente específico -----
if ($metodo === "GET") {
    $id_paciente = filter_input(INPUT_GET, "id_paciente", FILTER_VALIDATE_INT);
    if (!$id_paciente) {
        http_response_code(400);
        echo json_encode(["error" => "Falta un id_paciente válido"]);
        exit();
    }

    try {
        $consulta = $pdo->prepare("SELECT * FROM tratamientos WHERE id_paciente = :id_paciente ORDER BY fecha_tratamiento DESC");
        $consulta->execute([":id_paciente" => $id_paciente]);
        $tratamientos = $consulta->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($tratamientos, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al consultar los tratamientos"]);
    }
    exit();
}

// ----- POST: crear un tratamiento -----
if ($metodo === "POST") {
    $cuerpo = json_decode(file_get_contents("php://input"), true);

    if (!is_array($cuerpo) || empty($cuerpo["id_paciente"]) || empty($cuerpo["numero_diente"]) || empty(trim((string)($cuerpo["plan_tratamiento"] ?? ""))) || empty($cuerpo["fecha_tratamiento"]) || !isset($cuerpo["presupuesto"]) || $cuerpo["presupuesto"] === "") {
        http_response_code(400);
        echo json_encode(["error" => "Faltan datos obligatorios del tratamiento"]);
        exit();
    }

    try {
        $consulta = $pdo->prepare(
            "INSERT INTO tratamientos (id_paciente, numero_diente, plan_tratamiento, fecha_tratamiento, presupuesto)
             VALUES (:id_paciente, :numero_diente, :plan_tratamiento, :fecha_tratamiento, :presupuesto)"
        );
        $consulta->execute([
            ":id_paciente" => (int)$cuerpo["id_paciente"],
            ":numero_diente" => (int)$cuerpo["numero_diente"],
            ":plan_tratamiento" => trim($cuerpo["plan_tratamiento"]),
            ":fecha_tratamiento" => trim($cuerpo["fecha_tratamiento"]),
            ":presupuesto" => (float)$cuerpo["presupuesto"]
        ]);

        http_response_code(201);
        echo json_encode(["mensaje" => "Tratamiento guardado correctamente"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al guardar el tratamiento en la base de datos"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);