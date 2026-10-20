// app.js

// url de la API RENDER
const URL_API = "https://clinica-backend-php.onrender.com/api/pacientes.php";

fetch('https://api.open-meteo.com/v1/forecast?latitude=-38.39014043302174&longitude=-60.2742201301069&current_weather=true')
      .then(response => response.json())
      .then(data => {
        const temp = data.current_weather.temperature;
        console.log("Temperatura actual de la zona:", temp, "°C");

        const contenedorClima = document.querySelector("#infoClima");
        if (contenedorClima) {
          contenedorClima.textContent = `Temperatura en Tres Arroyos: ${temp} °C`;
        }
      })
      .catch(error => {
        console.warn("No se pudo cargar el servicio externo de clima:", error);
        const contenedorClima = document.querySelector("#infoClima");
        if (contenedorClima) {
          contenedorClima.textContent = "Clima no disponible";
        }
      });

// ----- El mapa (Etapa 2): Leaflet dibuja, OpenStreetMap pone las imágenes -----
// Establecemos las coordenadas de la clínica (Castelli 965, Tres Arroyos)
// latitud y longitud: -38.39014043302174, -60.2742201301069
const latitudClinica = -38.39014043302174;
const longitudClinica = -60.2742201301069;

const mapa = L.map("mapa").setView([latitudClinica, longitudClinica], 15); // Zoom nivel 15

L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
  maxZoom: 19,
  attribution: "&copy; colaboradores de OpenStreetMap",
}).addTo(mapa);

// Agregamos un marcador fijo para la clínica
const marcadorClinica = L.marker([latitudClinica, longitudClinica])
  .bindPopup("<b>Clínica Odontológica</b><br>Nuestra ubicación central.")
  .addTo(mapa);
  
// Abrimos el popup por defecto
marcadorClinica.openPopup();

// Variable global para saber si estamos creando (POST) o editando (PUT)
let idEdicion = null;

// ----- Listar -----
async function listarPacientes() {
  try {
      const respuesta = await fetch(URL_API);
      if (!respuesta.ok) {
          throw new Error(`HTTP error! status: ${respuesta.status}`);
      }
      const pacientes = await respuesta.json();
      console.log("Pacientes recibidos:", pacientes);
      renderizarPacientes(pacientes);
  } catch (error) {
      console.error("Error al obtener los pacientes:", error);
  }
}

function renderizarPacientes(pacientes) {
  const cuerpo = document.querySelector("#cuerpoTabla");
  cuerpo.innerHTML = "";

  pacientes.forEach(function (paciente) {
    const fila = document.createElement("tr");
    fila.dataset.id = paciente.id;
    fila.innerHTML = `
      <td>${paciente.id}</td><td>${paciente.nombre}</td><td>${paciente.apellido}</td><td>${paciente.fecha_nacimiento}</td>
      <td>${paciente.telefono}</td><td>${paciente.localidad}</td><td>${paciente.domicilio}</td><td>${paciente.dni}</td>
      <td>${paciente.email}</td><td>${paciente.fecha_turno_asignado}</td>
      <td>${paciente.obra_social ?? "—"}</td><td>${paciente.num_afiliado ?? "-"}</td><td>${paciente.plan ?? "-"}</td>
      <td><button class="btn-editar">Editar</button>
          <button class="btn-borrar">Borrar</button></td>
    `;
    cuerpo.appendChild(fila);
  });
}

// ----- Alta -----
document.querySelector("#formAlta").addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const mensaje = document.querySelector("#mensaje");
  mensaje.textContent = "Guardando…";

  const nuevoPaciente = {
    nombre: document.querySelector("#campoNombre").value,
    apellido: document.querySelector("#campoApellido").value,
    fecha_nacimiento: document.querySelector("#campoFechaNacimiento").value,
    telefono: document.querySelector("#campoTelefono").value,
    localidad: document.querySelector("#campoLocalidad").value,
    domicilio: document.querySelector("#campoDomicilio").value,
    dni: document.querySelector("#campoDNI").value,
    email: document.querySelector("#campoEmail").value,
    fecha_turno_asignado: document.querySelector("#campoFechaTurnoAsignado").value,

    obra_social: document.querySelector("#campoObraSocial").value,
    num_afiliado: document.querySelector("#campoNumAfiliado").value,
    plan: document.querySelector("#campoPlan").value,

  };

  const respuesta = await fetch(URL_API, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(nuevoPaciente),
  });

  if (!respuesta.ok) {
    const error = await respuesta.json();
    mensaje.textContent = `Error ${respuesta.status}: ${error.error}`;
    return;
  }

  mensaje.textContent = "";
  document.querySelector("#formAlta").reset();
  listarPacientes();
});

// ----- Baja y edición -----
document.querySelector("#cuerpoTabla").addEventListener("click", async function (evento) {
  const fila = evento.target.closest("tr");
  if (!fila) return;
  const id = fila.dataset.id;
  const mensaje = document.querySelector("#mensaje");

  if (evento.target.classList.contains("btn-borrar")) {
    try {
      const respuesta = await fetch(`${URL_API}?id=${id}`, { method: "DELETE" });
      if (!respuesta.ok) {
        const err = await respuesta.json();
        mensaje.textContent = `Error ${respuesta.status}: ${err.error || "No se pudo borrar"}`;
        return;
      }
      mensaje.textContent = "";
      fila.remove();
      // Si el paciente borrado estaba seleccionado en tratamientos, ocultamos la sección
      if (document.querySelector("#idPacienteSeleccionado").value === id) {
        document.querySelector("#seccionTratamientos").style.display = "none";
      }
    } catch (error) {
      mensaje.textContent = "Error de conexión al intentar borrar el paciente.";
    }
  }

  if (evento.target.classList.contains("btn-editar")) {
    const nombreNuevo = prompt("Nuevo nombre:", fila.children[1].textContent);
    if (nombreNuevo === null) return;
    const apellidoNuevo = prompt("Nuevo apellido:", fila.children[2].textContent);
    const fechaNacimientoNuevo = prompt("Nueva fecha de nacimiento (AAAA-MM-DD):", fila.children[3].textContent);
    const telefonoNuevo = prompt("Nuevo teléfono:", fila.children[4].textContent);
    const localidadNuevo = prompt("Nueva localidad:", fila.children[5].textContent);
    const domicilioNuevo = prompt("Nuevo domicilio:", fila.children[6].textContent);
    const dniNuevo = prompt("Nuevo DNI:", fila.children[7].textContent);
    const emailNuevo = prompt("Nuevo email:", fila.children[8].textContent);
    const fechaTurnoAsignadoNuevo = prompt("Nueva fecha de turno asignado (AAAA-MM-DD):", fila.children[9].textContent);
    const obraSocialNuevo = prompt("Nueva obra social:", fila.children[10].textContent);
    const numAfiliadoNuevo = prompt("Nuevo número de afiliado:", fila.children[11].textContent);
    const planNuevo = prompt("Nuevo plan:", fila.children[12].textContent);

    try {
      const respuesta = await fetch(`${URL_API}?id=${id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          nombre: nombreNuevo,
          apellido: apellidoNuevo,
          fecha_nacimiento: fechaNacimientoNuevo,
          telefono: telefonoNuevo,
          localidad: localidadNuevo,
          domicilio: domicilioNuevo,
          dni: dniNuevo,
          email: emailNuevo,
          fecha_turno_asignado: fechaTurnoAsignadoNuevo,
          obra_social: obraSocialNuevo,
          num_afiliado: numAfiliadoNuevo,
          plan: planNuevo
        }),
      });

      if (!respuesta.ok) {
        const err = await respuesta.json();
        mensaje.textContent = `Error ${respuesta.status}: ${err.error || "Error al actualizar"}`;
        return;
      }

      mensaje.textContent = "";
      const pacienteActualizado = await respuesta.json();
      fila.children[1].textContent = pacienteActualizado.nombre;
      fila.children[2].textContent = pacienteActualizado.apellido;
      fila.children[3].textContent = pacienteActualizado.fecha_nacimiento;
      fila.children[4].textContent = pacienteActualizado.telefono;
      fila.children[5].textContent = pacienteActualizado.localidad;
      fila.children[6].textContent = pacienteActualizado.domicilio;
      fila.children[7].textContent = pacienteActualizado.dni;
      fila.children[8].textContent = pacienteActualizado.email;
      fila.children[9].textContent = pacienteActualizado.fecha_turno_asignado;
      fila.children[10].textContent = pacienteActualizado.obra_social;
      fila.children[11].textContent = pacienteActualizado.num_afiliado;
      fila.children[12].textContent = pacienteActualizado.plan;
    } catch (error) {
      mensaje.textContent = "Error de conexión al intentar editar el paciente.";
    }
  }
});

listarPacientes();

// ---- TABLA TRATAMIENTOS ----

// url que apunta a Render
const URL_TRATAMIENTOS = "https://clinica-backend-php.onrender.com/api/tratamientos.php";

// ----- Lógica para Tratamientos -----

// Al hacer clic en un paciente, mostramos sus tratamientos
document.querySelector("#cuerpoTabla").addEventListener("click", function (evento) {
  // Si hizo clic en un botón, ignoramos (porque eso es borrar/editar)
  if (evento.target.tagName === "BUTTON") return;

  const fila = evento.target.closest("tr");
  if (!fila) return;

  const pacienteId = fila.dataset.id;
  const pacienteNombre = fila.children[1].textContent + " " + fila.children[2].textContent;

  // Mostramos la sección y seteamos los datos
  document.querySelector("#seccionTratamientos").style.display = "block";
  document.querySelector("#nombrePacienteSeleccionado").textContent = pacienteNombre;
  document.querySelector("#idPacienteSeleccionado").value = pacienteId;

  // Cargamos los tratamientos de este paciente
  listarTratamientos(pacienteId);
  
  // Hacemos scroll suave hasta la sección de tratamientos
  document.querySelector("#seccionTratamientos").scrollIntoView({ behavior: 'smooth' });
});

async function listarTratamientos(idPaciente) {
  const respuesta = await fetch(`${URL_TRATAMIENTOS}?id_paciente=${idPaciente}`);
  const tratamientos = await respuesta.json();
  
  const cuerpoTratamientos = document.querySelector("#cuerpoTablaTratamientos");
  cuerpoTratamientos.innerHTML = "";

  if (tratamientos.length === 0) {
      cuerpoTratamientos.innerHTML = "<tr><td colspan='5'>No hay tratamientos registrados.</td></tr>";
      return;
  }

  tratamientos.forEach(t => {
    const fila = document.createElement("tr");
    fila.innerHTML = `
      <td>${t.id}</td>
      <td>${t.numero_diente}</td>
      <td>${t.plan_tratamiento}</td>
      <td>${t.fecha_tratamiento}</td>
      <td>$${t.presupuesto}</td>
    `;
    cuerpoTratamientos.appendChild(fila);
  });
}

// Alta de nuevo tratamiento
document.querySelector("#formTratamiento").addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const idPaciente = document.querySelector("#idPacienteSeleccionado").value;
  
  const nuevoTratamiento = {
    id_paciente: idPaciente,
    numero_diente: document.querySelector("#campoDiente").value,
    plan_tratamiento: document.querySelector("#campoPlanTratamiento").value,
    fecha_tratamiento: document.querySelector("#campoFechaTratamiento").value,
    presupuesto: document.querySelector("#campoPresupuesto").value
  };

  const respuesta = await fetch(URL_TRATAMIENTOS, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(nuevoTratamiento),
  });

  if (respuesta.ok) {
    document.querySelector("#formTratamiento").reset();
    listarTratamientos(idPaciente); // Recargamos la tabla
  } else {
    alert("Error al guardar el tratamiento");
  }
});

// Lógica para alternar entre pantallas y menú
document.addEventListener('DOMContentLoaded', () => {
  const btnComenzar = document.getElementById('btnComenzar');
  const pantallaBienvenida = document.getElementById('pantallaBienvenida');
  const pantallaPrincipal = document.getElementById('pantallaPrincipal');

  if (btnComenzar) {
    btnComenzar.addEventListener('click', () => {
      pantallaBienvenida.classList.add('ocultar');
      pantallaPrincipal.classList.remove('ocultar');
      
      // Si usas Leaflet mapa, fuerza un resize al mostrarlo por primera vez
      setTimeout(() => {
        if (typeof mapa !== 'undefined' && mapa.invalidateSize) {
          mapa.invalidateSize();
        }
      }, 200);
    });
  }
});

// Función para cambiar entre las secciones del menú (Pacientes / Mapa)
function mostrarSeccion(idSeccion, evento) {
  document.querySelectorAll('.seccion-contenido').forEach(sec => {
    sec.classList.add('ocultar');
  });
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove('activo');
  });

  document.getElementById(idSeccion).classList.remove('ocultar');
  if (evento && evento.currentTarget) {
    evento.currentTarget.classList.add('activo');
  }

  if (idSeccion === 'seccionMapa' && typeof mapa !== 'undefined' && mapa.invalidateSize) {
    setTimeout(() => mapa.invalidateSize(), 100);
  }
}