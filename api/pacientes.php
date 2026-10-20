<?php
require "conexion.php";
require "servicios.php";

configurarCabecerasCORS("GET, POST, PUT, DELETE, OPTIONS");

$metodo = $_SERVER["REQUEST_METHOD"];

function validarDatosPaciente($cuerpo) {
    if (!is_array($cuerpo)) {
        return "El cuerpo de la solicitud debe ser un JSON válido";
    }
    $obligatorios = [
        "nombre", "apellido", "fecha_nacimiento", "telefono",
        "localidad", "domicilio", "dni", "email", "fecha_turno_asignado"
    ];
    foreach ($obligatorios as $campo) {
        if (!isset($cuerpo[$campo]) || trim((string)$cuerpo[$campo]) === "") {
            return "El campo '$campo' es obligatorio y no puede estar vacío";
        }
    }
    if (!filter_var(trim($cuerpo["email"]), FILTER_VALIDATE_EMAIL)) {
        return "El formato del email no es válido";
    }
    // Validar formato de fechas YYYY-MM-DD
    foreach (["fecha_nacimiento", "fecha_turno_asignado"] as $campoFecha) {
        $fecha = trim($cuerpo[$campoFecha]);
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            return "El campo '$campoFecha' debe tener un formato de fecha válido (AAAA-MM-DD)";
        }
    }
    return null;
}

// ----- POST: crear un paciente -----
if ($metodo === "POST") {
    $cuerpo = json_decode(file_get_contents("php://input"), true);
    $errorValidacion = validarDatosPaciente($cuerpo);

    if ($errorValidacion !== null) {
        http_response_code(400);
        echo json_encode(["error" => $errorValidacion], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $nombre = trim($cuerpo["nombre"]);
    $apellido = trim($cuerpo["apellido"]);
    $fecha_nacimiento = trim($cuerpo["fecha_nacimiento"]);
    $telefono = trim($cuerpo["telefono"]);
    $localidad = trim($cuerpo["localidad"]);
    $domicilio = trim($cuerpo["domicilio"]);
    $dni = trim($cuerpo["dni"]);
    $email = trim($cuerpo["email"]);
    $fecha_turno_asignado = trim($cuerpo["fecha_turno_asignado"]);
    $obra_social = trim($cuerpo["obra_social"] ?? "");
    $num_afiliado = trim($cuerpo["num_afiliado"] ?? "");
    $plan = trim($cuerpo["plan"] ?? "");

    try {
        $consulta = $pdo->prepare(
            "INSERT INTO pacientes (nombre, apellido, fecha_nacimiento, telefono, localidad, domicilio, dni, email, fecha_turno_asignado, obra_social, num_afiliado, plan)
             VALUES (:nombre, :apellido, :fecha_nacimiento, :telefono, :localidad, :domicilio, :dni, :email, :fecha_turno_asignado, :obra_social, :num_afiliado, :plan)
             RETURNING id"
        );
        $consulta->execute([
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":fecha_nacimiento" => $fecha_nacimiento,
            ":telefono" => $telefono,
            ":localidad" => $localidad,
            ":domicilio" => $domicilio,
            ":dni" => $dni,
            ":email" => $email,
            ":fecha_turno_asignado" => $fecha_turno_asignado,
            ":obra_social" => $obra_social,
            ":num_afiliado" => $num_afiliado,
            ":plan" => $plan,
        ]);

        $filaInsertada = $consulta->fetch(PDO::FETCH_ASSOC);
        $nuevoId = $filaInsertada ? (int)$filaInsertada["id"] : (int)$pdo->lastInsertId();

        $paciente = [
            "id" => $nuevoId,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "fecha_nacimiento" => $fecha_nacimiento,
            "telefono" => $telefono,
            "localidad" => $localidad,
            "domicilio" => $domicilio,
            "dni" => $dni,
            "email" => $email,
            "fecha_turno_asignado" => $fecha_turno_asignado,
            "obra_social" => $obra_social,
            "num_afiliado" => $num_afiliado,
            "plan" => $plan,
        ];

        // R4 y R5: si falla el servicio externo, no interrumpe el alta
        enviarAviso($paciente);

        http_response_code(201);
        echo json_encode($paciente, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al guardar en la base de datos"]);
    }
    exit();
}

// ----- PUT: modificar un paciente -----
if ($metodo === "PUT") {
    $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        echo json_encode(["error" => "Falta un id válido"]);
        exit();
    }

    $cuerpo = json_decode(file_get_contents("php://input"), true);
    $errorValidacion = validarDatosPaciente($cuerpo);

    if ($errorValidacion !== null) {
        http_response_code(400);
        echo json_encode(["error" => $errorValidacion], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $nombre = trim($cuerpo["nombre"]);
    $apellido = trim($cuerpo["apellido"]);
    $fecha_nacimiento = trim($cuerpo["fecha_nacimiento"]);
    $telefono = trim($cuerpo["telefono"]);
    $localidad = trim($cuerpo["localidad"]);
    $domicilio = trim($cuerpo["domicilio"]);
    $dni = trim($cuerpo["dni"]);
    $email = trim($cuerpo["email"]);
    $fecha_turno_asignado = trim($cuerpo["fecha_turno_asignado"]);
    $obra_social = trim($cuerpo["obra_social"] ?? "");
    $num_afiliado = trim($cuerpo["num_afiliado"] ?? "");
    $plan = trim($cuerpo["plan"] ?? "");

    try {
        // Verificar primero si existe el paciente para devolver 404 si no existe (R2)
        $check = $pdo->prepare("SELECT id FROM pacientes WHERE id = :id");
        $check->execute([":id" => $id]);
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(["error" => "Paciente no encontrado"]);
            exit();
        }

        $consulta = $pdo->prepare(
            "UPDATE pacientes SET nombre = :nombre, apellido = :apellido, fecha_nacimiento = :fecha_nacimiento, telefono = :telefono, localidad = :localidad, domicilio = :domicilio, dni = :dni, email = :email, fecha_turno_asignado = :fecha_turno_asignado, obra_social = :obra_social, num_afiliado = :num_afiliado, plan = :plan WHERE id = :id"
        );
        $consulta->execute([
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":fecha_nacimiento" => $fecha_nacimiento,
            ":telefono" => $telefono,
            ":localidad" => $localidad,
            ":domicilio" => $domicilio,
            ":dni" => $dni,
            ":email" => $email,
            ":fecha_turno_asignado" => $fecha_turno_asignado,
            ":obra_social" => $obra_social,
            ":num_afiliado" => $num_afiliado,
            ":plan" => $plan,
            ":id" => $id
        ]);

        http_response_code(200);
        echo json_encode([
            "id" => $id,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "fecha_nacimiento" => $fecha_nacimiento,
            "telefono" => $telefono,
            "localidad" => $localidad,
            "domicilio" => $domicilio,
            "dni" => $dni,
            "email" => $email,
            "fecha_turno_asignado" => $fecha_turno_asignado,
            "obra_social" => $obra_social,
            "num_afiliado" => $num_afiliado,
            "plan" => $plan,
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar la base de datos"]);
    }
    exit();
}

// ----- DELETE: borrar un paciente -----
if ($metodo === "DELETE") {
    $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        echo json_encode(["error" => "Falta un id válido"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM pacientes WHERE id = :id");
        $stmt->execute([":id" => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Paciente no encontrado"]);
            exit();
        }

        http_response_code(200);
        echo json_encode(["mensaje" => "Paciente y sus tratamientos eliminados"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al eliminar el paciente"]);
    }
    exit();
}

// ----- GET: Obtener pacientes -----
if ($metodo === "GET") {
    try {
        if (isset($_GET["id"])) {
            $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "El id debe ser numérico"]);
                exit();
            }
            $consulta = $pdo->prepare("SELECT * FROM pacientes WHERE id = :id");
            $consulta->execute([":id" => $id]);
            $paciente = $consulta->fetch(PDO::FETCH_ASSOC);

            if ($paciente === false) {
                http_response_code(404);
                echo json_encode(["error" => "No existe un paciente con ese id"]);
                exit();
            }

            http_response_code(200);
            echo json_encode($paciente, JSON_UNESCAPED_UNICODE);
        } else {
            $consulta = $pdo->query("SELECT * FROM pacientes ORDER BY id ASC");
            http_response_code(200);
            echo json_encode($consulta->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al consultar la base de datos"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);