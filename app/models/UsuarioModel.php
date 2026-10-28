<?php
/**
 * Usuarios de la plataforma.
 * El agricultor y el administrador inician sesión con correo y contraseña (password_hash / password_verify).
 * El comprador se registra al confirmar su primer pedido, sin contraseña (CU-07).
 */
class UsuarioModel implements IUsuario
{
    private PDO $bd;

    public function __construct()
    {
        $this->bd = Conexion::obtener();
    }

    /** CU-01: crea el usuario de tipo agricultor y su perfil en una transacción. Devuelve id_agricultor. */
    public function registrarAgricultor(array $datos): int
    {
        $this->bd->beginTransaction();
        try {
            $st = $this->bd->prepare(
                "INSERT INTO usuario (nombres, apellidos, correo, telefono, tipo_usuario, contrasena)
                 VALUES (?, ?, ?, ?, 'agricultor', ?)");
            $st->execute([$datos['nombres'], $datos['apellidos'], $datos['correo'], $datos['telefono'],
                          password_hash($datos['clave'], PASSWORD_DEFAULT)]);
            $idUsuario = (int) $this->bd->lastInsertId();
            $st = $this->bd->prepare(
                'INSERT INTO agricultor (id_usuario, id_region, comunidad, dni, fecha_registro)
                 VALUES (?, ?, ?, ?, CURDATE())');
            $st->execute([$idUsuario, $datos['id_region'], $datos['comunidad'], $datos['dni']]);
            $idAgricultor = (int) $this->bd->lastInsertId();
            $this->bd->commit();
            return $idAgricultor;
        } catch (Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    /**
     * CU-07: devuelve el id_comprador del correo indicado; si no existe, lo crea sin contraseña.
     * Si el correo pertenece a un agricultor o al administrador, no se usa para comprar.
     */
    public function registrarComprador(array $datos): int
    {
        $st = $this->bd->prepare(
            'SELECT u.id_usuario, u.tipo_usuario, c.id_comprador
               FROM usuario u LEFT JOIN comprador c ON c.id_usuario = u.id_usuario
              WHERE u.correo = ?');
        $st->execute([$datos['correo']]);
        $existente = $st->fetch();

        if ($existente) {
            if ($existente['tipo_usuario'] !== 'comprador') {
                throw new DomainException('Ese correo pertenece a una cuenta de agricultor o administrador. Usa otro correo para comprar.');
            }
            // No se sobrescriben los datos guardados: como el comprador no tiene contraseña, cualquiera podría
            // escribir su correo. La dirección de este pedido queda en pedido.direccion_entrega.
            return (int) $existente['id_comprador'];
        }

        $this->bd->beginTransaction();
        try {
            $st = $this->bd->prepare(
                "INSERT INTO usuario (nombres, apellidos, correo, telefono, tipo_usuario, contrasena)
                 VALUES (?, ?, ?, ?, 'comprador', NULL)");
            $st->execute([$datos['nombres'], $datos['apellidos'], $datos['correo'], $datos['telefono']]);
            $idUsuario = (int) $this->bd->lastInsertId();
            $this->bd->prepare('INSERT INTO comprador (id_usuario, direccion_entrega, referencia) VALUES (?, ?, ?)')
                     ->execute([$idUsuario, $datos['direccion'], $datos['referencia'] ?: null]);
            $idComprador = (int) $this->bd->lastInsertId();
            $this->bd->commit();
            return $idComprador;
        } catch (Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    /** Inicio de sesión (precondición de CU-02, CU-03 y CU-10). Devuelve el usuario o null. */
    public function iniciarSesion(string $correo, string $clave): ?array
    {
        $st = $this->bd->prepare(
            'SELECT u.id_usuario, u.nombres, u.tipo_usuario, u.contrasena, a.id_agricultor
               FROM usuario u LEFT JOIN agricultor a ON a.id_usuario = u.id_usuario
              WHERE u.correo = ?');
        $st->execute([$correo]);
        $u = $st->fetch();
        // Los compradores no tienen contraseña: no inician sesión
        if (!$u || $u['contrasena'] === null || !password_verify($clave, $u['contrasena'])) {
            return null;
        }
        Sesion::entrar($u);
        return $u;
    }

    public function cerrarSesion(): void
    {
        Sesion::salir();
    }

    public function usuarioActual(): ?array
    {
        return Sesion::usuario();
    }

    public function existeCorreo(string $correo): bool
    {
        $st = $this->bd->prepare('SELECT 1 FROM usuario WHERE correo = ?');
        $st->execute([$correo]);
        return (bool) $st->fetchColumn();
    }

    public function existeDni(string $dni): bool
    {
        $st = $this->bd->prepare('SELECT 1 FROM agricultor WHERE dni = ?');
        $st->execute([$dni]);
        return (bool) $st->fetchColumn();
    }

    /** Perfil del agricultor: región y comunidad que se precargan al publicar. */
    public function perfilAgricultor(int $idAgricultor): ?array
    {
        $st = $this->bd->prepare(
            'SELECT a.*, r.nombre_region AS region, r.provincia, r.altitud_msnm, u.nombres, u.apellidos
               FROM agricultor a
               JOIN region r  ON r.id_region = a.id_region
               JOIN usuario u ON u.id_usuario = a.id_usuario
              WHERE a.id_agricultor = ?');
        $st->execute([$idAgricultor]);
        return $st->fetch() ?: null;
    }
}
