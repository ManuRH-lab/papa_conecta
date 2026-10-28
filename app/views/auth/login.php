<?php /** @var ?string $error */ ?>
<div class="contenedor" style="max-width:480px">
  <div class="card pad">
    <h2>Ingresar</h2>
    <p class="muted small">Para agricultores y administradores. Para comprar no necesitas cuenta.</p>
    <?php if ($error): ?><div class="aviso error" style="margin-top:12px"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= url('login') ?>">
      <?= campoCsrf() ?>
      <label for="correo">Correo</label>
      <input id="correo" name="correo" type="email" value="<?= viejo('correo') ?>" autocomplete="username" required autofocus>
      <label for="clave">Contraseña</label>
      <input id="clave" name="clave" type="password" autocomplete="current-password" required>
      <button class="btn pri ancho" style="margin-top:18px">Ingresar</button>
    </form>
    <p class="small" style="margin-top:14px">¿Eres agricultor y aún no tienes cuenta? <a href="<?= url('registro') ?>"><b>Regístrate aquí</b></a></p>
  </div>
</div>
