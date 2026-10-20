# Clínica Odontológica - Emilia Ongarini

## 1. Presentación
* **Nombre de la aplicación:** Sistema de Gestión - Clínica Odontológica Emilia Ongarini
* **Tema:** Gestión de pacientes, turnos asignados y planes de tratamiento odontológico (ortodoncia y alineadores invisibles).
* **Integrantes:** Lionel Lambrecht, Perla Sabrina Viera, Malena Arana, Lucas Zulaica y Nico Niembro
* **URL pública (Frontend):** https://app-clinica-odontologica.onrender.com/
* **URL de la API (Backend):** https://clinica-backend-php.onrender.com/api/pacientes.php

---

## 2. Cómo correrla en local
1. Instalar **XAMPP** (con soporte para PHP y la extensión `pdo_pgsql` habilitada en `php.ini` si se usa PostgreSQL local) y un motor de base de datos PostgreSQL.
2. Clonar o descargar este repositorio dentro de la carpeta `htdocs/` de XAMPP (por ejemplo: `C:\xampp\htdocs\app_clinica_odontologica`).
3. Crear una base de datos llamada `clinica_odontologica` y ejecutar el script ubicado en `sql/clinica.sql` para crear las tablas `pacientes` y `tratamientos`.
4. Configurar las variables de entorno locales (`DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`) en el entorno del sistema o en Apache; si no se define `RESEND_API_KEY`, el sistema registrará los avisos por correo automáticamente en el archivo local `api/avisos.log`.
5. En `app.js`, apuntar las constantes `URL_API` y `URL_TRATAMIENTOS` a `http://localhost/app_clinica_odontologica/api/pacientes.php` y `http://localhost/app_clinica_odontologica/api/tratamientos.php`.
6. Iniciar Apache desde el panel de XAMPP y abrir `http://localhost/app_clinica_odontologica/` en el navegador.

---

## 3. Endpoints de la API

### Recurso principal: `/api/pacientes.php`
| Método | Ruta | Qué recibe | Qué devuelve | Códigos de estado |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/pacientes.php` | Nada (o `?id={id}` por query string) | Lista JSON de todos los pacientes, o el objeto JSON del paciente solicitado. | `200 OK`, `400 Bad Request` (ID no numérico), `404 Not Found` (no existe), `500 Internal Server Error` |
| `POST` | `/api/pacientes.php` | Cuerpo JSON con `nombre`, `apellido`, `fecha_nacimiento`, `telefono`, `localidad`, `domicilio`, `dni`, `email`, `fecha_turno_asignado`, y opcionales `obra_social`, `num_afiliado`, `plan`. | Objeto JSON del paciente creado con su `id` asignado. | `201 Created`, `400 Bad Request` (faltan datos o formato inválido), `500 Internal Server Error` |
| `PUT` | `/api/pacientes.php?id={id}` | `id` por query string y cuerpo JSON con los campos actualizados del paciente. | Objeto JSON del paciente actualizado. | `200 OK`, `400 Bad Request` (ID o datos inválidos), `404 Not Found` (no existe), `500 Internal Server Error` |
| `DELETE` | `/api/pacientes.php?id={id}` | `id` válido por query string. | Mensaje JSON confirmando la eliminación del paciente y sus tratamientos asociados. | `200 OK`, `400 Bad Request` (ID inválido), `404 Not Found` (no existe), `500 Internal Server Error` |

### Recurso secundario: `/api/tratamientos.php`
| Método | Ruta | Qué recibe | Qué devuelve | Códigos de estado |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/tratamientos.php?id_paciente={id}` | `id_paciente` por query string. | Arreglo JSON con los tratamientos del paciente ordenados por fecha. | `200 OK`, `400 Bad Request`, `500 Internal Server Error` |
| `POST` | `/api/tratamientos.php` | Cuerpo JSON con `id_paciente`, `numero_diente`, `plan_tratamiento`, `fecha_tratamiento` y `presupuesto`. | Mensaje JSON de confirmación. | `201 Created`, `400 Bad Request`, `500 Internal Server Error` |

---

## 4. Variables de entorno
Ninguna credencial ni clave privada se encuentra en el código fuente. El backend lee las siguientes variables mediante `getenv()`:

* `DATABASE_URL`: Cadena de conexión completa provista por Render para conectarse a la base de datos PostgreSQL en la nube.
* `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`: Variables individuales de respaldo para la conexión a la base de datos en entorno local.
* `RESEND_API_KEY`: Clave privada de autenticación para el servicio de envío de correos transaccionales **Resend**.
* `EMAIL_AVISOS`: Dirección de correo electrónico destinataria donde llegan los recordatorios de turnos al dar de alta un paciente.

---

## 5. Servicios externos
1. **Resend (Envío de emails — llamado desde el Backend en `api/servicios.php`):**
   * **Por qué desde el backend:** Requiere una clave privada (`RESEND_API_KEY`) que no debe exponerse en el navegador del cliente, además de evitar bloqueos de CORS.
   * **Datos personales que salen:** Se envía el nombre de pila del paciente (`nombre`) y la fecha de su turno (`fecha_turno_asignado`) hacia los servidores de Resend (`https://api.resend.com/emails`) para notificar a la casilla configurada en `EMAIL_AVISOS`.
   * **Qué pasa si falla (Degradación elegante):** La petición cURL tiene un timeout de 5 segundos. Si el servicio no responde, falla la red o no hay API Key configurada (en cuyo caso guarda el registro en `avisos.log`), la función no interrumpe el flujo: el paciente se guarda igualmente en la base de datos y la API responde `201 Created`.
2. **Open-Meteo (Clima actual — llamado desde el Navegador en `app.js`):**
   * **Por qué desde el navegador:** Es una API pública gratuita que no requiere API Key y permite mostrar en el encabezado el clima actual de la ciudad del consultorio (Tres Arroyos, coordenadas `-38.3901, -60.2742`).
   * **Datos personales que salen:** Ningún dato de los pacientes; únicamente la solicitud HTTP desde la IP del navegador hacia `api.open-meteo.com`.
   * **Qué pasa si falla (Degradación elegante):** Se captura el error en el bloque `.catch()`, mostrando el texto `"Clima no disponible"` en el widget superior sin afectar la gestión de pacientes.
3. **Leaflet + OpenStreetMap (Mapa interactivo — llamado desde el Navegador en `index.html` y `app.js`):**
   * **Por qué desde el navegador:** Renderiza en el cliente las teselas del mapa y el marcador de ubicación del consultorio (Castelli 965, Tres Arroyos).
   * **Datos personales que salen:** Ningún dato de los pacientes.
   * **Qué pasa si falla:** El mapa está aislado en su propia pestaña de navegación (`Ubicaciones / Mapa`), por lo que el CRUD de pacientes y tratamientos funciona de forma independiente.

---

## 6. Despliegue
* **Proveedor elegido:** **Render** (modelo **PaaS** — *Platform as a Service*).
* **Justificación:** Se eligió Render como PaaS porque automatiza el despliegue continuo desde GitHub, provee certificados HTTPS de manera nativa y permite alojar de forma desacoplada pero integrada los tres componentes de la arquitectura:
  1. `app_clinica_odontologica` (*Static Site* para el frontend HTML/CSS/JS).
  2. `clinica-backend-php` (*Web Service* con entorno Docker para ejecutar la API REST en PHP).
  3. `clinica-db` (Base de datos gestionada *PostgreSQL*).
  Además, al ser un PaaS real y no un hosting compartido gratuito con bloqueadores antibot, garantiza que la API podrá ser consumida sin bloqueos desde la aplicación Android en el TP N.º 2.
* **Pasos para volver a desplegar:**
  1. Subir los cambios mediante `git push` a la rama principal del repositorio en GitHub.
  2. Render detecta el nuevo commit y reconstruye automáticamente el *Static Site* y el contenedor Docker del backend.
  3. En caso de recrear el entorno desde cero, crear la instancia PostgreSQL en Render, ejecutar `sql/clinica.sql`, crear el Web Service vinculando las variables `DATABASE_URL`, `RESEND_API_KEY` y `EMAIL_AVISOS` en la pestaña *Environment*, y desplegar el Static Site.

---

## 7. Limitaciones conocidas
* **Plan gratuito de Render (Cold Start):** El servicio backend en el plan gratuito de Render entra en suspensión tras 15 minutos de inactividad, por lo que la primera petición luego de ese período puede demorar entre 30 y 50 segundos en responder.
* **Envío de correos en modo sandbox de Resend:** Al utilizar el remitente de prueba `onboarding@resend.dev` sin un dominio propio verificado por DNS, Resend solo permite entregar correos reales a la dirección registrada en la cuenta (`EMAIL_AVISOS`).
* **Edición de pacientes:** La modificación de datos de un paciente (`PUT`) se realiza mediante cuadros de diálogo (`prompt`) secuenciales del navegador en lugar de un modal o formulario dedicado, y los tratamientos solo implementan listado (`GET`) y alta (`POST`).
* **Sistema de turnos:** Somos conscientes de que la aplicación web full-stack escasea de un sistema de turnos sólido, con una tabla propia 'Turnos' en la base de datos
y su correspondiente programación. La razón de que no se llevó a cabo es porque no llegabamos con el tiempo para la primer fecha de entrega.

## 8. Aportes
* **Propuesta de idea de proyecto, análisis y charla con la odontóloga Emilia Ongarini:** Perla Sabrina Viera y Malena Arana.

* **Lionel Lambrecht:** Elaboración del README.md. Encargado de ensamblar todos los aportes de cada uno en un solo proyecto (Project Manager). Encargado de los cumplimientos: Requisito 1, Requisito 9, Requisito 10 y requisito 6 (Desplegar la app en un servicio cloud). Desarrollador de la base de datos MySQL y PostgreSQL. Desarrollador del repositorio en GitHUB.
* **Nicolas Niembro:** Integración de la API de la Temperatura en Tres Arroyos. Encargado de los cumplimientos: Requisito 4 y Requisito 5.
* **Perla Sabrina Viera:** Encargada de los cumplimientos: Requisito 2 y Requisito 3. Elaboración de la hoja PDF de entrega. Desarrollo del CSS.
* **Malena Arana:** Encargada de los cumplimientos: Requisito 7 y Requisito 8.
* **Lucas Zulaica:** Desarrollador del metodo 'delete' y servicios.php.