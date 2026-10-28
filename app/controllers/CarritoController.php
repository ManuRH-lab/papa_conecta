<?php
/** Módulo Carrito y Pedido: CU-06 (agregar al carrito) y vista del carrito con el formulario del CU-07. */
class CarritoController extends Controller
{
    public function ver(): void
    {
        $carrito = new CarritoSesion();
        $this->vista('carrito/ver', [
            'titulo'  => 'Mi carrito',
            'items'   => $carrito->obtenerItems(),
            'total'   => $carrito->calcularTotal(),
            'errores' => [],
        ]);
    }

    public function agregar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('catalogo');
        }
        $this->verificarCsrf();
        $id = $this->entero('id_producto', $_POST);
        $kg = $this->decimal($_POST['kg'] ?? '') ?? 0;
        try {
            (new CarritoSesion())->agregar($id, $kg);
            Sesion::flash('ok', 'Agregaste ' . kg($kg) . ' al carrito.');
        } catch (DomainException $e) {
            Sesion::flash('error', $e->getMessage());       // CU-06, 2a: supera el stock
        }
        $volver = $_POST['volver'] ?? 'catalogo';
        $volver === 'producto' ? $this->redirigir('producto', ['id' => $id]) : $this->redirigir('catalogo');
    }

    /** Cambia los kilos de cada ítem; el total se recalcula (RF-06). */
    public function actualizar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('carrito');
        }
        $this->verificarCsrf();
        $carrito = new CarritoSesion();
        foreach ((array) ($_POST['kg'] ?? []) as $id => $valor) {
            $kg = $this->decimal($valor);
            if ($kg === null) {
                Sesion::flash('error', 'Ingresa una cantidad válida en kilos.');
                continue;
            }
            try {
                $carrito->actualizarCantidad((int) $id, $kg);
            } catch (DomainException $e) {
                Sesion::flash('error', $e->getMessage());
            }
        }
        $this->redirigir('carrito');
    }

    public function quitar(): void
    {
        if ($this->esPost()) {
            $this->verificarCsrf();
            (new CarritoSesion())->quitar($this->entero('id_producto', $_POST));
            Sesion::flash('ok', 'Producto quitado del carrito.');
        }
        $this->redirigir('carrito');
    }
}
