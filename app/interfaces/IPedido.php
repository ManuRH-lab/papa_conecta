<?php
/** Registro y consulta de pedidos (CU-07). */
interface IPedido
{
    public function registrar(int $idComprador, string $direccion, array $items): int;
    public function listarPorComprador(int $idComprador): array;
    public function cancelar(int $idPedido): void;
}
