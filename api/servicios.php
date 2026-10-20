<?php
// api/servicios.php — todo lo que la API le pide a servicios cloud externos.

require_once "config.php";

// ----- Cliente HTTP genérico -----
// Devuelve [código de estado, cuerpo ya decodificado]. Si no hubo respuesta
// (sin red, timeout, dominio inexistente), el código es 0.
function pedirJSON($url, $metodo = "GET", $cuerpo = null, $cabeceras = []) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // guardar la respuesta, no mostrarla
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);            // esperar hasta 5 segundos
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);  // tipo de operacion -- GET, post,...
    curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);

    if ($cuerpo !== null) { // datos para enviar en post...
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cuerpo));
        $cabeceras[] = "Content-Type: application/json";
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $cabeceras); // convertir a JSON

    $respuesta = curl_exec($curl);                      // respuesta en JSON
    $estado = curl_getinfo($curl, CURLINFO_HTTP_CODE); // espero el 200 OK

    if ($respuesta === false) {
        return [0, null];
    }
    return [$estado, json_decode($respuesta, true)];
}

// ----- aviso por email (Resend, con clave) -----

function enviarAviso($paciente) {
    $texto = "Hola " . $paciente["nombre"] . ", acordate que tenés turno en la clínica"
            . (!empty($paciente["fecha_turno_asignado"]) ? " el " . $paciente["fecha_turno_asignado"] : "") . ".";
    
    if (RESEND_API_KEY === "") {
        file_put_contents(
            __DIR__ . "/avisos.log",
            date("Y-m-d H:i:s") . " " . $paciente["email"] . " " . $texto . PHP_EOL,
            FILE_APPEND
        );
        return true;
    }

    [$estado] = pedirJSON(
        "https://api.resend.com/emails",
        "POST",
        [
            "from" => "Clínica Odontológica <onboarding@resend.dev>",
            "to" => [EMAIL_AVISOS],
            "subject" => "Recordatorio de turno",
            "text" => $texto,
        ],
        ["Authorization: Bearer " . RESEND_API_KEY]
    );

    return $estado === 200;
}