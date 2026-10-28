<?php
/** CU-07: confirmar el pedido con los datos de entrega y mostrar el resumen. */
class PedidoController extends Controller
{
    public function confirmar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('carrito');
        }
        $this->verificarCsrf();
        $carrito = new CarritoSesion();
        // El carrito y los datos van en el mismo formulario: primero se guardan los kilos editados
        $erroresCarrito = [];
        foreach ((array) ($_POST['kg'] ?? []) as $id => $valor) {
            $kg = $this->decimal($valor);
            try {
                $kg === null ? throw new DomainException('Ingresa una cantidad válida en kilos.')
                             : $carrito->actualizarCantidad((int) $id, $kg);
            } catch (DomainException $e) {
                $erroresCarrito[] = $e->getMessage();
            }
        }
        $items = $carrito->obtenerItems();
        if (!$items) {
            Sesion::flash('info', 'Tu carrito está vacío.');
            $this->redirigir('catalogo');
        }

        $d = [
            'nombres'    => trim((string) ($_POST['nombres'] ?? '')),
            'apellidos'  => trim((string) ($_POST['apellidos'] ?? '')),
            'correo'     => mb_strtolower(trim((string) ($_POST['correo'] ?? ''))),
            'telefono'   => preg_replace('/\s+/', '', (string) ($_POST['telefono'] ?? '')),
            'direccion'  => trim((string) ($_POST['direccion'] ?? '')),
            'referencia' => trim((string) ($_POST['referencia'] ?? '')),
        ];
        $errores = $this->validar($d);
        if ($erroresCarrito) {
            $errores['general'] = implode(' ', $erroresCarrito);
        }

        if (!$errores) {
            try {
                $idComprador = (new UsuarioModel())->registrarComprador($d);
                $preciosVistos = (array) ($_POST['precio'] ?? []);   // precio por kilo que el comprador vio en el carrito
                $idPedido = (new PedidoModel())->registrar($idComprador, $d['direccion'],
                    array_map(fn($i) => ['id_producto' => $i['id_producto'], 'cantidad_kg' => $i['cantidad_kg'],
                                         'precio_visto' => $this->decimal($preciosVistos[$i['id_producto']] ?? '')], $items));
                $carrito->vaciar();
                // Solo quien hizo el pedido ve su resumen, con los datos que escribió en este pedido
                $_SESSION['ultimo_pedido'] = ['id' => $idPedido, 'contacto' => array_intersect_key($d,
                    array_flip(['nombres', 'apellidos', 'correo', 'telefono']))];
                $this->redirigir('pedido/resumen', ['id' => $idPedido]);
            } catch (DomainException $e) {
                $errores['general'] = $e->getMessage();      // CU-07, 7a: stock insuficiente
            }
        }

        $this->vista('carrito/ver', [
            'titulo'  => 'Mi carrito',
            'items'   => $carrito->obtenerItems(),
            'total'   => $carrito->calcularTotal(),
            'errores' => $errores,
        ]);
    }

    public function resumen(): void
    {
        $id = $this->entero('id');
        $ultimo = $_SESSION['ultimo_pedido'] ?? null;
        if (!$id || !is_array($ultimo) || $ultimo['id'] !== $id) {
            $this->redirigir('catalogo');
        }
        $this->vista('pedido/resumen', [
            'titulo'   => 'Pedido registrado',
            'pedido'   => (new PedidoModel())->obtener($id),
            'contacto' => $ultimo['contacto'],
        ]);
    }

    /** CU-07, 6a: campos obligatorios y formatos. */
    private function validar(array $d): array
    {
        $e = [];
        $nombre = '/^[\p{L} .\'-]{2,50}$/u';
        if (!preg_match($nombre, $d['nombres'])) {
            $e['nombres'] = 'Ingresa tus nombres (solo letras).';
        }
        if (!preg_match($nombre, $d['apellidos'])) {
            $e['apellidos'] = 'Ingresa tus apellidos (solo letras).';
        }
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['correo']) > 80) {
            $e['correo'] = 'Ingresa un correo válido.';
        }
        if (!preg_match('/^9\d{8}$/', $d['telefono'])) {
            $e['telefono'] = 'Ingresa un celular de 9 dígitos que empiece con 9.';
        }
        if (mb_strlen($d['direccion']) < 5 || mb_strlen($d['direccion']) > 150) {
            $e['direccion'] = 'Ingresa la dirección de entrega.';
        }
        if (mb_strlen($d['referencia']) > 150) {
            $e['referencia'] = 'La referencia es muy larga (máximo 150 caracteres).';
        }
        return $e;
    }
}
