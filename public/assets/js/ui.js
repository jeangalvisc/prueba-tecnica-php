/**
 * Utilidades de interfaz de usuario y feedback visual.
 */

let feedbackTimeout = null;

export const ui = {
  /**
   * Muestra un banner de feedback (éxito o error).
   * @param {string} message
   * @param {'success'|'danger'} [type='success']
   * @param {number} [duration=5000]
   */
  showFeedback(message, type = 'success', duration = 5000) {
    const alert = document.getElementById('feedback-alert');
    const text = document.getElementById('feedback-text');
    if (!alert || !text) return;

    if (feedbackTimeout) {
      clearTimeout(feedbackTimeout);
    }

    text.textContent = message;
    alert.className = `feedback-alert alert-${type}`;
    alert.hidden = false;

    if (duration > 0) {
      feedbackTimeout = setTimeout(() => {
        alert.hidden = true;
      }, duration);
    }
  },

  /**
   * Oculta el banner de feedback.
   */
  hideFeedback() {
    const alert = document.getElementById('feedback-alert');
    if (alert) {
      alert.hidden = true;
    }
    if (feedbackTimeout) {
      clearTimeout(feedbackTimeout);
    }
  },

  /**
   * Limpia todos los mensajes y estados de error de un formulario.
   * @param {HTMLFormElement} form
   */
  clearFormErrors(form) {
    form.querySelectorAll('.form-control').forEach(input => {
      input.classList.remove('is-invalid');
    });
    form.querySelectorAll('[data-error-for]').forEach(span => {
      span.textContent = '';
    });
  },

  /**
   * Marca un campo con error y muestra el mensaje debajo.
   * @param {HTMLFormElement} form
   * @param {string} field
   * @param {string} message
   */
  setFieldError(form, field, message) {
    const input = form.elements.namedItem(field);
    if (input && input instanceof HTMLElement) {
      input.classList.add('is-invalid');
    }
    const errorSpan = form.querySelector(`[data-error-for="${field}"]`);
    if (errorSpan) {
      errorSpan.textContent = message;
    }
  },

  /**
   * Aplica un mapa de errores de campo devueltos por la API (HTTP 400).
   * @param {HTMLFormElement} form
   * @param {Record<string, string>} fields
   */
  applyFieldErrors(form, fields) {
    this.clearFormErrors(form);
    for (const [field, message] of Object.entries(fields)) {
      this.setFieldError(form, field, message);
    }
    // Foco en el primer campo con error
    const firstInvalid = form.querySelector('.is-invalid');
    if (firstInvalid && typeof firstInvalid.focus === 'function') {
      firstInvalid.focus();
    }
  },

  /**
   * Formatea una fecha YYYY-MM-DD a DD/MM/YYYY.
   * @param {string} dateString
   * @returns {string}
   */
  formatDate(dateString) {
    if (!dateString) return '';
    const parts = dateString.split('-');
    if (parts.length === 3) {
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateString;
  },

  /**
   * Formatea un importe numérico o string a moneda con 2 decimales y coma ("1.250,50 €").
   * @param {string|number} amount
   * @returns {string}
   */
  formatCurrency(amount) {
    const num = typeof amount === 'number' ? amount : parseFloat(amount);
    if (isNaN(num)) return '0,00 €';
    return new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency: 'EUR',
    }).format(num);
  },

  /**
   * Crea un badge de estado.
   * @param {string} estado
   * @returns {HTMLSpanElement}
   */
  createBadge(estado) {
    const span = document.createElement('span');
    span.className = 'badge';

    if (estado === 'confirmada') {
      span.classList.add('badge-success');
      span.textContent = 'Confirmada';
    } else if (estado === 'cancelada') {
      span.classList.add('badge-danger');
      span.textContent = 'Cancelada';
    } else {
      span.classList.add('badge-warning');
      span.textContent = 'Pendiente';
    }

    return span;
  },

  /**
   * Alterna el estado de carga de un botón (deshabilita y muestra spinner).
   * @param {HTMLButtonElement} button
   * @param {boolean} loading
   */
  setButtonLoading(button, loading) {
    button.disabled = loading;
    const spinner = button.querySelector('.spinner');
    if (spinner) {
      spinner.hidden = !loading;
    }
  },
};

// Cerrar feedback al hacer clic en la X
document.addEventListener('DOMContentLoaded', () => {
  const closeBtn = document.getElementById('feedback-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', () => ui.hideFeedback());
  }
});
