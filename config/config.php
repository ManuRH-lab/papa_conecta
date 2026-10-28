<?php
/**
 * Configuración de Papa Conecta.
 * En XAMPP los valores por defecto funcionan sin cambios (usuario root sin contraseña).
 */
return [
    'bd' => [
        'host'    => 'localhost',
        'puerto'  => 3306,
        'nombre'  => 'papa_conecta',
        'usuario' => 'root',
        'clave'   => '',
    ],
    'app' => [
        'nombre'       => 'Papa Conecta',
        'foto_max_mb'  => 2,                 // tamaño máximo de la foto del producto
        'zona_horaria' => 'America/Lima',
        'mostrar_errores' => false,          // true solo durante el desarrollo
    ],
];
