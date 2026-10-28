<?php
/**
 * Carrito de compras guardado en $_SESSION (CU-06). No se guarda en la base de datos
 * hasta que el comprador confirma el pedido (CU-07).
 * Estructura: $_SESSION['carrito'] = [id_producto => kilos]
 */
class CarritoSesion implements ICarrito
{
    private const CLAVE = 'carrito';
    private ?ProductoModel $productos = null;

    private function productos(): ProductoModel
    {
        return $this->productos ??= new ProductoModel();
    }

    private function &datos(): array
    {
        if (!isset($_SESSION[self::CLAVE]) || !is_array($_SESSION[self::CLAVE])) {
            $_SESSION[self::CLAVE] = [];
        }
        return $_SESSION[self::CLAVE];
    }

    /** Suma los kilos si el producto ya está en el carrito; valida stock y estado. */
    public function agregar(int $idProducto, float $kg): void
    {
        $carrito = &$this->datos();
        $this->validar($idProducto, ($carrito[$idProducto] ?? 0) + $kg, $kg);
        $carrito[$idProducto] = round(($carrito[$idProducto] ?? 0) + $kg, 2);
    }

    public function quitar(int $idProducto): void
    {
        $carrito = &$this->datos();
        unset($carrito[$idProducto]);
    }

    public function actualizarCantidad(int $idProducto, float $kg): void
    {
        if ($kg <= 0) {
            $this->quitar($idProducto);
            return;
        }
        $this->validar($idProducto, $kg, $kg);
        $carrito = &$this->datos();
        $carrito[$idProducto] = round($kg, 2);
    }

    /** Ítems con los datos vigentes del producto (precio, stock y origen). */
    public function obtenerItems(): array
    {
        $items = [];
        foreach ($this->datos() as $id => $kg) {
            $p = $this->productos()->obtener((int) $id);
            if (!$p) {
                $this->quitar((int) $id);
                continue;
            }
            $items[] = [
                'id_producto'  => (int) $id,
                'variedad'     => $p['variedad'],
                'cantidad_kg'  => (float) $kg,
                'precio_kg'    => (float) $p['precio_kg'],
                'subtotal'     => round($kg * $p['precio_kg'], 2),
                'stock'        => (float) $p['cantidad_kg'],
                'disponible'   => $p['estado'] === 'activo' && $kg <= $p['cantidad_kg'],
                'imagen'       => $p['imagen'],
                'agricultor'   => $p['agricultor'],
                'comunidad'    => $p['comunidad'],
                'region'       => $p['region'],
                'provincia'    => $p['provincia'],
                'altitud_msnm' => $p['altitud_msnm'],
            ];
        }
        return $items;
    }

    public function calcularTotal(): float
    {
        return round(array_sum(array_column($this->obtenerItems(), 'subtotal')), 2);
    }

    public function vaciar(): void
    {
        $_SESSION[self::CLAVE] = [];
    }

    /** Número de productos distintos (contador del encabezado). No consulta la base de datos. */
    public function contarItems(): int
    {
        return count($_SESSION[self::CLAVE] ?? []);
    }

    private function validar(int $idProducto, float $kgTotal, float $kgNuevo): void
    {
        if ($kgNuevo <= 0) {
            throw new DomainException('La cantidad debe ser mayor a 0 kg.');
        }
        $p = $this->productos()->obtener($idProducto);
        if (!$p || $p['estado'] !== 'activo') {
            throw new DomainException('Este producto no está disponible.');
        }
        if ($kgTotal > (float) $p['cantidad_kg']) {
            throw new DomainException("Solo hay " . kg($p['cantidad_kg']) . " disponibles de papa {$p['variedad']}.");
        }
    }
}
