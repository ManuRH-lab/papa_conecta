<?php
/** Módulo Publicación: CU-02 (publicar producto) y CU-03 (actualizar disponibilidad o precio). */
class AgricultorController extends Controller
{
    private ProductoModel $productos;
    private array $usuario;

    public function __construct()
    {
        $this->usuario   = $this->exigirTipo('agricultor');     // precondición: sesión de agricultor
        $this->productos = new ProductoModel();
    }

    /** «Mis productos»: estado, cantidad, precio y observaciones del administrador. */
    public function productos(): void
    {
        $this->vista('agricultor/productos', [
            'titulo'    => 'Mis productos',
            'productos' => $this->productos->listarPorAgricultor($this->usuario['id_agricultor']),
        ]);
    }

    /** CU-02: la región y la comunidad salen del perfil; el producto queda «pendiente». */
    public function publicar(): void
    {
        $variedades = new VariedadModel();
        $errores = [];
        if ($this->esPost()) {
            $this->verificarCsrf();
            $d = $this->leerFormulario($errores, true);
            if (!$errores) {
                try {
                    $d['imagen'] = SubidaFoto::guardar($_FILES['foto'] ?? null);
                    $d['id_agricultor'] = $this->usuario['id_agricultor'];
                    $this->productos->publicar($d);
                    Sesion::flash('ok', 'Tu publicación quedó pendiente de revisión. Te avisaremos en «Mis productos».');
                    $this->redirigir('agricultor/productos');
                } catch (DomainException $e) {
                    $errores['foto'] = $e->getMessage();
                }
            }
        }
        $this->vista('agricultor/publicar', [
            'titulo'     => 'Publicar mi producto',
            'variedades' => $variedades->listar(),
            'perfil'     => (new UsuarioModel())->perfilAgricultor($this->usuario['id_agricultor']),
            'errores'    => $errores,
        ]);
    }

    /** CU-03: editar cantidad, precio y foto. Si estaba rechazado, al guardar vuelve a revisión. */
    public function editar(): void
    {
        $producto = $this->propio($this->entero('id') ?: $this->entero('id', $_POST));
        $errores = [];
        if ($this->esPost()) {
            $this->verificarCsrf();
            $d = $this->leerFormulario($errores, false);
            $foto = null;
            if (!$errores) {
                try {
                    $foto = SubidaFoto::guardar($_FILES['foto'] ?? null);
                } catch (DomainException $e) {
                    $errores['foto'] = $e->getMessage();
                }
            }
            if (!$errores) {
                try {
                    $this->productos->actualizar($producto['id_producto'], $d['cantidad_kg'], $d['precio_kg'],
                                                 $this->decimal($_POST['cantidad_anterior'] ?? ''));
                } catch (DomainException $e) {                  // se vendieron kilos mientras editaba
                    if ($foto) {
                        unlink(PUBLICO . '/' . $foto);
                    }
                    $errores['cantidad_kg'] = $e->getMessage();
                    $producto = $this->productos->obtener($producto['id_producto']);   // muestra el stock actual
                    unset($_POST['cantidad_kg']);
                }
            }
            if (!$errores) {
                if ($foto) {
                    $this->productos->cambiarImagen($producto['id_producto'], $foto);
                }
                if ($producto['estado'] === 'inactivo' && $producto['observacion']) {
                    $this->productos->cambiarEstado($producto['id_producto'], 'pendiente');   // corrige y reenvía
                    Sesion::flash('ok', 'Guardaste los cambios y la publicación volvió a revisión.');
                } else {
                    Sesion::flash('ok', 'Los cambios se guardaron.');
                }
                $this->redirigir('agricultor/productos');
            }
        }
        $this->vista('agricultor/editar', ['titulo' => 'Editar producto', 'producto' => $producto, 'errores' => $errores]);
    }

    /** CU-03, 3a: dar de baja (estado «inactivo» sin observación). */
    public function baja(): void
    {
        if ($this->esPost()) {
            $this->verificarCsrf();
            $p = $this->propio($this->entero('id', $_POST));
            if (in_array($p['estado'], ['pendiente', 'activo', 'agotado'], true)) {
                $this->productos->cambiarEstado($p['id_producto'], 'inactivo');
                Sesion::flash('ok', 'La publicación de papa ' . $p['variedad'] . ' se dio de baja.');
            }
        }
        $this->redirigir('agricultor/productos');
    }

    /** Reenvía a revisión una publicación rechazada o dada de baja. */
    public function reenviar(): void
    {
        if ($this->esPost()) {
            $this->verificarCsrf();
            $p = $this->propio($this->entero('id', $_POST));
            if ($p['estado'] === 'inactivo') {
                $this->productos->cambiarEstado($p['id_producto'], 'pendiente');
                Sesion::flash('ok', 'La publicación volvió a revisión del administrador.');
            }
        }
        $this->redirigir('agricultor/productos');
    }

    /** El agricultor solo puede tocar sus propios productos. */
    private function propio(int $id): array
    {
        $p = $id ? $this->productos->obtener($id) : null;
        if (!$p || (int) $p['id_agricultor'] !== $this->usuario['id_agricultor']) {
            http_response_code(404);
            $this->vista('layout/error', ['titulo' => 'Producto no encontrado',
                'mensaje' => 'No encontramos ese producto entre tus publicaciones.']);
            exit;
        }
        return $p;
    }

    /** CU-02 paso 5 y CU-03 paso 5: cantidad y precio válidos. */
    private function leerFormulario(array &$errores, bool $nuevo): array
    {
        $d = [
            'id_variedad' => $this->entero('id_variedad', $_POST),
            'cantidad_kg' => $this->decimal($_POST['cantidad_kg'] ?? ''),
            'precio_kg'   => $this->decimal($_POST['precio_kg'] ?? ''),
        ];
        if ($nuevo && !(new VariedadModel())->obtener($d['id_variedad'])) {
            $errores['id_variedad'] = 'Elige la variedad de papa.';
        }
        $minimo = $nuevo ? 0.01 : 0;          // al publicar debe haber stock; al editar puede quedar en 0 (agotado)
        if ($d['cantidad_kg'] === null || $d['cantidad_kg'] < $minimo || $d['cantidad_kg'] > 99999) {
            $errores['cantidad_kg'] = $nuevo ? 'La cantidad debe ser mayor a 0 kg.' : 'La cantidad no puede ser negativa.';
        }
        if ($d['precio_kg'] === null || $d['precio_kg'] <= 0 || $d['precio_kg'] > 9999) {
            $errores['precio_kg'] = 'El precio por kilo debe ser mayor a S/ 0.00.';
        }
        return $d;
    }
}
