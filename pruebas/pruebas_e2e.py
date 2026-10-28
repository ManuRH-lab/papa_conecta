"""Pruebas funcionales de punta a punta de Papa Conecta (Capítulo 4 del informe).

Usa el sistema como lo haría una persona (Playwright + Chromium) y comprueba el resultado en la pantalla y en la base
de datos. Cada caso registra: id, CU/RF, descripción, resultado esperado, resultado obtenido y estado. También toma las
capturas de pantalla que se usan en el informe (sección 3.6 y Capítulo 4).

ATENCIÓN: antes de empezar vuelve a cargar database/papa_conecta.sql, es decir, BORRA los datos de la base papa_conecta.

Requisitos: XAMPP con Apache y MySQL iniciados, Python 3.10 o superior y Playwright:
    pip install playwright
    playwright install chromium

Ejecución (desde la carpeta papa_conecta):
    python pruebas/pruebas_e2e.py

Variables de entorno opcionales:
    PAPA_URL           dirección de index.php      (por defecto http://localhost/papa_conecta/public/index.php)
    PAPA_MYSQL         cliente de MySQL            (por defecto C:\\xampp\\mysql\\bin\\mysql.exe en Windows; mysql en otros)
    PAPA_MYSQL_CLAVE   contraseña del usuario root (por defecto, vacía como en XAMPP)
    PAPA_CAPTURAS      carpeta de las capturas     (por defecto pruebas/capturas)
    PAPA_RESULTADOS    archivo de resultados       (por defecto pruebas/resultados_pruebas.json)
"""
import json, os, subprocess, sys, tempfile, time, pathlib, re
from playwright.sync_api import sync_playwright

sys.stdout.reconfigure(encoding="utf-8")                # tildes y flechas en la consola de Windows
AQUI = pathlib.Path(__file__).resolve().parent
RAIZ = AQUI.parent                                      # carpeta papa_conecta
BASE = os.environ.get("PAPA_URL", "http://localhost/papa_conecta/public/index.php")
MYSQL = os.environ.get("PAPA_MYSQL", r"C:\xampp\mysql\bin\mysql.exe" if os.name == "nt" else "mysql")
CLAVE = os.environ.get("PAPA_MYSQL_CLAVE", "")
SHOTS = pathlib.Path(os.environ.get("PAPA_CAPTURAS", AQUI / "capturas"))
SHOTS.mkdir(parents=True, exist_ok=True)
RESULTADOS = pathlib.Path(os.environ.get("PAPA_RESULTADOS", AQUI / "resultados_pruebas.json"))
SQL = RAIZ / "database" / "papa_conecta.sql"
UPLOADS = RAIZ / "public" / "uploads" / "productos"
FOTO = str(AQUI / "foto_prueba.jpg")
R = []


def mysql(*args):
    return [MYSQL, "-uroot"] + ([f"-p{CLAVE}"] if CLAVE else []) + list(args)


def q(sql):
    return subprocess.run(mysql("--default-character-set=utf8mb4", "papa_conecta", "-N", "-B", "-e", sql),
                          capture_output=True, text=True, encoding="utf-8", check=True).stdout.strip()


def check(cid, cu, desc, esperado, ok, obtenido):
    R.append({"id": cid, "cu": cu, "descripcion": desc, "esperado": esperado, "obtenido": obtenido,
              "estado": "Aprobado" if ok else "Fallido"})
    print(("OK  " if ok else "FALLA ") + cid, "-", desc, "|", obtenido)


def shot(page, name, full=False):
    page.wait_for_timeout(250)
    page.screenshot(path=str(SHOTS / f"{name}.png"), full_page=full)


def cards(page):
    return page.locator("section.grilla article.producto").count()


def flash(page):
    return " ".join(page.locator(".aviso").all_inner_texts())


# ------------------------------------------------------------------ base limpia
with open(SQL, "rb") as script:
    subprocess.run(mysql("--default-character-set=utf8mb4"), stdin=script, check=True)
for f in UPLOADS.glob("*"):
    if f.name not in (".gitkeep", ".htaccess"):
        f.unlink()

with sync_playwright() as p:
    b = p.chromium.launch()
    # ================================================================ COMPRADOR (visitante)
    ctx = b.new_context(viewport={"width": 1280, "height": 800})
    pg = ctx.new_page()
    pg.on("dialog", lambda d: d.accept())

    pg.goto(BASE); shot(pg, "v01_inicio")
    pg.goto(BASE + "?r=catalogo")
    n = cards(pg)
    check("CP-01", "CU-04 / RF-02", "El catálogo público muestra solo los productos activos",
          "6 productos (el producto «pendiente» no aparece)", n == 6 and "Amarilla Tumbay" not in pg.inner_text("section.grilla"),
          f"{n} productos; Amarilla Tumbay (pendiente) no aparece")
    shot(pg, "v02_catalogo", full=True)

    pg.select_option("#f-variedad", label="Peruanita"); pg.click("text=Aplicar filtros")
    n = cards(pg)
    check("CP-02", "CU-05 / RF-03", "Filtrar por variedad Peruanita", "2 resultados (Junín y Cusco)", n == 2, f"{n} resultados")

    pg.goto(BASE + "?r=catalogo"); pg.select_option("#f-region", label="Puno"); pg.click("text=Aplicar filtros")
    n = cards(pg)
    check("CP-03", "CU-05 / RF-03", "Filtrar por región Puno", "2 resultados (Huayro y Huamantanga)", n == 2, f"{n} resultados")

    pg.goto(BASE + "?r=catalogo"); pg.fill("input[name=min]", "4"); pg.fill("input[name=max]", "4.5"); pg.click("text=Aplicar filtros")
    n = cards(pg)
    check("CP-04", "CU-05 / RF-03", "Filtrar por rango de precio S/ 4.00 – 4.50", "3 resultados", n == 3, f"{n} resultados")
    shot(pg, "v02b_catalogo_filtrado")

    pg.goto(BASE + "?r=catalogo"); pg.fill("input[name=min]", "6"); pg.fill("input[name=max]", "3"); pg.click("text=Aplicar filtros")
    av = flash(pg); n = cards(pg)
    check("CP-05", "CU-05 (1a)", "Precio mínimo mayor que el máximo", "Aviso y no se aplica el filtro de precio",
          "mínimo no puede ser mayor" in av and n == 6, f"Aviso mostrado; {n} resultados")

    pg.goto(BASE + "?r=catalogo&f=1&variedad=3&disponibles=1")
    txt = pg.inner_text("main")
    check("CP-06", "CU-05 (3a)", "Filtro sin resultados (Amarilla Tumbay disponible)", "Mensaje «No hay productos con esos filtros» y opción de limpiar",
          "No hay productos con esos filtros" in txt and "Limpiar filtros" in txt, "Mensaje y botón «Limpiar filtros» visibles")

    pg.goto(BASE + "?r=catalogo&q=%27%20OR%201%3D1%20--%20")
    n = cards(pg)
    check("CP-07", "Seguridad", "Búsqueda con inyección SQL (' OR 1=1 --)", "Sin error; 0 resultados (consulta preparada)",
          n == 0 and pg.locator("text=Error de base de datos").count() == 0, f"{n} resultados, sin error")

    pg.goto(BASE + "?r=producto&id=1")
    t = pg.inner_text("#origen")
    ok = all(s in t for s in ["Juana Quispe Mamani", "Comunidad campesina de Muñani", "Azángaro", "Puno", "3,859"])
    check("CP-08", "CU-08 / RF-08", "Detalle del producto con trazabilidad", "Productor, comunidad, provincia, región y altitud",
          ok, "Ficha «¿De dónde viene?» completa")
    shot(pg, "v03_detalle_trazabilidad", full=True)

    pg.fill("#kg", "10"); pg.click("form >> text=Agregar al carrito")
    cnt = pg.inner_text(".insignia")
    check("CP-09", "CU-06 / RF-04", "Agregar 10 kg de Huayro al carrito", "Mensaje de confirmación y contador = 1",
          "Agregaste 10 kg" in flash(pg) and cnt == "1", f"Contador {cnt}")

    pg.goto(BASE + "?r=producto&id=2"); pg.fill("#kg", "500"); pg.click("form >> text=Agregar al carrito")
    bloqueado = pg.evaluate("document.querySelector('#kg').validity.rangeOverflow") and "r=producto" in pg.url
    pg.evaluate("document.querySelector('#kg').removeAttribute('max')")      # se salta la validación del navegador
    pg.click("form >> text=Agregar al carrito")
    m = flash(pg)
    check("CP-10", "CU-06 (2a)", "Agregar más kilos que el stock (500 kg de Huamantanga, hay 60)",
          "El navegador lo impide y, si se fuerza, el servidor muestra la cantidad máxima",
          bloqueado and "Solo hay 60 kg" in m, "Bloqueado por el navegador; servidor: «" + m.split(" de papa")[0] + "»")
    shot(pg, "e01_stock_carrito")

    pg.goto(BASE + "?r=producto&id=1"); pg.fill("#kg", "5"); pg.click("form >> text=Agregar al carrito")
    pg.goto(BASE + "?r=producto&id=3"); pg.fill("#kg", "5"); pg.click("form >> text=Agregar al carrito")
    pg.goto(BASE + "?r=producto&id=5"); pg.fill("#kg", "3"); pg.click("form >> text=Agregar al carrito")
    pg.goto(BASE + "?r=carrito")
    kg_huayro = pg.input_value("input[name='kg[1]']")
    check("CP-11", "CU-06 (3)", "Agregar otra vez un producto que ya está en el carrito", "Se suman los kilos (10 + 5 = 15)",
          kg_huayro == "15", f"{kg_huayro} kg")

    pg.fill("input[name='kg[1]']", "10"); pg.click("text=Actualizar carrito")
    total = pg.inner_text(".total [data-total]")
    esperado = 10 * 4.50 + 5 * 3.80 + 3 * 5.00
    check("CP-12", "CU-07 / RF-06", "Cambiar la cantidad y recalcular el total", f"Total S/ {esperado:.2f}",
          total.replace(" ", "") == f"S/{esperado:.2f}", total)

    pg.fill("#nombres", "Lu1s"); pg.fill("#correo", "correo-malo"); pg.fill("#telefono", "12345"); pg.click("button:has-text('Confirmar pedido')")
    errs = pg.locator(".error-campo").count()
    pedidos = q("SELECT COUNT(*) FROM pedido")
    check("CP-13", "CU-07 (6a)", "Confirmar con datos incompletos o inválidos", "Campos marcados en rojo y no se registra el pedido",
          errs >= 5 and pedidos == "1", f"{errs} campos con error; pedidos en BD: {pedidos}")
    shot(pg, "e02_validacion_pedido", full=True)
    pg.press("#referencia", "Enter")                     # Enter dentro del formulario del carrito
    n_items = pg.locator("[data-item]").count()
    check("CP-45", "CU-06 / CU-07", "Presionar Enter en un campo de los datos del pedido",
          "Se intenta confirmar (valida los datos); no se quita ningún producto del carrito",
          n_items == 3 and pg.locator(".error-campo").count() >= 4 and q("SELECT COUNT(*) FROM pedido") == "1",
          f"{n_items} productos siguen en el carrito; datos validados")

    pg.goto(BASE + "?r=carrito")                         # pantalla limpia para la captura del informe
    pg.fill("#nombres", "María"); pg.fill("#apellidos", "Salas Ccori"); pg.fill("#correo", "maria.salas@correo.pe")
    pg.fill("#telefono", "987654321"); pg.fill("#direccion", "Jr. Huancavelica 320, Cercado de Lima"); pg.fill("#referencia", "Puerta verde")
    shot(pg, "v04_carrito_pedido", full=True)
    stock_antes = q("SELECT cantidad_kg FROM producto WHERE id_producto=1")
    pg.click("button:has-text('Confirmar pedido')")
    ok_url = "pedido/resumen" in pg.url
    num = re.search(r"N\.° (\d+)", pg.inner_text("main"))
    idp = int(num.group(1)) if num else 0
    total_bd = q(f"SELECT total FROM pedido WHERE id_pedido={idp}")
    stock_desp = q("SELECT cantidad_kg FROM producto WHERE id_producto=1")
    clave = q("SELECT IFNULL(contrasena,'NULL') FROM usuario WHERE correo='maria.salas@correo.pe'")
    check("CP-14", "CU-07 / RF-05", "Confirmar un pedido válido con un comprador nuevo",
          "Resumen con número de pedido; total, stock y comprador sin contraseña en BD",
          ok_url and total_bd == f"{esperado:.2f}" and float(stock_antes) - float(stock_desp) == 10 and clave == "NULL",
          f"Pedido N.° {idp:05d}; total S/ {total_bd}; stock Huayro {stock_antes} → {stock_desp}; contraseña {clave}")
    shot(pg, "v05_pedido_resumen", full=True)
    check("CP-15", "CU-07 (8)", "El carrito queda vacío después del pedido", "Contador = 0", pg.inner_text(".insignia") == "0",
          f"Contador {pg.inner_text('.insignia')}")

    # Stock insuficiente al confirmar (otro comprador se adelantó)
    pg.goto(BASE + "?r=producto&id=2"); pg.fill("#kg", "60"); pg.click("form >> text=Agregar al carrito")
    q("UPDATE producto SET cantidad_kg = 50 WHERE id_producto = 2")
    pg.goto(BASE + "?r=carrito")
    for c, v in [("#nombres", "María"), ("#apellidos", "Salas Ccori"), ("#correo", "maria.salas@correo.pe"),
                 ("#telefono", "987654321"), ("#direccion", "Jr. Huancavelica 320, Cercado de Lima")]:
        pg.fill(c, v)
    pedidos_antes = q("SELECT COUNT(*) FROM pedido")
    pg.click("button:has-text('Confirmar pedido')")
    m = flash(pg)
    check("CP-16", "CU-07 (7a)", "Confirmar cuando el stock bajó (60 kg pedidos, quedan 50)",
          "Mensaje para ajustar la cantidad; no se registra el pedido",
          "Solo hay 50 kg" in m and q("SELECT COUNT(*) FROM pedido") == pedidos_antes, m[:80])
    shot(pg, "e03_stock_insuficiente")
    pg.fill("input[name='kg[2]']", "50"); pg.fill("#correo", "juana.quispe@correo.pe"); pg.click("button:has-text('Confirmar pedido')")
    m = flash(pg)
    check("CP-17", "CU-07", "Comprar con el correo de un agricultor", "Mensaje: usar otro correo; no se registra el pedido",
          "pertenece a una cuenta de agricultor" in m, m[:80])
    pg.fill("#correo", "maria.salas@correo.pe"); pg.click("button:has-text('Confirmar pedido')")
    usuarios_maria = q("SELECT COUNT(*) FROM usuario WHERE correo='maria.salas@correo.pe'")
    check("CP-18", "CU-07", "Segundo pedido con el mismo correo", "Se reutiliza el comprador (no se duplica el usuario)",
          "pedido/resumen" in pg.url and usuarios_maria == "1", f"Usuarios con ese correo: {usuarios_maria}")
    estado2 = q("SELECT estado FROM producto WHERE id_producto=2")
    check("CP-19", "RF-04 / trigger", "Comprar todo el stock restante (50 kg)", "El producto pasa a «agotado» y sale del catálogo",
          estado2 == "agotado", f"Estado: {estado2}")

    # El agricultor cambia el precio mientras el comprador tiene abierto su carrito
    pg.goto(BASE + "?r=producto&id=5"); pg.fill("#kg", "2"); pg.click("form >> text=Agregar al carrito")
    pg.goto(BASE + "?r=carrito")
    for c, v in [("#nombres", "Pedro"), ("#apellidos", "Ramos Ccahua"), ("#correo", "lucia.torres@correo.pe"),
                 ("#telefono", "912345678"), ("#direccion", "Av. Brasil 1450, Pueblo Libre")]:
        pg.fill(c, v)
    q("UPDATE producto SET precio_kg = 5.50 WHERE id_producto = 5")
    pedidos_antes = q("SELECT COUNT(*) FROM pedido")
    pg.click("button:has-text('Confirmar pedido')")
    m = flash(pg)
    check("CP-46", "CU-07 / RF-05", "El precio cambia (S/ 5.00 → S/ 5.50) antes de confirmar",
          "Aviso con el nuevo precio; no se registra el pedido hasta que el comprador confirme otra vez",
          "cambió a S/ 5.50" in m and q("SELECT COUNT(*) FROM pedido") == pedidos_antes,
          "Aviso «…cambió a S/ 5.50 por kilo»; no se registró el pedido")
    pg.click("button:has-text('Confirmar pedido')")       # confirma con el precio nuevo
    t = pg.inner_text("main")
    check("CP-47", "CU-07 / Seguridad", "Pedido con el correo de un comprador ya registrado y otros datos",
          "El resumen muestra solo los datos escritos en este pedido, no los guardados de esa cuenta",
          "pedido/resumen" in pg.url and "Pedro Ramos Ccahua" in t and "912345678" in t
          and "Lucía" not in t and "987444555" not in t, "Resumen con «Pedro Ramos Ccahua»; no muestra datos guardados")
    q("UPDATE producto SET precio_kg = 5.00 WHERE id_producto = 5")   # se restaura para las capturas

    pg.goto(BASE + "?r=variedades")
    nv = pg.locator(".grilla-4 a.producto").count()
    shot(pg, "v06_variedades", full=True)
    pg.click("text=Papa Peruanita")
    t = pg.inner_text("main")
    shot(pg, "v07_variedad_detalle")
    pg.click("text=Ver productos de esta variedad")
    check("CP-20", "CU-09 / RF-07", "Consultar variedades y ver los productos de una variedad",
          "6 variedades; ficha con temporada y beneficios; catálogo filtrado (2 Peruanita)",
          nv == 6 and "Beneficios" in t and cards(pg) == 2, f"{nv} variedades; {cards(pg)} productos Peruanita")

    # Seguridad: acceso sin sesión y CSRF
    pg.goto(BASE + "?r=agricultor/publicar")
    check("CP-21", "Seguridad", "Entrar a «Publicar» sin iniciar sesión", "Redirige al inicio de sesión",
          "r=login" in pg.url, pg.url.split("?")[-1])
    resp = pg.request.post(BASE + "?r=carrito/agregar", form={"id_producto": "1", "kg": "5"})
    check("CP-22", "Seguridad", "Enviar un formulario sin token CSRF", "Solicitud rechazada (400)",
          resp.status == 400, f"HTTP {resp.status}")
    resp = pg.request.post(BASE + "?r=carrito/agregar", data="csrf[]=x&id_producto=1&kg=5",
                           headers={"Content-Type": "application/x-www-form-urlencoded"})
    check("CP-48", "Seguridad", "Enviar el token CSRF como arreglo (csrf[]=x)", "Solicitud rechazada (400), sin error del servidor",
          resp.status == 400, f"HTTP {resp.status}")
    ctx.close()

    # ================================================================ AGRICULTOR NUEVO (CU-01, CU-02)
    ctx = b.new_context(viewport={"width": 1280, "height": 800})
    pg = ctx.new_page()
    pg.on("dialog", lambda d: d.accept())
    pg.goto(BASE + "?r=login"); shot(pg, "v08_login")
    pg.fill("#correo", "juana.quispe@correo.pe"); pg.fill("#clave", "otra-clave1"); pg.click("button:has-text('Ingresar')")
    m = flash(pg)
    check("CP-23", "Inicio de sesión", "Contraseña incorrecta", "Mensaje genérico «Correo o contraseña incorrectos»",
          "Correo o contraseña incorrectos" in m, m)

    pg.goto(BASE + "?r=registro")
    pg.fill("#nombres", "Fortunata"); pg.fill("#apellidos", "Mamani Apaza"); pg.fill("#dni", "4512A")
    pg.fill("#telefono", "951444777"); pg.select_option("#id_region", label="Puno · Chucuito")
    pg.fill("#comunidad", "Comunidad campesina de Cruzani"); pg.fill("#correo", "teodoro.huaman@correo.pe")
    pg.fill("#clave", "Papa2026"); pg.fill("#clave2", "Papa2027"); pg.click("button:has-text('Crear cuenta')")
    t = " ".join(pg.locator(".error-campo").all_inner_texts())
    check("CP-24", "CU-01 (4a, 4b)", "Registro con DNI inválido, correo ya registrado y contraseñas distintas",
          "Errores en DNI, correo y confirmación de contraseña",
          "8 dígitos" in t and "ya está registrado" in t and "no coinciden" in t, "3 errores mostrados")
    shot(pg, "e04_registro_errores", full=True)

    pg.goto(BASE + "?r=registro")                        # pantalla limpia para la captura del informe
    pg.fill("#nombres", "Fortunata"); pg.fill("#apellidos", "Mamani Apaza"); pg.fill("#dni", "45127788")
    pg.fill("#telefono", "951444777"); pg.select_option("#id_region", label="Puno · Chucuito")
    pg.fill("#comunidad", "Comunidad campesina de Cruzani"); pg.fill("#correo", "fortunata.mamani@correo.pe")
    pg.fill("#clave", "Papa2026"); pg.fill("#clave2", "Papa2026")
    shot(pg, "v09_registro", full=True)
    pg.click("button:has-text('Crear cuenta')")
    hash_ = q("SELECT contrasena FROM usuario WHERE correo='fortunata.mamani@correo.pe'")
    check("CP-25", "CU-01 / RF-01", "Registro válido de un agricultor",
          "Cuenta creada, sesión iniciada y contraseña guardada con hash bcrypt",
          "agricultor/publicar" in pg.url and hash_.startswith("$2y$"), f"Hash {hash_[:7]}…; redirige a «Publicar»")

    pg.select_option("#id_variedad", label="Huamantanga"); pg.fill("#cantidad_kg", "40"); pg.fill("#precio_kg", "0")
    pg.click("button:has-text('Publicar producto')")
    t = " ".join(pg.locator(".error-campo").all_inner_texts())
    check("CP-26", "CU-02 (5a)", "Publicar con precio 0", "Error en el precio; no se registra", "mayor a S/ 0.00" in t, t[:60])

    bad = str(pathlib.Path(tempfile.gettempdir()) / "no_es_imagen.jpg")
    pathlib.Path(bad).write_text("esto no es una imagen")
    pg.fill("#precio_kg", "6.50"); pg.set_input_files("#foto", bad); pg.click("button:has-text('Publicar producto')")
    t = " ".join(pg.locator(".error-campo").all_inner_texts())
    check("CP-27", "CU-02 / Seguridad", "Subir un archivo que no es imagen (renombrado .jpg)", "Error: la foto debe ser JPG, PNG o WEBP",
          "JPG, PNG o WEBP" in t, t[:60])

    pg.goto(BASE + "?r=agricultor/publicar")             # pantalla limpia para la captura del informe
    pg.select_option("#id_variedad", label="Huamantanga"); pg.fill("#cantidad_kg", "40"); pg.fill("#precio_kg", "6.50")
    pg.set_input_files("#foto", FOTO)
    shot(pg, "v10_publicar", full=True)
    pg.click("button:has-text('Publicar producto')")
    idnuevo = q("SELECT MAX(id_producto) FROM producto")
    fila = q(f"SELECT estado, imagen FROM producto WHERE id_producto={idnuevo}").split("\t")
    check("CP-28", "CU-02 / RF-01", "Publicar un producto válido con foto", "Producto «pendiente» con la foto guardada en uploads/",
          fila[0] == "pendiente" and fila[1].startswith("uploads/productos/"), f"Estado {fila[0]}; foto {fila[1][:28]}…")
    pg.goto(BASE + "?r=catalogo")
    check("CP-29", "CU-02 (post.)", "La publicación pendiente no aparece en el catálogo", "No visible hasta su aprobación",
          "Fortunata" not in pg.inner_text("main"), "No aparece")

    pg.goto(BASE + "?r=agricultor/publicar")
    pg.select_option("#id_variedad", label="Qompis"); pg.fill("#cantidad_kg", "25"); pg.fill("#precio_kg", "5.80")
    pg.click("button:has-text('Publicar producto')")
    id_rech = q("SELECT MAX(id_producto) FROM producto")

    pg.goto(BASE + "?r=admin/moderar")
    check("CP-30", "Seguridad", "Un agricultor intenta entrar al panel del administrador", "Acceso restringido (403)",
          "Acceso restringido" in pg.inner_text("main"), "Mensaje «Acceso restringido»")
    pg.goto(BASE + f"?r=agricultor/editar&id=1")
    check("CP-31", "CU-03 / Seguridad", "Editar un producto de otro agricultor", "No se permite (producto no encontrado)",
          "No encontramos ese producto" in pg.inner_text("main"), "Mensaje «No encontramos ese producto…»")
    agr_ctx = ctx

    # ================================================================ ADMINISTRADOR (CU-10)
    ctx = b.new_context(viewport={"width": 1280, "height": 800})
    pa = ctx.new_page()
    pa.on("dialog", lambda d: d.accept())
    pa.goto(BASE + "?r=login"); pa.fill("#correo", "admin@papaconecta.pe"); pa.fill("#clave", "Admin#2026"); pa.click("button:has-text('Ingresar')")
    check("CP-32", "Inicio de sesión", "Ingreso del administrador", "Abre la bandeja de moderación",
          "admin/moderar" in pa.url, "Bandeja «Moderar publicaciones»")
    pa.goto(BASE + f"?r=admin/moderar&id={idnuevo}")
    shot(pa, "v13_moderar", full=True)
    pa.click("button:has-text('Aprobar y publicar')")
    est = q(f"SELECT estado FROM producto WHERE id_producto={idnuevo}")
    check("CP-33", "CU-10 (4)", "Aprobar una publicación pendiente", "Estado «activo»; aparece en el catálogo",
          est == "activo", f"Estado {est}")

    pa.goto(BASE + f"?r=admin/moderar&id={id_rech}"); pa.click("button:has-text('Rechazar con observación')")
    t = pa.inner_text("aside")
    check("CP-34", "CU-10 (4a)", "Rechazar sin escribir observación", "Error: la observación es obligatoria",
          "Escribe una observación" in t, "Error mostrado; no se rechaza")
    shot(pa, "e05_rechazo_sin_observacion")
    pa.fill("#observacion", "Agrega una foto de tu cosecha para que el comprador pueda verla.")
    pa.click("button:has-text('Rechazar con observación')")
    fila = q(f"SELECT estado, observacion FROM producto WHERE id_producto={id_rech}").split("\t")
    check("CP-35", "CU-10 (4a)", "Rechazar con observación", "Estado «inactivo» con la observación guardada",
          fila[0] == "inactivo" and fila[1].startswith("Agrega una foto"), f"Estado {fila[0]}")

    pa.goto(BASE + "?r=admin/moderar&bandeja=activas&id=6")
    pa.fill("#observacion", "Las fotos no corresponden a la variedad publicada.")
    pa.click("button:has-text('Retirar del catálogo')")
    est = q("SELECT estado FROM producto WHERE id_producto=6")
    check("CP-36", "CU-10 (3a)", "Retirar una publicación activa", "Estado «inactivo»; sale del catálogo", est == "inactivo", f"Estado {est}")
    q("UPDATE producto SET estado='activo', observacion=NULL WHERE id_producto=6")   # se restaura para las capturas
    pa.goto(BASE + "?r=admin/moderar")
    adm_ctx = ctx

    # ================================================================ AGRICULTOR: corregir, editar, baja (CU-03)
    pg = agr_ctx.pages[0]
    pg.goto(BASE + "?r=agricultor/productos")
    t = pg.inner_text("main")
    check("CP-37", "CU-10 → CU-03", "El agricultor ve la observación del rechazo", "Observación visible en «Mis productos»",
          "Observación del administrador" in t and "Agrega una foto" in t, "Observación visible")
    token = pg.get_attribute("input[name=csrf]", "value")
    pg.request.post(BASE + "?r=agricultor/baja", form={"csrf": token, "id": str(id_rech)})
    fila = q(f"SELECT estado, IFNULL(observacion, 'NULL') FROM producto WHERE id_producto={id_rech}").split("\t")
    check("CP-49", "CU-03 (3a)", "Dar de baja una publicación rechazada enviando el formulario directamente",
          "No se permite; la observación del administrador se conserva",
          fila[0] == "inactivo" and fila[1].startswith("Agrega una foto"), f"Estado {fila[0]}; observación conservada")
    pg.goto(BASE + f"?r=agricultor/editar&id={id_rech}")
    pg.set_input_files("#foto", FOTO)
    shot(pg, "v12_editar")
    pg.click("button:has-text('Guardar y reenviar a revisión')")
    est = q(f"SELECT estado FROM producto WHERE id_producto={id_rech}")
    check("CP-38", "CU-03 / BPMN TO-BE 1", "Corregir y reenviar una publicación rechazada", "Vuelve a «pendiente»",
          est == "pendiente", f"Estado {est}")

    pg.goto(BASE + f"?r=agricultor/editar&id={idnuevo}"); pg.fill("#cantidad_kg", "0"); pg.click("button:has-text('Guardar cambios')")
    e1 = q(f"SELECT estado FROM producto WHERE id_producto={idnuevo}")
    pg.goto(BASE + f"?r=agricultor/editar&id={idnuevo}"); pg.fill("#cantidad_kg", "35"); pg.fill("#precio_kg", "6.20"); pg.click("button:has-text('Guardar cambios')")
    e2 = q(f"SELECT CONCAT(estado,' ',cantidad_kg,' ',precio_kg) FROM producto WHERE id_producto={idnuevo}")
    check("CP-39", "CU-03 (6)", "Editar la cantidad a 0 y luego reponer stock", "«agotado» con 0 kg; «activo» al reponer",
          e1 == "agotado" and e2.startswith("activo 35.00 6.20"), f"{e1} → {e2}")
    pg.goto(BASE + f"?r=agricultor/editar&id={idnuevo}")
    q(f"UPDATE producto SET cantidad_kg = 30 WHERE id_producto = {idnuevo}")   # un comprador se lleva 5 kg
    pg.fill("#precio_kg", "6.30"); pg.click("button:has-text('Guardar cambios')")
    t = " ".join(pg.locator(".error-campo").all_inner_texts())
    e3 = q(f"SELECT CONCAT(cantidad_kg,' ',precio_kg) FROM producto WHERE id_producto={idnuevo}")
    check("CP-50", "CU-03", "Guardar la edición después de que se vendieron kilos (35 → 30 kg)",
          "Aviso de que el stock cambió; no se sobrescribe la cantidad vendida",
          "Mientras editabas" in t and e3 == "30.00 6.20" and pg.input_value("#cantidad_kg") == "30",
          f"Aviso mostrado; en BD {e3.split()[0]} kg; el formulario muestra 30 kg")

    # una tercera publicación para mostrar los estados en «Mis productos» y probar la baja
    pg.goto(BASE + "?r=agricultor/publicar")
    pg.select_option("#id_variedad", label="Huayro"); pg.fill("#cantidad_kg", "30"); pg.fill("#precio_kg", "4.80")
    pg.click("button:has-text('Publicar producto')")
    id_baja = q("SELECT MAX(id_producto) FROM producto")
    pg.locator(f"form:has(input[value='{id_baja}']) >> text=Dar de baja").click()
    pg.wait_for_load_state()
    est = q(f"SELECT estado FROM producto WHERE id_producto={id_baja}")
    check("CP-40", "CU-03 (3a)", "Dar de baja una publicación", "Estado «inactivo» sin observación (De baja)",
          est == "inactivo" and "De baja" in pg.inner_text("main"), f"Estado {est}")
    q(f"UPDATE producto SET estado='inactivo', observacion='La foto no se ve con claridad; vuelve a tomarla con luz natural.' WHERE id_producto={id_rech}")
    pg.goto(BASE + "?r=agricultor/productos"); shot(pg, "v11_mis_productos", full=True)
    q(f"UPDATE producto SET estado='pendiente', observacion=NULL WHERE id_producto={id_rech}")

    # Aprobar una publicación pendiente que no tiene kilos (CU-10)
    q(f"UPDATE producto SET estado='pendiente', cantidad_kg=0 WHERE id_producto={id_baja}")
    pa = adm_ctx.pages[0]
    pa.goto(BASE + f"?r=admin/moderar&id={id_baja}"); pa.click("button:has-text('Aprobar y publicar')")
    est = q(f"SELECT estado FROM producto WHERE id_producto={id_baja}")
    check("CP-51", "CU-10 (4)", "Aprobar una publicación pendiente con 0 kg", "Queda «agotado» (no aparece como disponible)",
          est == "agotado" and "agotado" in flash(pa), f"Estado {est}")
    q(f"UPDATE producto SET estado='inactivo', cantidad_kg=30 WHERE id_producto={id_baja}")   # se restaura

    # XSS: la comunidad con <script> se muestra como texto
    alerts = []
    pv = b.new_page(); pv.on("dialog", lambda d: (alerts.append(d.message), d.accept()))
    original = q("SELECT comunidad FROM agricultor WHERE dni='45127788'")
    q("UPDATE agricultor SET comunidad='Cruzani <script>alert(1)</script>' WHERE dni='45127788'")
    pv.goto(BASE + f"?r=producto&id={idnuevo}")
    html = pv.content()
    q(f"UPDATE agricultor SET comunidad='{original}' WHERE dni='45127788'")
    check("CP-41", "Seguridad", "Texto con <script> en la comunidad del agricultor", "Se muestra como texto; no se ejecuta",
          "&lt;script&gt;" in html and not alerts, "Escapado con htmlspecialchars(); sin alertas")
    pv.close()

    pg.locator("form[action*='logout'] button").first.click()
    pg.goto(BASE + "?r=agricultor/productos")
    check("CP-42", "Cierre de sesión", "Salir y volver a una página privada", "Redirige al inicio de sesión", "r=login" in pg.url, "Redirige a «Ingresar»")

    # ================================================================ ADMIN: captura final de la bandeja
    pa = adm_ctx.pages[0]
    pa.goto(BASE + "?r=admin/moderar"); shot(pa, "v13b_moderar_bandeja", full=True)

    # ================================================================ MÓVIL (RFN-01) y tiempos (RFN-02)
    m = b.new_context(viewport={"width": 390, "height": 844}, device_scale_factor=2, is_mobile=True, has_touch=True)
    pm = m.new_page()
    pm.goto(BASE + "?r=catalogo")
    w_card = pm.locator("section.grilla article.producto").first.bounding_box()["width"]
    menu_btn = pm.locator(".boton-menu").is_visible()
    shot(pm, "m01_movil_catalogo")
    pm.click(".boton-menu"); shot(pm, "m02_movil_menu")
    check("CP-43", "RFN-01", "Catálogo en un celular (390 px)", "Una columna y menú desplegable",
          w_card > 330 and menu_btn, f"Tarjeta de {w_card:.0f} px de ancho; menú ☰ visible")
    pm.goto(BASE + "?r=producto&id=5"); shot(pm, "m03_movil_detalle", full=True)
    pm.fill("#kg", "4"); pm.click("form >> text=Agregar al carrito")
    pm.goto(BASE + "?r=carrito"); shot(pm, "m04_movil_carrito", full=True)
    m.close()

    pt = b.new_page()
    tiempos = []
    for r in ["", "?r=catalogo", "?r=producto&id=1", "?r=variedades", "?r=carrito"]:
        t0 = time.perf_counter(); pt.goto(BASE + r, wait_until="load"); tiempos.append(time.perf_counter() - t0)
    check("CP-44", "RFN-02", "Tiempo de carga de 5 páginas (servidor local)", "Menos de 3 segundos cada una",
          max(tiempos) < 3, f"Máximo {max(tiempos):.2f} s; promedio {sum(tiempos)/len(tiempos):.2f} s")
    b.close()

R.sort(key=lambda r: int(r["id"][3:]))
RESULTADOS.write_text(json.dumps(R, ensure_ascii=False, indent=1), encoding="utf-8")
ok = sum(r["estado"] == "Aprobado" for r in R)
print(f"\n{ok}/{len(R)} casos aprobados")
