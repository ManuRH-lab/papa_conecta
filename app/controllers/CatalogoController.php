<?php
/** Módulo Catálogo: CU-04 (ver catálogo), CU-05 (filtrar) y CU-08 (trazabilidad). */
class CatalogoController extends Controller
{
    public function index(): void
    {
        $variedades = new VariedadModel();
        $filtros = [];
        $avisos  = [];

        // Si el formulario no se envió, por defecto se muestran solo los disponibles
        $enviado = isset($_GET['f']);
        $filtros['solo_disponibles'] = !$enviado || isset($_GET['disponibles']);
        if ($id = $this->entero('variedad')) {
            $filtros['id_variedad'] = $id;
        }
        $region = trim((string) ($_GET['region'] ?? ''));
        if ($region !== '' && in_array($region, $variedades->listarNombresRegion(), true)) {
            $filtros['region'] = $region;
        }
        $buscar = trim((string) ($_GET['q'] ?? ''));
        if ($buscar !== '') {
            $filtros['buscar'] = mb_substr($buscar, 0, 60);
        }
        $min = ($_GET['min'] ?? '') !== '' ? $this->decimal($_GET['min']) : null;
        $max = ($_GET['max'] ?? '') !== '' ? $this->decimal($_GET['max']) : null;
        if ($min !== null && $max !== null && $min > $max) {
            $avisos[] = 'El precio mínimo no puede ser mayor que el máximo. No se aplicó el filtro de precio.';  // CU-05, 1a
        } else {
            if ($min !== null && $min >= 0) {
                $filtros['precio_min'] = $min;
            }
            if ($max !== null && $max >= 0) {
                $filtros['precio_max'] = $max;
            }
        }

        $this->vista('catalogo/index', [
            'titulo'     => 'Catálogo de papas nativas',
            'productos'  => (new ProductoModel())->listarActivos($filtros),
            'variedades' => $variedades->listar(),
            'regiones'   => $variedades->listarNombresRegion(),
            'filtros'    => $filtros,
            'avisos'     => $avisos,
            'hayFiltros' => $enviado,
        ]);
    }

    /** Detalle del producto con la ficha «¿De dónde viene?» (CU-08). */
    public function detalle(): void
    {
        $modelo = new ProductoModel();
        $id = $this->entero('id');
        $producto = $id ? $modelo->obtener($id) : null;
        if (!$producto || !in_array($producto['estado'], ['activo', 'agotado'], true)) {
            http_response_code(404);
            $this->vista('layout/error', ['titulo' => 'Producto no disponible',
                'mensaje' => 'El producto que buscas no está publicado o ya no existe.']);
            return;
        }
        $this->vista('catalogo/detalle', [
            'titulo'        => 'Papa ' . $producto['variedad'],
            'producto'      => $producto,
            'trazabilidad'  => $modelo->obtenerTrazabilidad($id),
        ]);
    }
}
