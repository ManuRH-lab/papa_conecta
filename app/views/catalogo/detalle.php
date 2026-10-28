<?php /** @var array $producto @var ?array $trazabilidad */ $agotado = $producto['estado'] === 'agotado'; ?>
<div class="contenedor">
  <p class="small" style="margin-bottom:12px"><a href="<?= url('catalogo') ?>">← Volver al catálogo</a></p>
  <div class="detalle">
    <div class="foto-grande"><img src="<?= e(imagenProducto($producto['imagen'])) ?>" alt="Papa <?= e($producto['variedad']) ?>"></div>
    <div class="card pad">
      <div class="fila" style="display:flex;justify-content:space-between;gap:12px;align-items:baseline">
        <h1 style="font-size:1.9rem">Papa <?= e($producto['variedad']) ?></h1>
        <span class="precio" style="font-size:1.6rem"><?= soles($producto['precio_kg']) ?><small> /kg</small></span>
      </div>
      <p class="muted" style="margin:6px 0 4px"><?= e($producto['desc_variedad']) ?></p>
      <p class="small"><b>Piel:</b> <?= e($producto['color_piel']) ?> · <b>Temporada:</b> <?= e($producto['temporada']) ?> ·
        <a href="<?= url('variedad', ['id' => $producto['id_variedad']]) ?>">Ficha de la variedad</a></p>
      <p style="margin:12px 0"><?= $agotado ? '<span class="chip gris">Agotado</span>' : '<b style="color:var(--verde)">' . kg($producto['cantidad_kg']) . '</b> disponibles' ?></p>

      <?php if (!$agotado && !Sesion::usuario()): ?>
        <form method="post" action="<?= url('carrito/agregar') ?>">
          <?= campoCsrf() ?>
          <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
          <input type="hidden" name="volver" value="producto">
          <label for="kg">Cantidad (kg)</label>
          <div style="display:flex;gap:10px">
            <input id="kg" type="number" name="kg" value="10" min="1" max="<?= e($producto['cantidad_kg']) ?>" step="1" style="width:120px" required>
            <button class="btn pri" style="flex:1">Agregar al carrito</button>
          </div>
        </form>
      <?php endif; ?>

      <section class="origen" id="origen" style="margin-top:18px">
        <h3>¿De dónde viene?</h3>
        <?php if ($trazabilidad): ?>
          <div class="datos-origen">
            <div class="dato"><span>Productor</span><b><?= e($trazabilidad['agricultor']) ?></b></div>
            <div class="dato"><span>Comunidad</span><b><?= e($trazabilidad['comunidad']) ?></b></div>
            <div class="dato"><span>Provincia</span><b><?= e($trazabilidad['provincia']) ?></b></div>
            <div class="dato"><span>Región</span><b><?= e($trazabilidad['region']) ?></b></div>
            <div class="dato"><span>Altitud</span><b><?= msnm($trazabilidad['altitud_msnm']) ?></b></div>
            <div class="dato"><span>Publicado</span><b><?= e(date('d/m/Y', strtotime($trazabilidad['fecha_publicacion']))) ?></b></div>
          </div>
        <?php else: ?>
          <p class="muted">Información de origen en verificación.</p>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>
