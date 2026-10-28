<section class="hero">
  <div class="contenedor">
    <div style="flex:1.1">
      <span class="chip ok">🌱 Papa nativa de origen verificado</span>
      <h1>Del campo andino<br>directo a tu mesa</h1>
      <p>Compra papa nativa directamente a pequeños agricultores de Puno, Junín, Cusco y Huánuco, y conoce de dónde viene cada kilo.</p>
      <div class="acciones">
        <a class="btn pri" href="<?= url('catalogo') ?>">Ver catálogo de papas</a>
        <a class="btn out" href="<?= url(Sesion::esTipo('agricultor') ? 'agricultor/publicar' : 'registro') ?>">Soy agricultor: publicar mi cosecha</a>
      </div>
      <p class="muted small" style="margin-top:12px"><?= (int) $totalActivos ?> productos disponibles ahora</p>
    </div>
    <div class="foto"><img src="assets/img/portada.jpg" alt="Papas nativas de colores"></div>
  </div>
</section>
<div class="contenedor">
  <div class="beneficios">
    <div class="card beneficio"><div class="icono">🤝</div><div><h3 style="color:var(--tierra)">Compra directa</h3>
      <p class="muted small">Sin intermediarios: el agricultor fija su precio y recibe un pago justo.</p></div></div>
    <div class="card beneficio"><div class="icono">📍</div><div><h3 style="color:var(--tierra)">Trazabilidad</h3>
      <p class="muted small">Conoce la región, la comunidad y al productor de cada papa.</p></div></div>
    <div class="card beneficio"><div class="icono">🥔</div><div><h3 style="color:var(--tierra)">Variedades nativas</h3>
      <p class="muted small">Descubre papas poco comunes en los mercados de la ciudad.</p></div></div>
  </div>

  <div class="titulo-seccion"><h2>Variedades de temporada</h2><a href="<?= url('variedades') ?>"><b>Ver todas las variedades →</b></a></div>
  <div class="grilla-4">
    <?php foreach ($temporada as $v): ?>
      <a class="card producto" href="<?= url('variedad', ['id' => $v['id_variedad']]) ?>" style="color:inherit">
        <div class="foto" style="height:110px"><img src="<?= e(imagenVariedad($v['nombre'])) ?>" alt="Papa <?= e($v['nombre']) ?>" loading="lazy"></div>
        <div class="cuerpo"><b>Papa <?= e($v['nombre']) ?></b><span class="muted small"><?= e($v['temporada']) ?></span></div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($destacados): ?>
    <div class="titulo-seccion"><h2>Recién publicados</h2><a href="<?= url('catalogo') ?>"><b>Ir al catálogo →</b></a></div>
    <div class="grilla">
      <?php foreach ($destacados as $p) { require __DIR__ . '/catalogo/_tarjeta.php'; } ?>
    </div>
  <?php endif; ?>
</div>
