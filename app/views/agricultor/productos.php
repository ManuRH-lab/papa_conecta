<?php /** @var array $productos */ ?>
<div class="contenedor" style="max-width:980px">
  <div class="cabecera-lista">
    <div><h2>Mis productos</h2><div class="muted small">Revisa el estado de tus publicaciones y actualiza la cantidad o el precio.</div></div>
    <a class="btn pri" href="<?= url('agricultor/publicar') ?>">+ Publicar producto</a>
  </div>
  <div class="card pad mis-productos">
    <?php if (!$productos): ?>
      <div class="vacio"><div style="font-size:2.4rem">🌱</div><h3>Aún no publicaste productos</h3>
        <a class="btn pri" style="margin-top:12px" href="<?= url('agricultor/publicar') ?>">Publicar mi primera cosecha</a></div>
    <?php endif; ?>
    <?php foreach ($productos as $p): $rechazado = $p['estado'] === 'inactivo' && $p['observacion']; ?>
      <div class="producto-fila">
        <div class="item" style="border:0;padding:0;flex:1">
          <div class="mini"><img src="<?= e(imagenProducto($p['imagen'])) ?>" alt=""></div>
          <div class="info">
            <b>Papa <?= e($p['variedad']) ?></b> <?= chipEstado($p['estado'], $p['observacion']) ?>
            <div class="muted small"><?= kg($p['cantidad_kg']) ?> · <?= soles($p['precio_kg']) ?> /kg · publicado el <?= e(date('d/m/Y', strtotime($p['fecha_publicacion']))) ?></div>
            <?php if ($rechazado): ?><div class="observacion"><b>Observación del administrador:</b> <?= e($p['observacion']) ?></div><?php endif; ?>
            <?php if ($p['estado'] === 'pendiente'): ?><div class="ayuda">En revisión: aparecerá en el catálogo cuando el administrador la apruebe.</div><?php endif; ?>
          </div>
        </div>
        <div class="acciones" style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end">
          <a class="btn sm suave" href="<?= url('agricultor/editar', ['id' => $p['id_producto']]) ?>"><?= $rechazado ? 'Corregir' : 'Editar' ?></a>
          <?php if (in_array($p['estado'], ['activo', 'agotado', 'pendiente'], true)): ?>
            <form method="post" action="<?= url('agricultor/baja') ?>" data-confirmar="¿Dar de baja esta publicación? Dejará de verse en el catálogo.">
              <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
              <button class="btn sm out">Dar de baja</button></form>
          <?php elseif ($p['estado'] === 'inactivo'): ?>
            <form method="post" action="<?= url('agricultor/reenviar') ?>">
              <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
              <button class="btn sm pri">Reenviar a revisión</button></form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
