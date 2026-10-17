/**
 * Controlador de la vista de detalle de una reserva.
 * Permite cambiar de estado (confirmar o cancelar) y actualiza el timeline.
 */

import { api, ApiException } from './api.js';
import { ui } from './ui.js';

document.addEventListener('DOMContentLoaded', () => {
  const btnConfirmar = document.getElementById('btn-confirmar-reserva');
  const btnCancelar = document.getElementById('btn-cancelar-reserva');
  const badgeContainer = document.getElementById('detalle-badge-container');
  const actionsContainer = document.getElementById('detalle-actions');
  const timelineContainer = document.getElementById('timeline-container');

  const modalCancelar = document.getElementById('modal-confirmar-cancelar');
  const btnCerrarCancelar = document.getElementById('btn-cerrar-cancelar');
  const btnAbortarCancelar = document.getElementById('btn-abortar-cancelar');
  const btnEjecutarCancelar = document.getElementById('btn-ejecutar-cancelar');

  const reservaId = btnConfirmar?.dataset.id || btnCancelar?.dataset.id;
  if (!reservaId) return;

  function actualizarBadge(nuevoEstado) {
    if (!badgeContainer) return;
    badgeContainer.innerHTML = '';
    badgeContainer.appendChild(ui.createBadge(nuevoEstado));
  }

  function agregarEventoTimeline(tipo, descripcion, fechaIso) {
    if (!timelineContainer) return;

    // Si había un mensaje de "No hay eventos", quitarlo
    const emptyMsg = timelineContainer.querySelector('p.text-muted');
    if (emptyMsg) {
      emptyMsg.remove();
    }

    const item = document.createElement('div');
    item.className = `timeline-item timeline-${tipo}`;

    const marker = document.createElement('div');
    marker.className = 'timeline-marker';

    const content = document.createElement('div');
    content.className = 'timeline-content';

    const header = document.createElement('div');
    header.className = 'timeline-header';

    const spanType = document.createElement('span');
    spanType.className = 'timeline-type';
    spanType.textContent = tipo.charAt(0).toUpperCase() + tipo.slice(1);

    const time = document.createElement('time');
    time.className = 'timeline-time';
    const dateObj = new Date(fechaIso);
    const day = String(dateObj.getDate()).padStart(2, '0');
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const year = dateObj.getFullYear();
    const hours = String(dateObj.getHours()).padStart(2, '0');
    const mins = String(dateObj.getMinutes()).padStart(2, '0');
    time.textContent = `${day}/${month}/${year} ${hours}:${mins}`;

    header.appendChild(spanType);
    header.appendChild(time);

    const desc = document.createElement('p');
    desc.className = 'timeline-desc';
    desc.textContent = descripcion;

    content.appendChild(header);
    content.appendChild(desc);

    item.appendChild(marker);
    item.appendChild(content);

    timelineContainer.appendChild(item);
  }

  // ---------------------------------------------------------------------------
  // 1. Confirmar reserva
  // ---------------------------------------------------------------------------
  btnConfirmar?.addEventListener('click', async () => {
    ui.setButtonLoading(btnConfirmar, true);

    try {
      const res = await api.patch(`/api/reservas/${reservaId}/estado`, {
        estado: 'confirmada',
      });

      actualizarBadge('confirmada');
      btnConfirmar.remove(); // Ya está confirmada, no se puede volver a confirmar

      const ultimoEvento = res.data.eventos?.slice(-1)[0];
      if (ultimoEvento) {
        agregarEventoTimeline(ultimoEvento.tipo, ultimoEvento.descripcion, ultimoEvento.fecha_creacion);
      }

      ui.showFeedback('Reserva confirmada con éxito.', 'success');
    } catch (err) {
      ui.showFeedback(err.message || 'Error al confirmar la reserva.', 'danger');
    } finally {
      if (btnConfirmar.parentNode) {
        ui.setButtonLoading(btnConfirmar, false);
      }
    }
  });

  // ---------------------------------------------------------------------------
  // 2. Cancelar reserva con confirmación
  // ---------------------------------------------------------------------------
  function abrirModalCancelar() {
    if (modalCancelar) modalCancelar.hidden = false;
  }

  function cerrarModalCancelar() {
    if (modalCancelar) modalCancelar.hidden = true;
  }

  btnCancelar?.addEventListener('click', abrirModalCancelar);
  btnCerrarCancelar?.addEventListener('click', cerrarModalCancelar);
  btnAbortarCancelar?.addEventListener('click', cerrarModalCancelar);
  modalCancelar?.addEventListener('click', (e) => {
    if (e.target === modalCancelar) cerrarModalCancelar();
  });

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalCancelar && !modalCancelar.hidden) {
      cerrarModalCancelar();
    }
  });

  btnEjecutarCancelar?.addEventListener('click', async () => {
    ui.setButtonLoading(btnEjecutarCancelar, true);

    try {
      const res = await api.patch(`/api/reservas/${reservaId}/estado`, {
        estado: 'cancelada',
      });

      cerrarModalCancelar();
      actualizarBadge('cancelada');

      // Si está cancelada es estado final: quitamos los botones de acción
      if (actionsContainer) {
        actionsContainer.innerHTML = '';
      }

      const ultimoEvento = res.data.eventos?.slice(-1)[0];
      if (ultimoEvento) {
        agregarEventoTimeline(ultimoEvento.tipo, ultimoEvento.descripcion, ultimoEvento.fecha_creacion);
      }

      ui.showFeedback('Reserva cancelada correctamente.', 'success');
    } catch (err) {
      cerrarModalCancelar();
      ui.showFeedback(err.message || 'No se pudo cancelar la reserva.', 'danger');
    } finally {
      ui.setButtonLoading(btnEjecutarCancelar, false);
    }
  });
});
