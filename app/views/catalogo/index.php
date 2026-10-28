<?php /** @var array $productos @var array $variedades @var array $regiones @var array $filtros @var array $avisos */ ?>
<div class="contenedor">
  <div class="cabecera-lista">
    <div>
      <h2>Catálogo de papas nativas</h2>
      <div class="muted small"><?= count($productos) ?> producto<?= count($productos) === 1 ? '' : 's' ?> encontrado<?= count($productos) === 1 ? '' : 's' ?></div>
    </div>
    <form class="buscador" method="get" action="index.php">
      <input type="hidden" name="r" value="catalogo">
      <input type="text" name="q" value="<?= e($filtros['buscar'] ?? '') ?>" placeholder="🔍 Buscar por variedad o región…" aria-label="Buscar">
      <button class="btn sm pri">Buscar</button>
    </form>
  </div>
  <?php foreach ($avisos as $a): ?><div class="aviso info"><?= e($a) ?></div><?php endforeach; ?>

  <div class="catalogo">
    <aside class="card filtros">
      <details open>
        <summary><h3 style="color:var(--verde);display:inline">Filtrar productos</h3></summary>
        <form class="contenido-filtros" method="get" action="index.php">
          <input type="hidden" name="r" value="catalogo"><input type="hidden" name="f" value="1">
          <label for="f-variedad">Variedad</label>
          <select id="f-variedad" name="variedad">
            <option value="">Todas</option>
            <?php foreach ($variedades as $v): ?>
              <option value="<?= (int) $v['id_variedad'] ?>" <?= ($filtros['id_variedad'] ?? 0) == $v['id_variedad'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <label for="f-region">Región</label>
          <select id="f-region" name="region">
            <option value="">Todas</option>
            <?php foreach ($regiones as $r): ?>
              <option <?= ($filtros['region'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
            <?php endforeach; ?>
          </select>
          <label>Precio por kg (S/)</label>
          <div class="dos" style="gap:8px">
            <input type="number" name="min" step="0.10" min="0" placeholder="Mín." value="<?= e($_GET['min'] ?? '') ?>" aria-label="Precio mínimo">
            <input type="number" name="max" step="0.10" min="0" placeholder="Máx." value="<?= e($_GET['max'] ?? '') ?>" aria-label="Precio máximo">
          </div>
          <label class="check"><input type="checkbox" name="disponibles" value="1" <?= $filtros['solo_disponibles'] ? 'checked' : '' ?>> Solo disponibles</label>
          <button class="btn pri ancho" style="margin-top:16px">Aplicar filtros</button>
          <a class="btn suave ancho" style="margin-top:8px" href="<?= url('catalogo') ?>">Limpiar filtros</a>
        </form>
      </details>
    </aside>

    <?php if ($productos): ?>
      <section class="grilla">
        <?php foreach ($productos as $p) { require __DIR__ . '/_tarjeta.php'; } ?>
      </section>
    <?php else: ?>
      <section class="card pad vacio" style="flex:1">
        <div style="font-size:2.4rem">🔎</div>
        <h3><?= $hayFiltros || !empty($filtros['buscar']) ? 'No hay productos con esos filtros' : 'No hay productos disponibles por el momento' ?></h3>
        <p class="muted" style="margin:6px 0 14px">Prueba con otra variedad, región o rango de precio.</p>
        <a class="btn out" href="<?= url('catalogo') ?>">Limpiar filtros</a>
      </section>
    <?php endif; ?>
  </div>
</div>
