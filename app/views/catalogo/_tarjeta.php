<?php /** Tarjeta de producto del catálogo. @var array $p */ $agotado = ($p['estado'] ?? 'activo') === 'agotado'; ?>
<article class="card producto">
  <a class="foto" href="<?= url('producto', ['id' => $p['id_producto']]) ?>">
    <img src="<?= e(imagenProducto($p['imagen'])) ?>" alt="Papa <?= e($p['variedad']) ?>" loading="lazy">
    <span class="chip">📍 <?= e($p['region']) ?> · <?= e($p['provincia']) ?></span>
  </a>
  <div class="cuerpo">
    <div class="fila">
      <h3><a href="<?= url('producto', ['id' => $p['id_producto']]) ?>" style="color:inherit">Papa <?= e($p['variedad']) ?></a></h3>
      <span class="precio"><?= soles($p['precio_kg']) ?><small> /kg</small></span>
    </div>
    <div class="muted small"><?= e($p['agricultor']) ?> ·
      <?php if ($agotado): ?><span class="chip gris">Agotado</span>
      <?php else: ?><b style="color:var(--verde)"><?= kg($p['cantidad_kg']) ?></b> disponibles<?php endif; ?>
    </div>
    <a class="small" href="<?= url('producto', ['id' => $p['id_producto']]) ?>#origen">¿De dónde viene? →</a>
    <?php if (!$agotado && !Sesion::usuario()): ?>
      <form class="agregar" method="post" action="<?= url('carrito/agregar') ?>">
        <?= campoCsrf() ?>
        <input type="hidden" name="id_producto" value="<?= (int) $p['id_producto'] ?>">
        <input type="number" name="kg" value="5" min="1" max="<?= e($p['cantidad_kg']) ?>" step="1" aria-label="Kilos" required>
        <button class="btn sm pri">Agregar al carrito</button>
      </form>
    <?php endif; ?>
  </div>
</article>
