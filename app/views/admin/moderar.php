<?php /** @var string $bandeja @var array $lista @var array $conteo @var ?array $seleccionado @var array $errores */
$tabs = ['pendientes' => 'Pendientes', 'activas' => 'Activas', 'rechazadas' => 'Rechazadas'];
?>
<div class="contenedor">
  <div class="moderacion">
    <div>
      <div class="card pad">
        <h2>Moderar publicaciones</h2>
        <p class="muted small">Revisa las publicaciones nuevas antes de que aparezcan en el catálogo.</p>
        <nav class="pestanas">
          <?php foreach ($tabs as $k => $t): ?>
            <a class="<?= $bandeja === $k ? 'activa' : '' ?>" href="<?= url('admin/moderar', ['bandeja' => $k]) ?>"><?= $t ?> (<?= (int) $conteo[$k] ?>)</a>
          <?php endforeach; ?>
        </nav>
        <?php if (!$lista): ?>
          <p class="muted vacio">No hay publicaciones en esta bandeja.</p>
        <?php else: ?>
        <table class="tabla">
          <thead><tr><th>Producto</th><th>Agricultor</th><th>Origen</th><th>Cantidad</th><th>Precio</th><th>Enviado</th></tr></thead>
          <tbody>
          <?php foreach ($lista as $p): $sel = $seleccionado && $seleccionado['id_producto'] == $p['id_producto']; ?>
            <tr class="<?= $sel ? 'marcada' : '' ?>">
              <td><a href="<?= url('admin/moderar', ['bandeja' => $bandeja, 'id' => $p['id_producto']]) ?>"><b>Papa <?= e($p['variedad']) ?></b></a>
                <?php if (!$p['imagen']): ?><div class="small" style="color:#8A6400;font-weight:700">⚠ Sin foto</div><?php endif; ?></td>
              <td><?= e($p['agricultor']) ?></td>
              <td><?= e($p['region']) ?> · <?= e($p['provincia']) ?></td>
              <td><?= kg($p['cantidad_kg']) ?></td>
              <td style="white-space:nowrap"><b style="color:var(--tierra)"><?= soles($p['precio_kg']) ?></b></td>
              <td class="muted small"><?= e(date('d/m/Y H:i', strtotime($p['fecha_publicacion']))) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <aside class="card pad">
      <?php if (!$seleccionado): ?>
        <p class="muted vacio">Elige una publicación de la lista para revisarla.</p>
      <?php else: $s = $seleccionado; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
          <h2>Revisar publicación</h2><?= chipEstado($s['estado'], $s['observacion']) ?>
        </div>
        <?php if ($s['imagen']): ?>
          <div style="height:150px;margin-top:12px;border-radius:12px;overflow:hidden"><img src="<?= e(imagenProducto($s['imagen'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover"></div>
        <?php else: ?>
          <div style="height:110px;margin-top:12px;border:2px dashed #E3C9A6;background:#FBF5EC;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#8A5A2B;font-weight:800">📷 El agricultor no adjuntó una foto</div>
        <?php endif; ?>
        <div class="datos-origen">
          <div class="dato"><span>Variedad</span><b><?= e($s['variedad']) ?></b></div>
          <div class="dato"><span>Cantidad</span><b><?= kg($s['cantidad_kg']) ?></b></div>
          <div class="dato"><span>Precio</span><b><?= soles($s['precio_kg']) ?> /kg</b></div>
          <div class="dato"><span>Comunidad</span><b><?= e($s['comunidad']) ?></b></div>
          <div class="dato"><span>Región</span><b><?= e($s['region']) ?> · <?= e($s['provincia']) ?></b></div>
          <div class="dato"><span>Altitud</span><b><?= msnm($s['altitud_msnm']) ?></b></div>
        </div>
        <p class="muted small" style="margin-top:8px">Agricultor: <b style="color:var(--txt)"><?= e($s['agricultor']) ?></b> · DNI <?= e($s['dni']) ?></p>
        <?php if ($s['observacion']): ?><div class="observacion"><b>Observación:</b> <?= e($s['observacion']) ?></div><?php endif; ?>

        <?php if (in_array($s['estado'], ['pendiente', 'activo', 'agotado'], true)): $pendiente = $s['estado'] === 'pendiente'; ?>
          <form method="post" action="<?= url('admin/decidir') ?>">
            <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $s['id_producto'] ?>">
            <div class="<?= isset($errores['observacion']) ? 'campo-error' : '' ?>">
              <label for="observacion">Observación <?= $pendiente ? '(obligatoria si se rechaza)' : '(obligatoria para retirar)' ?></label>
              <textarea id="observacion" name="observacion" maxlength="200" placeholder="Ej.: Agrega una foto de tu cosecha para que el comprador pueda verla."><?= e($_POST['observacion'] ?? '') ?></textarea>
              <?php if (isset($errores['observacion'])): ?><div class="error-campo"><?= e($errores['observacion']) ?></div><?php endif; ?>
            </div>
            <div class="dos" style="gap:10px;margin-top:14px">
              <?php if ($pendiente): ?>
                <button class="btn pri" name="accion" value="aprobar">✓ Aprobar y publicar</button>
                <button class="btn peligro" name="accion" value="rechazar">✕ Rechazar con observación</button>
              <?php else: ?>
                <button class="btn peligro" name="accion" value="retirar" style="grid-column:1/3">Retirar del catálogo</button>
              <?php endif; ?>
            </div>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </aside>
  </div>
</div>
