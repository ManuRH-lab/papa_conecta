<?php
/**
 * Pedidos (CU-07). El pedido y su detalle se guardan en una sola transacción;
 * los triggers calculan subtotales y total y descuentan el stock (sección 3.5).
 */
class PedidoModel implements IPedido
{
    /** Mayor monto que admite la columna pedido.total, DECIMAL(10,2). */
    private const TOTAL_MAXIMO = 99999999.99;

    private PDO $bd;

    public function __construct()
    {
        $this->bd = Conexion::obtener();
    }

    /**
     * @param array $items lista de ['id_producto' => int, 'cantidad_kg' => float, 'precio_visto' => ?float]
     * @throws DomainException si algún producto ya no está disponible, no alcanza el stock o cambió de precio
     */
    public function registrar(int $idComprador, string $direccion, array $items): int
    {
        if (!$items) {
            throw new DomainException('El carrito está vacío.');
        }
        $this->bd->beginTransaction();
        try {
            // Bloquea las filas de los productos para que dos pedidos no vendan el mismo stock
            $consulta = $this->bd->prepare(
                'SELECT p.estado, p.cantidad_kg, p.precio_kg, v.nombre AS variedad
                   FROM producto p JOIN variedad_papa v ON v.id_variedad = p.id_variedad
                  WHERE p.id_producto = ? FOR UPDATE');
            $precios = [];
            $total = 0.0;
            foreach ($items as $it) {
                $consulta->execute([$it['id_producto']]);
                $p = $consulta->fetch();
                if (!$p || $p['estado'] !== 'activo') {
                    throw new DomainException('Un producto de tu carrito ya no está disponible. Quítalo para continuar.');
                }
                if ((float) $it['cantidad_kg'] > (float) $p['cantidad_kg']) {
                    throw new DomainException("De la papa {$p['variedad']} solo quedan " . kg($p['cantidad_kg'])
                        . '. Ajusta la cantidad para continuar.');
                }
                $visto = $it['precio_visto'] ?? null;
                if ($visto !== null && abs((float) $visto - (float) $p['precio_kg']) > 0.001) {
                    throw new DomainException("El precio de la papa {$p['variedad']} cambió a " . soles($p['precio_kg'])
                        . ' por kilo. Revisa el nuevo total y confirma otra vez.');
                }
                $precios[$it['id_producto']] = $p['precio_kg'];   // se cobra el precio vigente (el mismo que vio)
                $total += $it['cantidad_kg'] * $p['precio_kg'];
            }
            if ($total > self::TOTAL_MAXIMO) {
                throw new DomainException('El pedido supera el monto máximo de ' . soles(self::TOTAL_MAXIMO)
                    . '. Divídelo en varios pedidos.');
            }

            $this->bd->prepare('INSERT INTO pedido (id_comprador, direccion_entrega) VALUES (?, ?)')
                     ->execute([$idComprador, $direccion]);
            $idPedido = (int) $this->bd->lastInsertId();

            $detalle = $this->bd->prepare(
                'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad_kg, precio_unit) VALUES (?, ?, ?, ?)');
            foreach ($items as $it) {
                $detalle->execute([$idPedido, $it['id_producto'], $it['cantidad_kg'], $precios[$it['id_producto']]]);
            }
            $this->bd->commit();
            return $idPedido;
        } catch (Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    /** Pedido con sus líneas y los datos del comprador (resumen del CU-07). */
    public function obtener(int $idPedido): ?array
    {
        $st = $this->bd->prepare(
            'SELECT pe.*, u.nombres, u.apellidos, u.correo, u.telefono, c.referencia
               FROM pedido pe
               JOIN comprador c ON c.id_comprador = pe.id_comprador
               JOIN usuario u   ON u.id_usuario = c.id_usuario
              WHERE pe.id_pedido = ?');
        $st->execute([$idPedido]);
        $pedido = $st->fetch();
        if (!$pedido) {
            return null;
        }
        $st = $this->bd->prepare(
            'SELECT d.*, v.nombre AS variedad, CONCAT(u.nombres, \' \', u.apellidos) AS agricultor,
                    a.comunidad, r.nombre_region AS region, r.provincia
               FROM detalle_pedido d
               JOIN producto p      ON p.id_producto = d.id_producto
               JOIN variedad_papa v ON v.id_variedad = p.id_variedad
               JOIN agricultor a    ON a.id_agricultor = p.id_agricultor
               JOIN usuario u       ON u.id_usuario = a.id_usuario
               JOIN region r        ON r.id_region = a.id_region
              WHERE d.id_pedido = ?
              ORDER BY d.id_detalle');
        $st->execute([$idPedido]);
        $pedido['items'] = $st->fetchAll();
        return $pedido;
    }

    public function listarPorComprador(int $idComprador): array
    {
        $st = $this->bd->prepare('SELECT * FROM pedido WHERE id_comprador = ? ORDER BY fecha_pedido DESC');
        $st->execute([$idComprador]);
        return $st->fetchAll();
    }

    /** Cancela un pedido pendiente y devuelve el stock a los productos. */
    public function cancelar(int $idPedido): void
    {
        $this->bd->beginTransaction();
        try {
            $st = $this->bd->prepare("SELECT estado_pedido FROM pedido WHERE id_pedido = ? FOR UPDATE");
            $st->execute([$idPedido]);
            if ($st->fetchColumn() !== 'pendiente') {
                throw new DomainException('Solo se puede cancelar un pedido pendiente.');
            }
            $this->bd->prepare(
                "UPDATE producto p JOIN detalle_pedido d ON d.id_producto = p.id_producto
                    SET p.cantidad_kg = p.cantidad_kg + d.cantidad_kg,
                        p.estado = IF(p.estado = 'agotado', 'activo', p.estado)
                  WHERE d.id_pedido = ?")->execute([$idPedido]);
            $this->bd->prepare("UPDATE pedido SET estado_pedido = 'cancelado' WHERE id_pedido = ?")->execute([$idPedido]);
            $this->bd->commit();
        } catch (Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }
}
