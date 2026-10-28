<?php
/** Fichas de variedades y regiones (CU-09 y formularios). */
interface IVariedad
{
    public function listar(): array;
    public function obtener(int $idVariedad): ?array;
    public function listarRegiones(): array;
}
