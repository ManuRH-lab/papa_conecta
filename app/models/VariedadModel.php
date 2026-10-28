<?php
/** Variedades de papa (CU-09) y regiones para los formularios y filtros. */
class VariedadModel implements IVariedad
{
    private PDO $bd;

    public function __construct()
    {
        $this->bd = Conexion::obtener();
    }

    /** Variedades con la cantidad de productos activos de cada una. */
    public function listar(): array
    {
        return $this->bd->query(
            "SELECT v.*, COUNT(p.id_producto) AS productos_activos
               FROM variedad_papa v
               LEFT JOIN producto p ON p.id_variedad = v.id_variedad AND p.estado = 'activo'
              GROUP BY v.id_variedad
              ORDER BY v.nombre")->fetchAll();
    }

    public function obtener(int $idVariedad): ?array
    {
        $st = $this->bd->prepare('SELECT * FROM variedad_papa WHERE id_variedad = ?');
        $st->execute([$idVariedad]);
        return $st->fetch() ?: null;
    }

    /** Regiones con su provincia (registro del agricultor). */
    public function listarRegiones(): array
    {
        return $this->bd->query('SELECT * FROM region ORDER BY nombre_region, provincia')->fetchAll();
    }

    /** Nombres de región sin repetir (filtro del catálogo). */
    public function listarNombresRegion(): array
    {
        return $this->bd->query('SELECT DISTINCT nombre_region FROM region ORDER BY nombre_region')
                        ->fetchAll(PDO::FETCH_COLUMN);
    }
}
