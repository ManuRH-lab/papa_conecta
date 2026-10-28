<?php
/** Controller base: mostrar vistas, redirigir y proteger las páginas. */
class Controller
{
    /** Muestra una vista dentro de la plantilla (cabecera + pie). */
    public function vista(string $nombre, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $mensajes = Sesion::mensajes();
        $usuario  = Sesion::usuario();
        $carritoCantidad = (new CarritoSesion())->contarItems();
        require RAIZ . '/app/views/layout/cabecera.php';
        require RAIZ . "/app/views/$nombre.php";
        require RAIZ . '/app/views/layout/pie.php';
    }

    protected function redirigir(string $ruta, array $params = []): never
    {
        header('Location: ' . url($ruta, $params));
        exit;
    }

    protected function esPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /** Todo formulario POST debe traer el token CSRF de la sesión. */
    protected function verificarCsrf(): void
    {
        $token = $_POST['csrf'] ?? null;
        if (!is_string($token) || !Sesion::csrfValido($token)) {
            http_response_code(400);
            $this->vista('layout/error', ['titulo' => 'Solicitud no válida',
                'mensaje' => 'El formulario expiró o no es válido. Vuelve a intentarlo.']);
            exit;
        }
    }

    /** Solo deja pasar al tipo de usuario indicado (precondición de los CU del agricultor y del administrador). */
    protected function exigirTipo(string $tipo): array
    {
        $u = Sesion::usuario();
        if (!$u) {
            Sesion::flash('info', 'Inicia sesión para continuar.');
            $this->redirigir('login');
        }
        if ($u['tipo'] !== $tipo) {
            http_response_code(403);
            $this->vista('layout/error', ['titulo' => 'Acceso restringido',
                'mensaje' => 'Esta sección no está disponible para tu tipo de cuenta.']);
            exit;
        }
        return $u;
    }

    protected function entero(string $clave, ?array $origen = null): int
    {
        $origen ??= $_GET;
        return filter_var($origen[$clave] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    }

    /** Número decimal con punto o coma; null si no es válido. */
    protected function decimal(mixed $valor): ?float
    {
        if (!is_scalar($valor)) {
            return null;
        }
        $v = str_replace(',', '.', trim((string) $valor));
        return is_numeric($v) ? round((float) $v, 2) : null;
    }
}
