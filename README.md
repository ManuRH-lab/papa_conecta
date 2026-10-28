# Papa Conecta

Plataforma web de comercio directo de papa nativa entre agricultores altoandinos y compradores.
Proyecto del curso Sistemas de Software (Curso Integrador I), UTP.

- **Tecnologías:** PHP 8.2 o superior (MVC sin framework), MySQL 8 / MariaDB 10.4 o superior, HTML5, CSS3 y JavaScript.
- **Acceso a datos:** PDO con consultas preparadas y transacciones.
- **Seguridad básica:** `password_hash()`, token CSRF, `htmlspecialchars()` y control de acceso por tipo de usuario.

## Instalación en XAMPP (Windows)

1. Instala XAMPP 8.2 o superior. Abre el **XAMPP Control Panel** e inicia **Apache** y **MySQL**.
2. Copia la carpeta `papa_conecta` en `C:\xampp\htdocs\`.
3. Abre `http://localhost/phpmyadmin`, entra a **Importar**, elige `papa_conecta/database/papa_conecta.sql` y presiona **Importar**.
   El script crea la base de datos `papa_conecta` con sus 8 tablas, los triggers, la vista y los datos de prueba.
   - Por consola también se puede: `C:\xampp\mysql\bin\mysql -u root < database\papa_conecta.sql`
4. Si tu MySQL tiene contraseña o usa otro puerto, cambia `config/config.php`.
5. Abre `http://localhost/papa_conecta/` (te lleva a `http://localhost/papa_conecta/public/`).

### Atajos para Windows

En la carpeta del proyecto hay tres archivos que hacen los pasos por ti (doble clic):

| Archivo | Qué hace |
|---|---|
| `instalar_en_xampp.bat` | Copia el proyecto a `C:\xampp\htdocs\papa_conecta`, crea la base de datos con los datos de prueba y abre la web. Antes, inicia Apache y MySQL en XAMPP. |
| `reiniciar_datos_demo.bat` | Vuelve a cargar los datos de prueba y borra las fotos subidas. Úsalo justo antes de una demo. |
| `ejecutar_pruebas.bat` | Ejecuta las 51 pruebas automáticas (instala Playwright la primera vez). Necesita Python 3. |

Si tu MySQL tiene contraseña, escríbela en la línea `set "CLAVE="` de los dos primeros archivos y en `config/config.php`.

## Usuarios de prueba

| Tipo | Correo | Contraseña |
|---|---|---|
| Administrador | admin@papaconecta.pe | Admin#2026 |
| Agricultor | juana.quispe@correo.pe | Demo#2026 |
| Agricultor | teodoro.huaman@correo.pe | Demo#2026 |

El comprador no necesita cuenta: se registra con sus datos al confirmar su primer pedido.

## Estructura

```
papa_conecta/
├── index.php              Redirige a public/
├── public/                Única carpeta visible desde el navegador
│   ├── index.php          Punto de entrada: carga la configuración y despacha las rutas (index.php?r=...)
│   ├── assets/            css/estilos.css, js/app.js, img/ (logo, productos y variedades de prueba)
│   └── uploads/productos/ Fotos que suben los agricultores (no ejecutan código)
├── app/
│   ├── core/              Router, Controller (base), Conexion (PDO), Sesion (CSRF y mensajes), SubidaFoto, funciones
│   ├── interfaces/        IConexion, IProducto, ICarrito, IPedido, IUsuario, IVariedad
│   ├── models/            ProductoModel, PedidoModel, UsuarioModel, VariedadModel, CarritoSesion
│   ├── controllers/       Inicio, Catalogo, Carrito, Pedido, Variedad, Auth, Agricultor y Admin (…Controller)
│   └── views/             Plantillas PHP por módulo (layout, catalogo, carrito, pedido, variedades, auth, agricultor, admin)
├── config/config.php      Conexión a la base de datos y opciones
├── database/              papa_conecta.sql (tablas, triggers, vista y datos de prueba)
└── pruebas/               pruebas_e2e.py: 51 pruebas automáticas de punta a punta (Capítulo 4)
```

## Rutas principales

| Ruta | Página | Caso de uso |
|---|---|---|
| `index.php` | Inicio | — |
| `?r=catalogo` | Catálogo con filtros | CU-04, CU-05 |
| `?r=producto&id=1` | Detalle y «¿De dónde viene?» | CU-08 |
| `?r=carrito` | Carrito y datos del pedido | CU-06, CU-07 |
| `?r=variedades` | Variedades de papa | CU-09 |
| `?r=registro` | Registro del agricultor | CU-01 |
| `?r=agricultor/publicar` | Publicar producto | CU-02 |
| `?r=agricultor/productos` | Mis productos (editar, dar de baja) | CU-03 |
| `?r=admin/moderar` | Moderar publicaciones | CU-10 |

## Pruebas automáticas

Las 51 pruebas del Capítulo 4 del informe usan el sistema como una persona (Playwright + Chromium) y revisan la pantalla y la base de datos.
**Antes de empezar vuelven a cargar `database/papa_conecta.sql`, así que borran los datos de la base.**

```
pip install playwright
playwright install chromium
cd C:\xampp\htdocs\papa_conecta
python pruebas\pruebas_e2e.py
```

El resultado se guarda en `pruebas/resultados_pruebas.json` y las capturas en `pruebas/capturas/`.
Si la dirección o el cliente de MySQL son otros, se cambian con las variables `PAPA_URL`, `PAPA_MYSQL` y `PAPA_MYSQL_CLAVE` (ver el inicio del script).

## Publicar en GitHub

1. En [github.com](https://github.com) crea un repositorio nuevo (**New repository**), por ejemplo `papa-conecta`, **Public** y **sin** README.
2. En una terminal, dentro de esta carpeta:

```
git init
git add .
git commit -m "Papa Conecta: versión final"
git branch -M main
git remote add origin https://github.com/USUARIO/papa-conecta.git
git push -u origin main
```

   Con GitHub Desktop: **File → Add local repository** → elige esta carpeta → **Publish repository**.
3. Copia el enlace del repositorio en la sección 3.7 del informe (texto resaltado en amarillo).

El archivo `.gitignore` evita subir las fotos de prueba y las capturas que generan las pruebas.

## Problemas frecuentes

- **«Error de base de datos»:** MySQL no está iniciado en XAMPP o no se importó `papa_conecta.sql`.
- **No se suben las fotos:** revisa que exista la carpeta `uploads/productos` y que la foto pese menos de 2 MB.
- **Tildes mal mostradas:** importa el script con la codificación UTF-8 (opción por defecto de phpMyAdmin).
