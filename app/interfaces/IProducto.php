<?php
/** Operaciones sobre los productos publicados (CU-02 a CU-05, CU-08, CU-10). */
interface IProducto
{
    public function listarActivos(array $filtros = []): array;
    public function obtener(int $idProducto): ?array;
    public function publicar(array $datos): int;
    public function actualizar(int $idProducto, float $cantidadKg, float $precioKg): void;
    public function cambiarEstado(int $idProducto, string $estado, ?string $observacion = null): void;
    public function obtenerTrazabilidad(int $idProducto): ?array;
}
