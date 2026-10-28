<?php
/** Módulo Administración: CU-10 (moderar publicaciones). */
class AdminController extends Controller
{
    private ProductoModel $productos;

    public function __construct()
    {
        $this->exigirTipo('administrador');
        $this->productos = new ProductoModel();
    }

    /** Bandeja con pestañas y, si se elige una publicación, el panel «Revisar publicación». */
    public function moderar(array $errores = []): void
    {
        $bandeja = in_array($_GET['bandeja'] ?? '', ['pendientes', 'activas', 'rechazadas'], true) ? $_GET['bandeja'] : 'pendientes';
        $lista = $this->productos->listarParaModeracion($bandeja);
        $id = $this->entero('id') ?: $this->entero('id', $_POST);
        if (!$id && $bandeja === 'pendientes' && $lista) {
            $id = (int) $lista[0]['id_producto'];          // abre la primera pendiente
        }
        $this->vista('admin/moderar', [
            'titulo'        => 'Moderar publicaciones',
            'bandeja'       => $bandeja,
            'lista'         => $lista,
            'conteo'        => $this->productos->contarParaModeracion(),
            'seleccionado'  => $id ? $this->productos->obtener($id) : null,
            'errores'       => $errores,
        ]);
    }

    public function revisar(): void
    {
        $this->moderar();
    }

    /** Aprobar (pendiente → activo), rechazar (pendiente → inactivo con observación) o retirar (activo → inactivo). */
    public function decidir(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('admin/moderar');
        }
        $this->verificarCsrf();
        $p = $this->productos->obtener($this->entero('id', $_POST));
        $accion = $_POST['accion'] ?? '';
        $observacion = trim((string) ($_POST['observacion'] ?? ''));
        if (!$p) {
            $this->redirigir('admin/moderar');
        }
        $nombre = 'papa ' . $p['variedad'] . ' de ' . $p['agricultor'];

        if ($accion === 'aprobar' && $p['estado'] === 'pendiente') {
            $conStock = (float) $p['cantidad_kg'] > 0;          // sin kilos queda «agotado» hasta que haya stock
            $this->productos->cambiarEstado($p['id_producto'], $conStock ? 'activo' : 'agotado');
            Sesion::flash('ok', "Aprobaste la publicación de $nombre. "
                . ($conStock ? 'Ya aparece en el catálogo.' : 'Queda «agotado» porque no tiene kilos disponibles.'));
            $this->redirigir('admin/moderar');
        }
        if (in_array($accion, ['rechazar', 'retirar'], true)) {
            $valido = $accion === 'rechazar' ? $p['estado'] === 'pendiente' : in_array($p['estado'], ['activo', 'agotado'], true);
            if ($valido && (mb_strlen($observacion) < 5 || mb_strlen($observacion) > 200)) {
                $_GET['bandeja'] = $accion === 'retirar' ? 'activas' : 'pendientes';
                $_GET['id'] = $p['id_producto'];
                $this->moderar(['observacion' => 'Escribe una observación (5 a 200 caracteres) para que el agricultor sepa qué corregir.']);
                return;
            }
            if ($valido) {
                $this->productos->cambiarEstado($p['id_producto'], 'inactivo', $observacion);
                Sesion::flash('ok', ($accion === 'rechazar' ? 'Rechazaste' : 'Retiraste') . " la publicación de $nombre.");
                $this->redirigir('admin/moderar', $accion === 'retirar' ? ['bandeja' => 'activas'] : []);
            }
        }
        Sesion::flash('error', 'Esa acción no corresponde al estado actual de la publicación.');
        $this->redirigir('admin/moderar');
    }
}
