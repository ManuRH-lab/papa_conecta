<?php
/** Manejo de la sesión: usuario autenticado, mensajes flash y token CSRF. */
class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']),
            ]);
            session_name('papaconecta');
            session_start();
        }
    }

    // ---------------------------------------------------------- usuario
    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);           // evita la fijación de sesión
        $_SESSION['usuario'] = [
            'id_usuario'    => (int) $usuario['id_usuario'],
            'nombres'       => $usuario['nombres'],
            'tipo'          => $usuario['tipo_usuario'],
            'id_agricultor' => isset($usuario['id_agricultor']) ? (int) $usuario['id_agricultor'] : null,
        ];
    }

    public static function salir(): void
    {
        unset($_SESSION['usuario']);
        session_regenerate_id(true);
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function esTipo(string $tipo): bool
    {
        return (self::usuario()['tipo'] ?? null) === $tipo;
    }

    // ---------------------------------------------------------- mensajes
    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    public static function mensajes(): array
    {
        $m = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $m;
    }

    // ---------------------------------------------------------- CSRF
    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfValido(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}
