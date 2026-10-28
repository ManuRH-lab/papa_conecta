<?php
/** Funciones de ayuda para las vistas. */

/** Escapa texto para HTML (evita XSS). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Arma un enlace interno: url('producto', ['id' => 3]) => index.php?r=producto&id=3 */
function url(string $ruta = '', array $params = []): string
{
    $partes = [];
    if ($ruta !== '') {
        $partes[] = 'r=' . str_replace('%2F', '/', rawurlencode($ruta));   // index.php?r=pedido/resumen
    }
    if ($params) {
        $partes[] = http_build_query($params);
    }
    return 'index.php' . ($partes ? '?' . implode('&', $partes) : '');
}

function soles(float|string $monto): string
{
    return 'S/ ' . number_format((float) $monto, 2, '.', ',');
}

function kg(float|string $cantidad): string
{
    $n = (float) $cantidad;
    return (floor($n) == $n ? number_format($n, 0, '.', ',') : number_format($n, 2, '.', ',')) . ' kg';
}

function msnm(int|string $altitud): string
{
    return number_format((int) $altitud, 0, '.', ',') . ' m s. n. m.';
}

/** Convierte un texto en un nombre de archivo simple: "Amarilla Tumbay" => "amarilla-tumbay" */
function slug(string $texto): string
{
    $t = strtr(mb_strtolower($texto), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
}

/** Ruta pública de la foto del producto (las de prueba están en assets/, las subidas en uploads/). */
function imagenProducto(?string $ruta): string
{
    if (!$ruta) {
        return 'assets/img/sin-foto.svg';
    }
    return str_starts_with($ruta, 'uploads/') ? $ruta : 'assets/' . $ruta;
}

/** Imagen de una variedad (assets/img/variedades/<nombre>.jpg). */
function imagenVariedad(string $nombre): string
{
    $archivo = 'assets/img/variedades/' . slug($nombre) . '.jpg';
    return is_file(PUBLICO . '/' . $archivo) ? $archivo : 'assets/img/sin-foto.svg';
}

/** Valor anterior de un campo del formulario (para no perder lo escrito si hay errores). */
function viejo(string $campo, string $porDefecto = ''): string
{
    return e($_POST[$campo] ?? $porDefecto);
}

/** Campo oculto con el token CSRF para cada formulario POST. */
function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(Sesion::tokenCsrf()) . '">';
}

/** Etiqueta de color para el estado de un producto. */
function chipEstado(string $estado, ?string $observacion = null): string
{
    $mapa = [
        'pendiente' => ['pend', 'Pendiente'],
        'activo'    => ['ok', 'Activo'],
        'agotado'   => ['gris', 'Agotado'],
        'inactivo'  => [$observacion ? 'mal' : 'gris', $observacion ? 'Rechazado' : 'De baja'],
    ];
    [$clase, $texto] = $mapa[$estado] ?? ['gris', $estado];
    return '<span class="chip ' . $clase . '">' . e($texto) . '</span>';
}
