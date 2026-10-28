<?php /** @var array $variedad @var array $productos */ ?>
<div class="contenedor">
  <p class="small" style="margin-bottom:12px"><a href="<?= url('variedades') ?>">← Todas las variedades</a></p>
  <div class="detalle">
    <div class="foto-grande"><img src="<?= e(imagenVariedad($variedad['nombre'])) ?>" alt="Papa <?= e($variedad['nombre']) ?>"></div>
    <div class="card pad">
      <h1 style="font-size:1.9rem">Papa <?= e($variedad['nombre']) ?></h1>
      <p style="margin:10px 0"><?= e($variedad['descripcion']) ?></p>
      <div class="datos-origen">
        <div class="dato"><span>Color de piel</span><b><?= e($variedad['color_piel']) ?></b></div>
        <div class="dato"><span>Temporada de cosecha</span><b><?= e($variedad['temporada']) ?></b></div>
      </div>
      <div class="origen" style="margin-top:14px"><h3>Beneficios</h3><p style="margin-top:4px"><?= e($variedad['beneficios']) ?></p></div>
      <a class="btn pri" style="margin-top:16px" href="<?= url('catalogo', ['f' => 1, 'variedad' => $variedad['id_variedad'], 'disponibles' => 1]) ?>">Ver productos de esta variedad (<?= count($productos) ?>)</a>
    </div>
  </div>
</div>
