<?php /** @var array $variedades */ ?>
<div class="contenedor">
  <h2>Variedades de papa nativa</h2>
  <p class="muted" style="margin:4px 0 18px">El Perú tiene más de 4,000 variedades de papa nativa. Conoce las que ofrecen nuestros agricultores.</p>
  <?php if (!$variedades): ?><div class="card pad vacio">Aún no hay variedades registradas.</div><?php endif; ?>
  <div class="grilla-4">
    <?php foreach ($variedades as $v): ?>
      <a class="card producto" href="<?= url('variedad', ['id' => $v['id_variedad']]) ?>" style="color:inherit">
        <div class="foto"><img src="<?= e(imagenVariedad($v['nombre'])) ?>" alt="Papa <?= e($v['nombre']) ?>" loading="lazy"></div>
        <div class="cuerpo">
          <h3>Papa <?= e($v['nombre']) ?></h3>
          <span class="muted small">Piel <?= e(mb_strtolower($v['color_piel'])) ?> · <?= e($v['temporada']) ?></span>
          <span class="small" style="color:var(--verde);font-weight:800"><?= (int) $v['productos_activos'] ?> en venta</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
