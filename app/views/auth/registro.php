<?php /** @var array $regiones @var array $errores */
$err = fn(string $c) => isset($errores[$c]) ? '<div class="error-campo">' . e($errores[$c]) . '</div>' : '';
$cls = fn(string $c) => isset($errores[$c]) ? 'campo-error' : '';
?>
<div class="contenedor" style="max-width:760px">
  <div class="card pad">
    <h2>Registrarme como agricultor</h2>
    <p class="muted small">Con tu cuenta podrás publicar tu cosecha. Tu región y comunidad aparecerán en cada producto para que el comprador sepa de dónde viene.</p>
    <?php if ($errores): ?><div class="aviso error" style="margin-top:12px">Revisa los datos marcados en rojo.</div><?php endif; ?>
    <form method="post" action="<?= url('registro') ?>" novalidate>
      <?= campoCsrf() ?>
      <div class="dos">
        <div class="<?= $cls('nombres') ?>"><label for="nombres">Nombres</label><input id="nombres" name="nombres" type="text" value="<?= viejo('nombres') ?>" required><?= $err('nombres') ?></div>
        <div class="<?= $cls('apellidos') ?>"><label for="apellidos">Apellidos</label><input id="apellidos" name="apellidos" type="text" value="<?= viejo('apellidos') ?>" required><?= $err('apellidos') ?></div>
        <div class="<?= $cls('dni') ?>"><label for="dni">DNI</label><input id="dni" name="dni" type="text" inputmode="numeric" maxlength="8" value="<?= viejo('dni') ?>" required><?= $err('dni') ?></div>
        <div class="<?= $cls('telefono') ?>"><label for="telefono">Celular</label><input id="telefono" name="telefono" type="tel" maxlength="9" value="<?= viejo('telefono') ?>" placeholder="9XXXXXXXX" required><?= $err('telefono') ?></div>
        <div class="<?= $cls('id_region') ?>"><label for="id_region">Región y provincia</label>
          <select id="id_region" name="id_region" required>
            <option value="">Elige…</option>
            <?php foreach ($regiones as $r): ?>
              <option value="<?= (int) $r['id_region'] ?>" <?= ($_POST['id_region'] ?? '') == $r['id_region'] ? 'selected' : '' ?>><?= e($r['nombre_region'] . ' · ' . $r['provincia']) ?></option>
            <?php endforeach; ?>
          </select><?= $err('id_region') ?></div>
        <div class="<?= $cls('comunidad') ?>"><label for="comunidad">Comunidad</label><input id="comunidad" name="comunidad" type="text" value="<?= viejo('comunidad') ?>" placeholder="Ej.: Comunidad campesina de Muñani" required><?= $err('comunidad') ?></div>
      </div>
      <div class="<?= $cls('correo') ?>"><label for="correo">Correo</label><input id="correo" name="correo" type="email" value="<?= viejo('correo') ?>" autocomplete="email" required><?= $err('correo') ?></div>
      <div class="dos">
        <div class="<?= $cls('clave') ?>"><label for="clave">Contraseña</label><input id="clave" name="clave" type="password" autocomplete="new-password" required>
          <div class="ayuda">Mínimo 8 caracteres, con letras y números.</div><?= $err('clave') ?></div>
        <div class="<?= $cls('clave2') ?>"><label for="clave2">Repite la contraseña</label><input id="clave2" name="clave2" type="password" autocomplete="new-password" required><?= $err('clave2') ?></div>
      </div>
      <button class="btn pri ancho" style="margin-top:18px">Crear cuenta</button>
    </form>
    <p class="small" style="margin-top:14px">¿Ya tienes cuenta? <a href="<?= url('login') ?>"><b>Ingresa aquí</b></a></p>
  </div>
</div>
