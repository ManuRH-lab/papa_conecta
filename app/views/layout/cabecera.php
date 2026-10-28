<?php
/** @var string $titulo  @var array $mensajes  @var ?array $usuario  @var int $carritoCantidad */
$rutaActual = $_GET['r'] ?? '';
$activo = fn(string ...$rutas) => in_array($rutaActual, $rutas, true) ? 'activo' : '';
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Papa Conecta') ?> · Papa Conecta</title>
  <meta name="description" content="Compra papa nativa directamente a pequeños agricultores altoandinos y conoce su origen.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/estilos.css">
  <link rel="icon" href="assets/img/logo.svg">
</head>
<body>
<header class="cabecera">
  <div class="fila">
    <a class="logo" href="<?= url() ?>">
      <img src="assets/img/logo.svg" alt="" width="38" height="38">
      <div>Papa Conecta<small><?= Sesion::esTipo('administrador') ? 'PANEL DEL ADMINISTRADOR' : 'DEL CAMPO ANDINO A TU MESA' ?></small></div>
    </a>
    <button class="boton-menu" aria-label="Abrir menú" aria-expanded="false">☰</button>
    <nav class="menu" aria-label="Menú principal">
      <a class="<?= $activo('') ?>" href="<?= url() ?>">Inicio</a>
      <a class="<?= $activo('catalogo', 'producto') ?>" href="<?= url('catalogo') ?>">Catálogo</a>
      <a class="<?= $activo('variedades', 'variedad') ?>" href="<?= url('variedades') ?>">Variedades</a>
      <?php if (Sesion::esTipo('agricultor')): ?>
        <a class="<?= $activo('agricultor/productos', 'agricultor/editar') ?>" href="<?= url('agricultor/productos') ?>">Mis productos</a>
        <a class="<?= $activo('agricultor/publicar') ?>" href="<?= url('agricultor/publicar') ?>">Publicar</a>
      <?php elseif (Sesion::esTipo('administrador')): ?>
        <a class="<?= $activo('admin/moderar', 'admin/revisar') ?>" href="<?= url('admin/moderar') ?>">Moderar publicaciones</a>
      <?php endif; ?>
    </nav>
    <div class="derecha">
      <?php if ($usuario): ?>
        <span class="saludo">Hola, <b><?= e($usuario['nombres']) ?></b></span>
        <form method="post" action="<?= url('logout') ?>"><?= campoCsrf() ?><button class="btn sm suave">Salir</button></form>
      <?php else: ?>
        <a class="btn sm out" href="<?= url('login') ?>">Ingresar</a>
        <a class="btn sm carrito-btn" href="<?= url('carrito') ?>">🛒 Carrito <span class="insignia"><?= (int) $carritoCantidad ?></span></a>
      <?php endif; ?>
    </div>
  </div>
</header>
<?php if ($mensajes): ?>
  <div class="mensajes">
    <?php foreach ($mensajes as $m): ?>
      <div class="aviso <?= e($m['tipo']) ?>" role="status"><?= e($m['mensaje']) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<main>
