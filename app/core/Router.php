<?php
/** Enrutador: relaciona cada ruta (index.php?r=...) con un controlador y una acción. */
class Router
{
    public function __construct(private array $rutas)
    {
    }

    public function despachar(string $ruta): void
    {
        $ruta = trim($ruta, '/');
        try {
            if (!isset($this->rutas[$ruta])) {
                http_response_code(404);
                (new Controller())->vista('layout/error', ['titulo' => 'Página no encontrada',
                    'mensaje' => 'La página que buscas no existe.']);
                return;
            }
            [$clase, $accion] = $this->rutas[$ruta];
            (new $clase())->$accion();
        } catch (PDOException $e) {
            error_log('[Papa Conecta] ' . $e->getMessage());     // el detalle técnico queda en el log, no en pantalla
            http_response_code(500);
            (new Controller())->vista('layout/error', ['titulo' => 'Error de base de datos',
                'mensaje' => 'No se pudo completar la operación. Verifica que MySQL esté iniciado en XAMPP '
                           . 'y que la base de datos papa_conecta exista (config/config.php).']);
        } catch (Throwable $e) {
            error_log('[Papa Conecta] ' . $e);
            http_response_code(500);
            (new Controller())->vista('layout/error', ['titulo' => 'Algo salió mal',
                'mensaje' => 'No se pudo completar la operación. Vuelve a intentarlo.']);
        }
    }
}
