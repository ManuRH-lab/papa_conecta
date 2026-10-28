<?php
/** Usuarios: registro e inicio de sesión (CU-01, CU-07, CU-10). */
interface IUsuario
{
    public function registrarAgricultor(array $datos): int;
    public function registrarComprador(array $datos): int;
    public function iniciarSesion(string $correo, string $clave): ?array;
    public function cerrarSesion(): void;
    public function usuarioActual(): ?array;
}
