<?php
/**
 * Productos publicados por los agricultores.
 * Reglas: se publica en estado «pendiente» (CU-02); el administrador lo aprueba, rechaza o retira (CU-10);
 * al llegar a 0 kg pasa a «agotado» (CU-03, trigger trg_detalle_total_stock).
 */
class ProductoModel implements IProducto
{
    private PDO $bd;

    public function __construct()
    {
        $this->bd = Conexion::obtener();
    }

    /** CU-04 y CU-05: catálogo con filtros (variedad, región, precio, disponibilidad y búsqueda). */
    public function listarActivos(array $filtros = []): array
    {
        if ($filtros['solo_disponibles'] ?? true) {
            $base = 'SELECT c.*, \'activo\' AS estado FROM vw_catalogo c';      // vista del catálogo (3.5)
        } else {
            $base = 'SELECT p.id_producto, v.id_variedad, v.nombre AS variedad, p.precio_kg, p.cantidad_kg,
                            p.imagen, CONCAT(u.nombres, \' \', u.apellidos) AS agricultor, a.comunidad,
                            r.id_region, r.nombre_region AS region, r.provincia, r.altitud_msnm,
                            p.fecha_publicacion, p.estado
                       FROM producto p
                       JOIN variedad_papa v ON v.id_variedad = p.id_variedad
                       JOIN agricultor a    ON a.id_agricultor = p.id_agricultor
                       JOIN usuario u       ON u.id_usuario = a.id_usuario
                       JOIN region r        ON r.id_region = a.id_region
                      WHERE p.estado IN (\'activo\', \'agotado\')';
        }
        $sql = "SELECT * FROM ($base) cat WHERE 1 = 1";
        $p = [];
        if (!empty($filtros['id_variedad'])) {
            $sql .= ' AND cat.id_variedad = :variedad';
            $p['variedad'] = (int) $filtros['id_variedad'];
        }
        if (!empty($filtros['region'])) {
            $sql .= ' AND cat.region = :region';
            $p['region'] = $filtros['region'];
        }
        if (isset($filtros['precio_min'])) {
            $sql .= ' AND cat.precio_kg >= :pmin';
            $p['pmin'] = $filtros['precio_min'];
        }
        if (isset($filtros['precio_max'])) {
            $sql .= ' AND cat.precio_kg <= :pmax';
            $p['pmax'] = $filtros['precio_max'];
        }
        if (!empty($filtros['buscar'])) {
            $sql .= ' AND (cat.variedad LIKE :q1 OR cat.region LIKE :q2 OR cat.provincia LIKE :q3)';
            $p['q1'] = $p['q2'] = $p['q3'] = '%' . $filtros['buscar'] . '%';
        }
        $sql .= ' ORDER BY cat.estado = \'agotado\', cat.fecha_publicacion DESC, cat.id_producto';
        $st = $this->bd->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    /** Datos completos de un producto (para el detalle, la edición y la moderación). */
    public function obtener(int $idProducto): ?array
    {
        $st = $this->bd->prepare(
            'SELECT p.*, v.nombre AS variedad, v.color_piel, v.temporada, v.descripcion AS desc_variedad,
                    a.id_agricultor, a.comunidad, a.dni, u.nombres, u.apellidos,
                    CONCAT(u.nombres, \' \', u.apellidos) AS agricultor,
                    r.nombre_region AS region, r.provincia, r.altitud_msnm
               FROM producto p
               JOIN variedad_papa v ON v.id_variedad = p.id_variedad
               JOIN agricultor a    ON a.id_agricultor = p.id_agricultor
               JOIN usuario u       ON u.id_usuario = a.id_usuario
               JOIN region r        ON r.id_region = a.id_region
              WHERE p.id_producto = ?');
        $st->execute([$idProducto]);
        return $st->fetch() ?: null;
    }

    /** CU-02: registra la publicación en estado «pendiente». */
    public function publicar(array $datos): int
    {
        $st = $this->bd->prepare(
            'INSERT INTO producto (id_agricultor, id_variedad, cantidad_kg, precio_kg, imagen)
             VALUES (:agricultor, :variedad, :kg, :precio, :imagen)');
        $st->execute([
            'agricultor' => $datos['id_agricultor'],
            'variedad'   => $datos['id_variedad'],
            'kg'         => $datos['cantidad_kg'],
            'precio'     => $datos['precio_kg'],
            'imagen'     => $datos['imagen'] ?? null,
        ]);
        return (int) $this->bd->lastInsertId();
    }

    /**
     * CU-03: actualiza cantidad y precio. Activo con 0 kg => agotado; agotado con stock => activo.
     * $cantidadAnterior es la cantidad que el agricultor vio al abrir el formulario: si mientras editaba se
     * vendieron kilos, no se sobrescribe el stock (así no se vende dos veces lo mismo).
     * @throws DomainException si el stock cambió mientras se editaba
     */
    public function actualizar(int $idProducto, float $cantidadKg, float $precioKg, ?float $cantidadAnterior = null): void
    {
        $this->bd->beginTransaction();
        try {
            $st = $this->bd->prepare('SELECT cantidad_kg FROM producto WHERE id_producto = ? FOR UPDATE');
            $st->execute([$idProducto]);
            $actual = (float) $st->fetchColumn();
            if ($cantidadAnterior !== null && abs($actual - $cantidadAnterior) > 0.001) {
                throw new DomainException('Mientras editabas se vendieron kilos: ahora quedan ' . kg($actual)
                    . '. Revisa la cantidad y guarda otra vez.');
            }
            $this->guardarCambios($idProducto, $cantidadKg, $precioKg);
            $this->bd->commit();
        } catch (Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    private function guardarCambios(int $idProducto, float $cantidadKg, float $precioKg): void
    {
        $st = $this->bd->prepare(
            'UPDATE producto
                SET estado = CASE
                               WHEN estado = \'activo\'  AND :kg1 = 0 THEN \'agotado\'
                               WHEN estado = \'agotado\' AND :kg2 > 0 THEN \'activo\'
                               ELSE estado
                             END,
                    cantidad_kg = :kg3,
                    precio_kg   = :precio
              WHERE id_producto = :id');
        $st->execute(['kg1' => $cantidadKg, 'kg2' => $cantidadKg, 'kg3' => $cantidadKg,
                      'precio' => $precioKg, 'id' => $idProducto]);
    }

    public function cambiarImagen(int $idProducto, string $ruta): void
    {
        $this->bd->prepare('UPDATE producto SET imagen = ? WHERE id_producto = ?')->execute([$ruta, $idProducto]);
    }

    /** CU-03 (dar de baja), CU-10 (aprobar, rechazar, retirar) y reenvío a revisión. */
    public function cambiarEstado(int $idProducto, string $estado, ?string $observacion = null): void
    {
        $st = $this->bd->prepare('UPDATE producto SET estado = ?, observacion = ? WHERE id_producto = ?');
        $st->execute([$estado, $observacion, $idProducto]);
    }

    /** CU-08: origen del producto. Solo para productos visibles en el catálogo. */
    public function obtenerTrazabilidad(int $idProducto): ?array
    {
        $st = $this->bd->prepare(
            'SELECT CONCAT(u.nombres, \' \', u.apellidos) AS agricultor, a.comunidad,
                    r.nombre_region AS region, r.provincia, r.altitud_msnm, p.fecha_publicacion
               FROM producto p
               JOIN agricultor a ON a.id_agricultor = p.id_agricultor
               JOIN usuario u    ON u.id_usuario = a.id_usuario
               JOIN region r     ON r.id_region = a.id_region
              WHERE p.id_producto = ? AND p.estado IN (\'activo\', \'agotado\')');
        $st->execute([$idProducto]);
        return $st->fetch() ?: null;
    }

    /** «Mis productos» del agricultor (CU-03). */
    public function listarPorAgricultor(int $idAgricultor): array
    {
        $st = $this->bd->prepare(
            'SELECT p.*, v.nombre AS variedad
               FROM producto p JOIN variedad_papa v ON v.id_variedad = p.id_variedad
              WHERE p.id_agricultor = ?
              ORDER BY FIELD(p.estado, \'inactivo\', \'pendiente\', \'activo\', \'agotado\'), p.fecha_publicacion DESC');
        $st->execute([$idAgricultor]);
        return $st->fetchAll();
    }

    /** Bandeja del administrador (CU-10): pendientes, activas o rechazadas. */
    public function listarParaModeracion(string $bandeja): array
    {
        $condicion = match ($bandeja) {
            'activas'     => "p.estado IN ('activo', 'agotado')",
            'rechazadas'  => "p.estado = 'inactivo' AND p.observacion IS NOT NULL",
            default       => "p.estado = 'pendiente'",
        };
        $st = $this->bd->query(
            "SELECT p.id_producto, p.cantidad_kg, p.precio_kg, p.imagen, p.estado, p.observacion,
                    p.fecha_publicacion, v.nombre AS variedad,
                    CONCAT(u.nombres, ' ', u.apellidos) AS agricultor, r.nombre_region AS region, r.provincia
               FROM producto p
               JOIN variedad_papa v ON v.id_variedad = p.id_variedad
               JOIN agricultor a    ON a.id_agricultor = p.id_agricultor
               JOIN usuario u       ON u.id_usuario = a.id_usuario
               JOIN region r        ON r.id_region = a.id_region
              WHERE $condicion
              ORDER BY p.fecha_publicacion DESC, p.id_producto DESC");
        return $st->fetchAll();
    }

    /** Totales para las pestañas de la bandeja. */
    public function contarParaModeracion(): array
    {
        $f = $this->bd->query(
            "SELECT SUM(estado = 'pendiente') AS pendientes,
                    SUM(estado IN ('activo', 'agotado')) AS activas,
                    SUM(estado = 'inactivo' AND observacion IS NOT NULL) AS rechazadas
               FROM producto")->fetch();
        return array_map('intval', $f);
    }
}
