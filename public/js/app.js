// Comportamiento mínimo: todo funciona con formularios normales y esto solo agrega comodidad.
(function () {
  'use strict';

  const fmt = (cents) => {
    const decimals = Number(document.body.dataset.decimals || 2);
    const sign = cents < 0 ? '-' : '';
    const amount = (Math.abs(cents) / 100).toLocaleString('en-US', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
    return sign + document.body.dataset.currency + ' ' + amount;
  };

  const toCents = (value) => Math.round((parseFloat(value) || 0) * 100);

  // Evita doble envío y pide confirmación en acciones delicadas.
  document.addEventListener('submit', (event) => {
    const form = event.target;

    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
      event.preventDefault();
      return;
    }

    const button = form.querySelector('[data-submit-once]');
    if (button) {
      setTimeout(() => { button.disabled = true; }, 0);
    }
  });

  // Filas repetibles: tallas de una prenda y líneas de una venta.
  document.querySelectorAll('[data-repeater]').forEach((repeater) => {
    const list = repeater.querySelector('[data-repeater-list]');
    const template = repeater.querySelector('template');
    let index = Number(repeater.dataset.next || list.children.length);

    repeater.querySelector('[data-repeater-add]').addEventListener('click', () => {
      list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__i__', index++));
      list.lastElementChild.querySelector('select, input:not([type=hidden])')?.focus();
      repeater.dispatchEvent(new Event('input', { bubbles: true }));
    });

    list.addEventListener('click', (event) => {
      const remove = event.target.closest('[data-repeater-remove]');
      if (remove) {
        remove.closest('[data-repeater-row]').remove();
        repeater.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
  });

  // Venta de mostrador: total en vivo y cobro sugerido.
  const saleForm = document.querySelector('[data-sale-form]');
  if (saleForm) {
    const output = saleForm.querySelector('[data-sale-total]');
    const amount = saleForm.querySelector('[data-sale-amount]');
    let touched = amount.value !== '';

    amount.addEventListener('input', () => { touched = true; });

    const refresh = () => {
      let total = 0;

      saleForm.querySelectorAll('[data-repeater-row]').forEach((row) => {
        const option = row.querySelector('select').selectedOptions[0];
        const quantityInput = row.querySelector('input[type=number]');
        const stock = Number(option?.dataset.stock || 0);

        if (stock) {
          quantityInput.max = stock;
        }

        total += Number(option?.dataset.price || 0) * (Number(quantityInput.value) || 0);
      });

      output.textContent = fmt(total);

      if (!touched) {
        amount.value = total ? (total / 100).toFixed(2) : '';
      }
    };

    saleForm.addEventListener('input', refresh);
    saleForm.addEventListener('change', refresh);
    refresh();
  }

  // Cierre de caja: efectivo esperado y diferencia en vivo.
  const cashForm = document.querySelector('[data-cash-form]');
  if (cashForm) {
    const base = Number(cashForm.dataset.base);
    const opening = cashForm.querySelector('[name=opening_amount]');
    const counted = cashForm.querySelector('[name=counted_cash]');
    const expectedOut = document.querySelector('[data-expected]');
    const differenceOut = cashForm.querySelector('[data-difference]');

    const refresh = () => {
      const expected = base + toCents(opening.value);
      expectedOut.textContent = fmt(expected);

      if (counted.value === '') {
        differenceOut.textContent = 'Escribe cuánto contaste para ver la diferencia.';
        return;
      }

      const difference = toCents(counted.value) - expected;
      differenceOut.textContent = difference === 0
        ? 'La caja cuadra exacto.'
        : (difference > 0 ? 'Sobrante de ' : 'Faltante de ') + fmt(Math.abs(difference));
    };

    cashForm.addEventListener('input', refresh);
    refresh();
  }

  // Entrega a domicilio: la dirección solo se pide cuando hace falta.
  document.querySelectorAll('[data-delivery]').forEach((group) => {
    const address = document.querySelector('[data-address]');
    const refresh = () => {
      const isDelivery = group.querySelector('select')?.value === 'envio';
      address.hidden = !isDelivery;
      address.querySelector('input').required = isDelivery;
    };
    group.addEventListener('change', refresh);
    refresh();
  });

  // Si una foto externa falla, queda el fondo neutro en lugar de un ícono roto.
  document.addEventListener('error', (event) => {
    if (event.target.tagName === 'IMG') {
      event.target.style.visibility = 'hidden';
    }
  }, true);
})();
