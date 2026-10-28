<?php /** @var array $producto @var array $errores */
$err = fn(string $c) => isset($errores[$c]) ? '<div class="error-campo">' . e($errores[$c]) . '</div>' : '';
$cls = fn(string $c) => isset($errores[$c]) ? 'campo-error' : '';
$rechazado = $producto['estado'] === 'inactivo' && $producto['observacion'];
?>
<div class="contenedor" style="max-width:820px">
  <p class="small" style="margin-bottom:12px"><a href="<?= url('agricultor/productos') ?>">← Mis productos</a></p>
  <div class="card pad">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
      <h2>Editar papa <?= e($producto['variedad']) ?></h2><?= chipEstado($producto['estado'], $producto['observacion']) ?>
    </div>
    <?php if ($rechazado): ?><div class="observacion" style="margin-top:10px"><b>Observación del administrador:</b> <?= e($producto['observacion']) ?></div><?php endif; ?>
    <form method="post" action="<?= url('agricultor/editar', ['id' => $producto['id_producto']]) ?>" enctype="multipart/form-data" novalidate>
      <?= campoCsrf() ?>
      <input type="hidden" name="cantidad_anterior" value="<?= e($producto['cantidad_kg']) ?>">
      <div class="dos">
        <div class="<?= $cls('cantidad_kg') ?>"><label for="cantidad_kg">Cantidad disponible (kg)</label><input id="cantidad_kg" name="cantidad_kg" type="number" min="0" step="0.5" value="<?= viejo('cantidad_kg', rtrim(rtrim($producto['cantidad_kg'], '0'), '.')) ?>" required>
          <div class="ayuda">Si pones 0, la publicación pasa a «agotado».</div><?= $err('cantidad_kg') ?></div>
        <div class="<?= $cls('precio_kg') ?>"><label for="precio_kg">Precio por kilo (S/)</label><input id="precio_kg" name="precio_kg" type="number" min="0.10" step="0.10" value="<?= viejo('precio_kg', $producto['precio_kg']) ?>" required><?= $err('precio_kg') ?></div>
      </div>
      <div class="<?= $cls('foto') ?>"><label for="foto">Cambiar foto (opcional)</label>
        <div style="display:flex;gap:14px;align-items:center">
          <div class="mini" style="width:110px;height:80px;border-radius:9px;overflow:hidden;flex:none;background:#EFE7D8"><img src="<?= e(imagenProducto($producto['imagen'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover"></div>
          <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
        </div><?= $err('foto') ?></div>
      <button class="btn pri ancho" style="margin-top:18px;padding:14px"><?= $rechazado ? 'Guardar y reenviar a revisión' : 'Guardar cambios' ?></button>
    </form>
  </div>
</div>
