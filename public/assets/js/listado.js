/**
 * Controlador de la vista de listado de reservas.
 * Consume la API REST de reservas con JavaScript vanilla.
 */

import { api, ApiException } from "./api.js";
import { ui } from "./ui.js";

document.addEventListener("DOMContentLoaded", () => {
  const formFiltros = document.getElementById("form-filtros");
  const btnLimpiar = document.getElementById("btn-limpiar-filtros");
  const tbody = document.getElementById("tbody-reservas");
  const loadingBar = document.getElementById("table-loading-bar");
  const totalCount = document.getElementById("total-count");
  const paginationBar = document.getElementById("pagination-bar");
  const paginationInfo = document.getElementById("pagination-info");
  const paginationPages = document.getElementById("pagination-pages");

  // Modales
  const modalCrear = document.getElementById("modal-crear");
  const btnAbrirCrear = document.getElementById("btn-abrir-crear");
  const btnCerrarModal = document.getElementById("btn-cerrar-modal");
  const btnCancelarModal = document.getElementById("btn-cancelar-modal");
  const formCrear = document.getElementById("form-crear-reserva");
  const btnGuardar = document.getElementById("btn-guardar-reserva");

  const modalCancelar = document.getElementById("modal-confirmar-cancelar");
  const btnCerrarCancelar = document.getElementById("btn-cerrar-cancelar");
  const btnAbortarCancelar = document.getElementById("btn-abortar-cancelar");
  const btnEjecutarCancelar = document.getElementById("btn-ejecutar-cancelar");
  const cancelReservaInfo = document.getElementById("cancel-reserva-info");

  let reservaACancelarId = null;

  // ---------------------------------------------------------------------------
  // 1. Filtrado dinámico sin recargar la página
  // ---------------------------------------------------------------------------

  let debounceTimer = null;

  function obtenerParametrosFiltros(pagina = 1) {
    const formData = new FormData(formFiltros);
    const params = { pagina };
    for (const [key, value] of formData.entries()) {
      const val = typeof value === "string" ? value.trim() : value;
      if (val) {
        params[key] = val;
      }
    }
    return params;
  }

  async function cargarReservas(pagina = 1) {
    if (loadingBar) loadingBar.hidden = false;

    try {
      const params = obtenerParametrosFiltros(pagina);
      const res = await api.get("/api/reservas", params);
      renderizarTabla(res.data, res.meta);
    } catch (err) {
      ui.showFeedback(err.message || "Error al cargar las reservas.", "danger");
    } finally {
      if (loadingBar) loadingBar.hidden = true;
    }
  }

  function renderizarTabla(items, meta) {
    tbody.innerHTML = "";

    if (!items || items.length === 0) {
      const tr = document.createElement("tr");
      tr.className = "empty-row";
      const td = document.createElement("td");
      td.colSpan = 7;
      td.className = "text-center text-muted py-8";
      td.textContent = "No se encontraron reservas con los filtros aplicados.";
      tr.appendChild(td);
      tbody.appendChild(tr);
    } else {
      for (const r of items) {
        tbody.appendChild(crearFilaReserva(r));
      }
    }

    if (totalCount) {
      totalCount.textContent = meta?.total ?? items.length;
    }

    if (paginationInfo) {
      paginationInfo.textContent = `Mostrando ${items.length} de `;
      const strong = document.createElement("strong");
      strong.id = "total-count";
      strong.textContent = meta?.total ?? items.length;
      paginationInfo.appendChild(strong);
      paginationInfo.append(" reservas");
    }

    renderizarPaginacion(meta);
  }

  function crearFilaReserva(r) {
    const tr = document.createElement("tr");
    tr.id = `fila-reserva-${r.id}`;
    tr.dataset.id = String(r.id);
    tr.dataset.estado = r.estado;

    // ID
    const tdId = document.createElement("td");
    tdId.className = "cell-id";
    tdId.textContent = `#${r.id}`;
    tr.appendChild(tdId);

    // Huésped
    const tdHuesped = document.createElement("td");
    const divNombre = document.createElement("div");
    divNombre.className = "cell-primary font-medium";
    divNombre.textContent = r.nombre_huesped;
    const divEmail = document.createElement("div");
    divEmail.className = "cell-secondary text-muted";
    divEmail.textContent = r.email_huesped;
    tdHuesped.appendChild(divNombre);
    tdHuesped.appendChild(divEmail);
    tr.appendChild(tdHuesped);

    // Alojamiento
    const tdAlojamiento = document.createElement("td");
    tdAlojamiento.textContent = r.nombre_alojamiento;
    tr.appendChild(tdAlojamiento);

    // Fechas
    const tdFechas = document.createElement("td");
    const divRango = document.createElement("div");
    divRango.textContent = `${ui.formatDate(r.fecha_entrada)} → ${ui.formatDate(r.fecha_salida)}`;
    const spanNoches = document.createElement("span");
    spanNoches.className = "text-muted small";
    spanNoches.textContent = `${r.noches} noche${r.noches !== 1 ? "s" : ""}`;
    tdFechas.appendChild(divRango);
    tdFechas.appendChild(spanNoches);
    tr.appendChild(tdFechas);

    // Importe
    const tdImporte = document.createElement("td");
    tdImporte.className = "text-right font-medium";
    tdImporte.textContent = ui.formatCurrency(r.importe);
    tr.appendChild(tdImporte);

    // Estado Badge
    const tdBadge = document.createElement("td");
    tdBadge.className = "text-center cell-badge";
    tdBadge.appendChild(ui.createBadge(r.estado));
    tr.appendChild(tdBadge);

    // Acciones
    const tdAcciones = document.createElement("td");
    tdAcciones.className = "text-right cell-actions";

    const linkDetalle = document.createElement("a");
    linkDetalle.href = `/reservas/${r.id}`;
    linkDetalle.className = "btn btn-xs btn-outline";
    linkDetalle.title = "Ver detalle";
    linkDetalle.textContent = "Detalle";
    tdAcciones.appendChild(linkDetalle);

    if (r.estado !== "cancelada") {
      const btnCancel = document.createElement("button");
      btnCancel.type = "button";
      btnCancel.className = "btn btn-xs btn-danger-outline btn-cancelar";
      btnCancel.dataset.id = String(r.id);
      btnCancel.dataset.nombre = r.nombre_huesped;
      btnCancel.textContent = "Cancelar";
      tdAcciones.appendChild(btnCancel);
    }

    tr.appendChild(tdAcciones);
    return tr;
  }

  function renderizarPaginacion(meta) {
    if (!paginationPages) return;
    paginationPages.innerHTML = "";
    if (!meta || meta.total_paginas <= 1) return;

    for (let p = 1; p <= meta.total_paginas; p++) {
      const a = document.createElement("a");
      a.href = `?pagina=${p}`;
      a.className = `page-link ${p === meta.pagina ? "active" : ""}`;
      a.dataset.page = String(p);
      a.textContent = String(p);
      paginationPages.appendChild(a);
    }
  }

  formFiltros.addEventListener("submit", (e) => {
    e.preventDefault();
    cargarReservas(1);
  });

  // Filtro en tiempo real al escribir o cambiar select
  formFiltros.addEventListener("input", (e) => {
    if (e.target.id === "filtro-q") {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => cargarReservas(1), 300);
    } else {
      cargarReservas(1);
    }
  });

  btnLimpiar.addEventListener("click", () => {
    formFiltros.reset();
    cargarReservas(1);
  });

  // Delegación de eventos para paginación AJAX
  paginationBar?.addEventListener("click", (e) => {
    const link = e.target.closest(".page-link");
    if (link && link.dataset.page) {
      e.preventDefault();
      cargarReservas(parseInt(link.dataset.page, 10));
    }
  });

  // ---------------------------------------------------------------------------
  // 2. Crear reserva (Validación cliente + envío API + Errores servidor)
  // ---------------------------------------------------------------------------

  function abrirModalCrear() {
    ui.clearFormErrors(formCrear);
    formCrear.reset();
    modalCrear.hidden = false;
    const primerInput = formCrear.querySelector("input");
    if (primerInput) primerInput.focus();
  }

  function cerrarModalCrear() {
    modalCrear.hidden = true;
    ui.clearFormErrors(formCrear);
  }

  btnAbrirCrear?.addEventListener("click", abrirModalCrear);
  btnCerrarModal?.addEventListener("click", cerrarModalCrear);
  btnCancelarModal?.addEventListener("click", cerrarModalCrear);

  // Cerrar modal al pulsar Escape o fondo
  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (!modalCrear.hidden) cerrarModalCrear();
      if (!modalCancelar.hidden) cerrarModalCancelar();
    }
  });

  modalCrear?.addEventListener("click", (e) => {
    if (e.target === modalCrear) cerrarModalCrear();
  });

  formCrear.addEventListener("submit", async (e) => {
    e.preventDefault();
    ui.clearFormErrors(formCrear);

    const formData = new FormData(formCrear);
    const data = {
      nombre_huesped: String(formData.get("nombre_huesped") || "").trim(),
      email_huesped: String(formData.get("email_huesped") || "").trim(),
      nombre_alojamiento: String(
        formData.get("nombre_alojamiento") || "",
      ).trim(),
      fecha_entrada: String(formData.get("fecha_entrada") || "").trim(),
      fecha_salida: String(formData.get("fecha_salida") || "").trim(),
      importe: String(formData.get("importe") || "").trim(),
      notas: String(formData.get("notas") || "").trim() || null,
    };

    // Validación básica en el cliente
    let hayErroresCliente = false;
    if (!data.nombre_huesped) {
      ui.setFieldError(
        formCrear,
        "nombre_huesped",
        "El nombre es obligatorio.",
      );
      hayErroresCliente = true;
    }
    if (!data.email_huesped) {
      ui.setFieldError(formCrear, "email_huesped", "El email es obligatorio.");
      hayErroresCliente = true;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email_huesped)) {
      ui.setFieldError(
        formCrear,
        "email_huesped",
        "Introduce un email válido.",
      );
      hayErroresCliente = true;
    }
    if (!data.nombre_alojamiento) {
      ui.setFieldError(
        formCrear,
        "nombre_alojamiento",
        "El alojamiento es obligatorio.",
      );
      hayErroresCliente = true;
    }
    if (!data.fecha_entrada) {
      ui.setFieldError(
        formCrear,
        "fecha_entrada",
        "La fecha de entrada es obligatoria.",
      );
      hayErroresCliente = true;
    }
    if (!data.fecha_salida) {
      ui.setFieldError(
        formCrear,
        "fecha_salida",
        "La fecha de salida es obligatoria.",
      );
      hayErroresCliente = true;
    }
    if (
      data.fecha_entrada &&
      data.fecha_salida &&
      data.fecha_salida <= data.fecha_entrada
    ) {
      ui.setFieldError(
        formCrear,
        "fecha_salida",
        "La salida debe ser posterior a la entrada.",
      );
      hayErroresCliente = true;
    }
    if (
      !data.importe ||
      isNaN(parseFloat(data.importe)) ||
      parseFloat(data.importe) <= 0
    ) {
      ui.setFieldError(
        formCrear,
        "importe",
        "Introduce un importe válido mayor que 0.",
      );
      hayErroresCliente = true;
    }

    if (hayErroresCliente) {
      return;
    }

    // Envío a la API
    ui.setButtonLoading(btnGuardar, true);

    try {
      const res = await api.post("/api/reservas", data);
      cerrarModalCrear();
      ui.showFeedback(
        `Reserva #${res.data.id} para ${res.data.nombre_huesped} creada con éxito.`,
        "success",
      );
      // Recargar la tabla con la lista actualizada
      cargarReservas(1);
    } catch (err) {
      if (
        err instanceof ApiException &&
        err.status === 400 &&
        Object.keys(err.fields).length > 0
      ) {
        ui.applyFieldErrors(formCrear, err.fields);
      } else {
        ui.showFeedback(err.message || "Error al crear la reserva.", "danger");
      }
    } finally {
      ui.setButtonLoading(btnGuardar, false);
    }
  });

  // ---------------------------------------------------------------------------
  // 3. Cancelar reserva con confirmación
  // ---------------------------------------------------------------------------

  function abrirModalCancelar(id, nombre) {
    reservaACancelarId = id;
    if (cancelReservaInfo) {
      cancelReservaInfo.textContent = `#${id} (${nombre})`;
    }
    modalCancelar.hidden = false;
  }

  function cerrarModalCancelar() {
    modalCancelar.hidden = true;
    reservaACancelarId = null;
  }

  tbody.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-cancelar");
    if (btn && btn.dataset.id) {
      abrirModalCancelar(btn.dataset.id, btn.dataset.nombre || "");
    }
  });

  btnCerrarCancelar?.addEventListener("click", cerrarModalCancelar);
  btnAbortarCancelar?.addEventListener("click", cerrarModalCancelar);
  modalCancelar?.addEventListener("click", (e) => {
    if (e.target === modalCancelar) cerrarModalCancelar();
  });

  btnEjecutarCancelar?.addEventListener("click", async () => {
    if (!reservaACancelarId) return;

    ui.setButtonLoading(btnEjecutarCancelar, true);

    try {
      const res = await api.patch(
        `/api/reservas/${reservaACancelarId}/estado`,
        {
          estado: "cancelada",
        },
      );

      cerrarModalCancelar();
      ui.showFeedback(
        `Reserva #${reservaACancelarId} cancelada correctamente.`,
        "success",
      );

      // Actualizar fila en la tabla sin recargar toda la página
      const fila = document.getElementById(
        `fila-reserva-${reservaACancelarId}`,
      );
      if (fila) {
        fila.dataset.estado = "cancelada";
        const badgeCell = fila.querySelector(".cell-badge");
        if (badgeCell) {
          badgeCell.innerHTML = "";
          badgeCell.appendChild(ui.createBadge("cancelada"));
        }
        const cancelBtn = fila.querySelector(".btn-cancelar");
        if (cancelBtn) {
          cancelBtn.remove();
        }
      }
    } catch (err) {
      cerrarModalCancelar();
      ui.showFeedback(
        err.message || "No se pudo cancelar la reserva.",
        "danger",
      );
    } finally {
      ui.setButtonLoading(btnEjecutarCancelar, false);
    }
  });
});
