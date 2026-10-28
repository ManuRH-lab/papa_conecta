<?php
/**
 * Papa Conecta — punto de entrada único (public/index.php).
 * Solo la carpeta public/ queda expuesta al navegador; app/, config/ y database/ están fuera.
 * Arquitectura MVC: el Router elige el controlador, que usa los modelos y muestra una vista.
 */
declare(strict_types=1);

define('RAIZ', dirname(__DIR__));      // carpeta del proyecto
define('PUBLICO', __DIR__);            // carpeta pública (assets y uploads)

$config = require RAIZ . '/config/config.php';
date_default_timezone_set($config['app']['zona_horaria']);
ini_set('display_errors', $config['app']['mostrar_errores'] ? '1' : '0');

// Carga automática de clases desde app/core, app/interfaces, app/models y app/controllers
spl_autoload_register(function (string $clase): void {
    foreach (['core', 'interfaces', 'models', 'controllers'] as $carpeta) {
        $archivo = RAIZ . "/app/$carpeta/$clase.php";
        if (is_file($archivo)) {
            require $archivo;
            return;
        }
    }
});
require RAIZ . '/app/core/funciones.php';

Sesion::iniciar();

// Tabla de rutas: ruta => [controlador, acción]
$router = new Router([
    ''                     => ['InicioController', 'index'],
    'catalogo'             => ['CatalogoController', 'index'],         // CU-04, CU-05
    'producto'             => ['CatalogoController', 'detalle'],       // CU-08
    'variedades'           => ['VariedadController', 'index'],         // CU-09
    'variedad'             => ['VariedadController', 'detalle'],
    'carrito'              => ['CarritoController', 'ver'],            // CU-06
    'carrito/agregar'      => ['CarritoController', 'agregar'],
    'carrito/actualizar'   => ['CarritoController', 'actualizar'],
    'carrito/quitar'       => ['CarritoController', 'quitar'],
    'pedido/confirmar'     => ['PedidoController', 'confirmar'],       // CU-07
    'pedido/resumen'       => ['PedidoController', 'resumen'],
    'login'                => ['AuthController', 'login'],
    'logout'               => ['AuthController', 'logout'],
    'registro'             => ['AuthController', 'registro'],          // CU-01
    'agricultor/productos' => ['AgricultorController', 'productos'],   // CU-03
    'agricultor/publicar'  => ['AgricultorController', 'publicar'],    // CU-02
    'agricultor/editar'    => ['AgricultorController', 'editar'],
    'agricultor/baja'      => ['AgricultorController', 'baja'],
    'agricultor/reenviar'  => ['AgricultorController', 'reenviar'],
    'admin/moderar'        => ['AdminController', 'moderar'],          // CU-10
    'admin/revisar'        => ['AdminController', 'revisar'],
    'admin/decidir'        => ['AdminController', 'decidir'],
]);
$router->despachar((string) ($_GET['r'] ?? ''));
