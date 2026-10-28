<?php
/** Inicio de sesión, cierre de sesión y CU-01 (registrarse como agricultor). */
class AuthController extends Controller
{
    public function login(): void
    {
        $error = null;
        if ($this->esPost()) {
            $this->verificarCsrf();
            $usuario = (new UsuarioModel())->iniciarSesion(
                mb_strtolower(trim((string) ($_POST['correo'] ?? ''))), (string) ($_POST['clave'] ?? ''));
            if ($usuario) {
                Sesion::flash('ok', 'Hola, ' . $usuario['nombres'] . '.');
                $usuario['tipo_usuario'] === 'administrador' ? $this->redirigir('admin/moderar')
                                                             : $this->redirigir('agricultor/productos');
            }
            $error = 'Correo o contraseña incorrectos.';     // mensaje genérico: no revela qué dato falló
        }
        $this->vista('auth/login', ['titulo' => 'Ingresar', 'error' => $error]);
    }

    public function logout(): void
    {
        if ($this->esPost()) {
            $this->verificarCsrf();
            (new UsuarioModel())->cerrarSesion();
            Sesion::flash('ok', 'Cerraste sesión.');
        }
        $this->redirigir('');
    }

    /** CU-01: registro del agricultor con su región y comunidad. */
    public function registro(): void
    {
        $variedades = new VariedadModel();
        $usuarios = new UsuarioModel();
        $errores = [];
        if ($this->esPost()) {
            $this->verificarCsrf();
            $d = [
                'nombres'   => trim((string) ($_POST['nombres'] ?? '')),
                'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
                'dni'       => trim((string) ($_POST['dni'] ?? '')),
                'telefono'  => preg_replace('/\s+/', '', (string) ($_POST['telefono'] ?? '')),
                'correo'    => mb_strtolower(trim((string) ($_POST['correo'] ?? ''))),
                'clave'     => (string) ($_POST['clave'] ?? ''),
                'clave2'    => (string) ($_POST['clave2'] ?? ''),
                'id_region' => $this->entero('id_region', $_POST),
                'comunidad' => trim((string) ($_POST['comunidad'] ?? '')),
            ];
            $errores = $this->validar($d, $variedades, $usuarios);
            if (!$errores) {
                $idAgricultor = $usuarios->registrarAgricultor($d);
                $usuarios->iniciarSesion($d['correo'], $d['clave']);
                Sesion::flash('ok', 'Tu cuenta fue creada. Ya puedes publicar tu cosecha.');
                $this->redirigir('agricultor/publicar');
            }
        }
        $this->vista('auth/registro', [
            'titulo'   => 'Registrarme como agricultor',
            'regiones' => $variedades->listarRegiones(),
            'errores'  => $errores,
        ]);
    }

    private function validar(array $d, VariedadModel $variedades, UsuarioModel $usuarios): array
    {
        $e = [];
        $nombre = '/^[\p{L} .\'-]{2,50}$/u';
        if (!preg_match($nombre, $d['nombres'])) {
            $e['nombres'] = 'Ingresa tus nombres (solo letras).';
        }
        if (!preg_match($nombre, $d['apellidos'])) {
            $e['apellidos'] = 'Ingresa tus apellidos (solo letras).';
        }
        if (!preg_match('/^\d{8}$/', $d['dni'])) {
            $e['dni'] = 'El DNI debe tener 8 dígitos.';
        } elseif ($usuarios->existeDni($d['dni'])) {
            $e['dni'] = 'Ese DNI ya está registrado. ¿Ya tienes cuenta? Inicia sesión.';
        }
        if (!preg_match('/^9\d{8}$/', $d['telefono'])) {
            $e['telefono'] = 'Ingresa un celular de 9 dígitos que empiece con 9.';
        }
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['correo']) > 80) {
            $e['correo'] = 'Ingresa un correo válido.';
        } elseif ($usuarios->existeCorreo($d['correo'])) {
            $e['correo'] = 'Ese correo ya está registrado. ¿Ya tienes cuenta? Inicia sesión.';
        }
        if (mb_strlen($d['clave']) < 8 || !preg_match('/[A-Za-z]/', $d['clave']) || !preg_match('/\d/', $d['clave'])) {
            $e['clave'] = 'La contraseña debe tener al menos 8 caracteres, con letras y números.';
        } elseif ($d['clave'] !== $d['clave2']) {
            $e['clave2'] = 'Las contraseñas no coinciden.';
        }
        $ids = array_column($variedades->listarRegiones(), 'id_region');
        if (!in_array($d['id_region'], array_map('intval', $ids), true)) {
            $e['id_region'] = 'Elige tu región y provincia.';
        }
        if (mb_strlen($d['comunidad']) < 3 || mb_strlen($d['comunidad']) > 80) {
            $e['comunidad'] = 'Ingresa el nombre de tu comunidad.';
        }
        return $e;
    }
}
