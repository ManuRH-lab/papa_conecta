<?php /** @var array $items @var float $total @var array $errores */
$err = fn(string $c) => isset($errores[$c]) ? '<div class="error-campo">' . e($errores[$c]) . '</div>' : '';
$cls = fn(string $c) => isset($errores[$c]) ? 'campo-error' : '';
?>
<div class="contenedor">
  <?php if (!$items): ?>
    <div class="card pad vacio">
      <div style="font-size:2.6rem">🛒</div>
      <h2>Tu carrito está vacío</h2>
      <p class="muted" style="margin:8px 0 16px">Agrega papas desde el catálogo indicando los kilos que necesitas.</p>
      <a class="btn pri" href="<?= url('catalogo') ?>">Ver catálogo</a>
    </div>
  <?php else: ?>
  <?php if (!empty($errores['general'])): ?><div class="aviso error"><?= e($errores['general']) ?></div><?php endif; ?>
  <?php if (array_diff_key($errores, ['general' => 1])): ?><div class="aviso error">Revisa los datos del pedido marcados en rojo.</div><?php endif; ?>
  <form class="carrito" method="post" action="<?= url('pedido/confirmar') ?>" novalidate>
    <?= campoCsrf() ?>
    <?php /* Primer botón del formulario: al presionar Enter se confirma el pedido (y no se quita un producto) */ ?>
    <button type="submit" class="oculto" tabindex="-1" aria-hidden="true">Enviar</button>
    <section class="card pad">
      <h2>Mi carrito <span class="muted small">(<?= count($items) ?> producto<?= count($items) === 1 ? '' : 's' ?>)</span></h2>
      <?php foreach ($items as $i): ?>
        <div class="item" data-item data-precio="<?= e($i['precio_kg']) ?>" data-stock="<?= e($i['stock']) ?>">
          <div class="mini"><img src="<?= e(imagenProducto($i['imagen'])) ?>" alt=""></div>
          <div class="info">
            <b>Papa <?= e($i['variedad']) ?></b>
            <div class="muted small">📍 <?= e($i['provincia']) ?> · <?= e($i['region']) ?> · <?= soles($i['precio_kg']) ?> /kg</div>
            <?php if (!$i['disponible']): ?><div class="error-campo">Quedan <?= kg($i['stock']) ?>: ajusta la cantidad.</div><?php endif; ?>
          </div>
          <div class="cantidad">
            <button type="button" data-paso="-1" aria-label="Quitar 1 kg">−</button>
            <input type="text" inputmode="decimal" name="kg[<?= (int) $i['id_producto'] ?>]" value="<?= e(rtrim(rtrim(number_format($i['cantidad_kg'], 2, '.', ''), '0'), '.')) ?>" aria-label="Kilos de papa <?= e($i['variedad']) ?>">
            <button type="button" data-paso="1" aria-label="Agregar 1 kg">+</button>
          </div>
          <span class="subtotal"><?= soles($i['subtotal']) ?></span>
          <button class="quitar" name="id_producto" value="<?= (int) $i['id_producto'] ?>" formaction="<?= url('carrito/quitar') ?>" formnovalidate aria-label="Quitar papa <?= e($i['variedad']) ?>">✕</button>
          <input type="hidden" name="precio[<?= (int) $i['id_producto'] ?>]" value="<?= e($i['precio_kg']) ?>">
        </div>
      <?php endforeach; ?>
      <div class="total"><b style="font-size:1.05rem">Total a pagar</b><span class="precio" data-total><?= soles($total) ?></span></div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:10px;flex-wrap:wrap">
        <span class="muted small">El total se calcula automáticamente al cambiar las cantidades.</span>
        <button class="btn sm out" data-guardar formaction="<?= url('carrito/actualizar') ?>" formnovalidate>Actualizar carrito</button>
      </div>
    </section>

    <section class="card pad">
      <h2>Datos del pedido</h2>
      <p class="muted small">No necesitas crear una cuenta: te registramos con estos datos al confirmar.</p>
      <div class="dos">
        <div class="<?= $cls('nombres') ?>"><label for="nombres">Nombres</label><input id="nombres" name="nombres" type="text" value="<?= viejo('nombres') ?>" autocomplete="given-name" required><?= $err('nombres') ?></div>
        <div class="<?= $cls('apellidos') ?>"><label for="apellidos">Apellidos</label><input id="apellidos" name="apellidos" type="text" value="<?= viejo('apellidos') ?>" autocomplete="family-name" required><?= $err('apellidos') ?></div>
        <div class="<?= $cls('correo') ?>"><label for="correo">Correo</label><input id="correo" name="correo" type="email" value="<?= viejo('correo') ?>" autocomplete="email" required><?= $err('correo') ?></div>
        <div class="<?= $cls('telefono') ?>"><label for="telefono">Celular</label><input id="telefono" name="telefono" type="tel" value="<?= viejo('telefono') ?>" placeholder="9XXXXXXXX" autocomplete="tel" required><?= $err('telefono') ?></div>
      </div>
      <div class="<?= $cls('direccion') ?>"><label for="direccion">Dirección de entrega</label><input id="direccion" name="direccion" type="text" value="<?= viejo('direccion') ?>" autocomplete="street-address" required><?= $err('direccion') ?></div>
      <div class="<?= $cls('referencia') ?>"><label for="referencia">Referencia (opcional)</label><input id="referencia" name="referencia" type="text" value="<?= viejo('referencia') ?>"><?= $err('referencia') ?></div>
      <div class="carrito-fijo"><button class="btn sec ancho" style="margin-top:16px;padding:14px;font-size:1rem">Confirmar pedido · <span data-total><?= soles($total) ?></span></button></div>
      <p class="muted small" style="margin-top:8px">El pago y la entrega se coordinan con el equipo de Papa Conecta después de confirmar.</p>
    </section>
  </form>

  <section class="card pad origen" style="margin-top:22px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <h3>¿De dónde viene tu papa?</h3><span class="chip ok">✔ Origen registrado por el productor</span>
    </div>
    <table class="tabla" style="margin-top:8px">
      <thead><tr><th>Producto</th><th>Productor</th><th>Comunidad</th><th>Provincia · Región</th><th>Altitud</th></tr></thead>
      <tbody>
      <?php foreach ($items as $i): ?>
        <tr><td><b>Papa <?= e($i['variedad']) ?></b></td><td><?= e($i['agricultor']) ?></td><td><?= e($i['comunidad']) ?></td>
            <td><?= e($i['provincia']) ?> · <?= e($i['region']) ?></td><td><?= msnm($i['altitud_msnm']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <?php endif; ?>
</div>
