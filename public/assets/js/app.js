// Papa Conecta — interacción mínima (sin librerías)
document.addEventListener('DOMContentLoaded', () => {
  // En el celular los filtros del catálogo empiezan cerrados
  if (window.innerWidth <= 760) {
    document.querySelectorAll('.filtros details[open]').forEach((d) => d.removeAttribute('open'));
  }

  // Menú del celular
  const cab = document.querySelector('.cabecera');
  const boton = document.querySelector('.boton-menu');
  if (cab && boton) {
    boton.addEventListener('click', () => {
      cab.classList.toggle('abierta');
      const menu = cab.querySelector('.menu');
      if (menu) cab.style.setProperty('--alto-menu', menu.offsetHeight + 'px');
      boton.setAttribute('aria-expanded', cab.classList.contains('abierta'));
    });
  }

  // Carrito: botones − / + y total recalculado al instante (RF-06).
  // El servidor vuelve a calcular todo al guardar; esto solo ayuda a ver el total antes.
  const soles = (n) => 'S/ ' + n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const recalcular = () => {
    let total = 0;
    document.querySelectorAll('[data-item]').forEach((fila) => {
      const kg = parseFloat(fila.querySelector('input[name^="kg["]').value.replace(',', '.')) || 0;
      const sub = kg * parseFloat(fila.dataset.precio);
      fila.querySelector('.subtotal').textContent = soles(sub);
      total += sub;
    });
    document.querySelectorAll('[data-total]').forEach((el) => (el.textContent = soles(total)));
  };
  document.querySelectorAll('[data-item]').forEach((fila) => {
    const input = fila.querySelector('input[name^="kg["]');
    const max = parseFloat(fila.dataset.stock);
    fila.querySelectorAll('[data-paso]').forEach((b) =>
      b.addEventListener('click', () => {
        const v = (parseFloat(input.value.replace(',', '.')) || 0) + parseFloat(b.dataset.paso);
        input.value = Math.max(1, Math.min(max, v));
        recalcular();
        document.querySelector('[data-guardar]')?.classList.add('resaltado');
      }));
    input.addEventListener('input', recalcular);
    // Enter en los kilos guarda el carrito (no confirma el pedido ni quita productos)
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); document.querySelector('[data-guardar]')?.click(); }
    });
  });

  // Confirmación antes de acciones que no se deshacen fácilmente
  document.querySelectorAll('[data-confirmar]').forEach((f) =>
    f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirmar)) e.preventDefault(); }));
});
