<?php /** @var array $pedido @var array $contacto datos escritos en este pedido (no los guardados) */ ?>
<div class="contenedor" style="max-width:860px">
  <div class="card pad">
    <div style="display:flex;gap:14px;align-items:center">
      <div style="font-size:2.4rem">✅</div>
      <div>
        <h1 style="font-size:1.7rem">¡Pedido registrado!</h1>
        <p class="muted">Número de pedido <b style="color:var(--tierra);font-size:1.15rem">N.° <?= str_pad((string) $pedido['id_pedido'], 5, '0', STR_PAD_LEFT) ?></b>
          · <?= e(date('d/m/Y H:i', strtotime($pedido['fecha_pedido']))) ?></p>
      </div>
    </div>
    <table class="tabla" style="margin-top:16px">
      <thead><tr><th>Producto</th><th>Origen</th><th>Cantidad</th><th>Precio</th><th style="text-align:right">Subtotal</th></tr></thead>
      <tbody>
      <?php foreach ($pedido['items'] as $i): ?>
        <tr><td><b>Papa <?= e($i['variedad']) ?></b><div class="muted small"><?= e($i['agricultor']) ?></div></td>
            <td><?= e($i['comunidad']) ?>, <?= e($i['provincia']) ?> · <?= e($i['region']) ?></td>
            <td><?= kg($i['cantidad_kg']) ?></td><td><?= soles($i['precio_unit']) ?> /kg</td>
            <td style="text-align:right"><?= soles($i['subtotal']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="total"><b>Total</b><span class="precio"><?= soles($pedido['total']) ?></span></div>
    <div class="datos-origen" style="margin-top:16px">
      <div class="dato"><span>Comprador</span><b><?= e($contacto['nombres'] . ' ' . $contacto['apellidos']) ?></b></div>
      <div class="dato"><span>Celular</span><b><?= e($contacto['telefono']) ?></b></div>
      <div class="dato"><span>Correo</span><b><?= e($contacto['correo']) ?></b></div>
      <div class="dato"><span>Entrega</span><b><?= e($pedido['direccion_entrega']) ?></b></div>
    </div>
    <p class="aviso info" style="margin-top:16px">Estado: <b>pendiente</b>. Te contactaremos por celular para coordinar el pago y la entrega.</p>
    <a class="btn pri" href="<?= url('catalogo') ?>">Seguir comprando</a>
  </div>
</div>
