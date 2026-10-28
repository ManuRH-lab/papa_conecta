<?php
/** Acceso a la base de datos (sección 3.3). */
interface IConexion
{
    public static function obtener(): PDO;
}
