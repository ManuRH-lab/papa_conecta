<?php /** @var array $variedades @var array $perfil @var array $errores */
$err = fn(string $c) => isset($errores[$c]) ? '<div class="error-campo">' . e($errores[$c]) . '</div>' : '';
$cls = fn(string $c) => isset($errores[$c]) ? 'campo-error' : '';
?>
<div class="contenedor" style="max-width:820px">
  <div class="card pad">
    <h2>Publicar mi producto</h2>
    <p class="muted small">Completa los datos de tu cosecha. Tu región y comunidad se toman de tu perfil.</p>
    <?php if ($errores): ?><div class="aviso error" style="margin-top:12px">Revisa los datos marcados en rojo.</div><?php endif; ?>
    <form method="post" action="<?= url('agricultor/publicar') ?>" enctype="multipart/form-data" novalidate>
      <?= campoCsrf() ?>
      <div class="<?= $cls('id_variedad') ?>"><label for="id_variedad">Variedad de papa</label>
        <select id="id_variedad" name="id_variedad" required>
          <option value="">Elige la variedad…</option>
          <?php foreach ($variedades as $v): ?>
            <option value="<?= (int) $v['id_variedad'] ?>" <?= ($_POST['id_variedad'] ?? '') == $v['id_variedad'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option>
          <?php endforeach; ?>
        </select><?= $err('id_variedad') ?></div>
      <div class="dos">
        <div class="<?= $cls('cantidad_kg') ?>"><label for="cantidad_kg">Cantidad disponible (kg)</label><input id="cantidad_kg" name="cantidad_kg" type="number" min="1" step="0.5" value="<?= viejo('cantidad_kg') ?>" required><?= $err('cantidad_kg') ?></div>
        <div class="<?= $cls('precio_kg') ?>"><label for="precio_kg">Precio por kilo (S/)</label><input id="precio_kg" name="precio_kg" type="number" min="0.10" step="0.10" value="<?= viejo('precio_kg') ?>" required><?= $err('precio_kg') ?></div>
        <div><label>Región de origen</label><input type="text" value="<?= e($perfil['region'] . ' · ' . $perfil['provincia']) ?> 🔒" readonly></div>
        <div><label>Comunidad</label><input type="text" value="<?= e($perfil['comunidad']) ?> 🔒" readonly></div>
      </div>
      <div class="<?= $cls('foto') ?>"><label for="foto">Foto del producto (opcional)</label>
        <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
        <div class="ayuda">JPG, PNG o WEBP · máximo 2 MB. Una buena foto ayuda a que te compren.</div><?= $err('foto') ?></div>
      <button class="btn pri ancho" style="margin-top:18px;padding:14px">Publicar producto</button>
      <div class="aviso info" style="margin-top:12px">ℹ️ Tu publicación quedará en estado <b>«pendiente»</b> hasta que el administrador la revise.</div>
    </form>
  </div>
</div>
