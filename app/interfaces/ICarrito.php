<?php
/** Carrito de compras guardado en la sesión (CU-06, CU-07). */
interface ICarrito
{
    public function agregar(int $idProducto, float $kg): void;
    public function quitar(int $idProducto): void;
    public function actualizarCantidad(int $idProducto, float $kg): void;
    public function obtenerItems(): array;
    public function calcularTotal(): float;
    public function vaciar(): void;
}
