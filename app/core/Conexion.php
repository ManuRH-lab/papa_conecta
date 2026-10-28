<?php
/** Única conexión PDO a MySQL (patrón Singleton). Implementa IConexion. */
class Conexion implements IConexion
{
    private static ?PDO $pdo = null;

    public static function obtener(): PDO
    {
        if (self::$pdo === null) {
            $cfg = (require RAIZ . '/config/config.php')['bd'];
            $dsn = "mysql:host={$cfg['host']};port={$cfg['puerto']};dbname={$cfg['nombre']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $cfg['usuario'], $cfg['clave'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errores como excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                    // consultas preparadas reales
            ]);
        }
        return self::$pdo;
    }
}
