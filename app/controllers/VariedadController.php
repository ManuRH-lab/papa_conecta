<?php
/** Módulo Variedades: CU-09 (consultar información de variedades). */
class VariedadController extends Controller
{
    public function index(): void
    {
        $this->vista('variedades/index', [
            'titulo'     => 'Variedades de papa nativa',
            'variedades' => (new VariedadModel())->listar(),
        ]);
    }

    public function detalle(): void
    {
        $id = $this->entero('id');
        $variedad = $id ? (new VariedadModel())->obtener($id) : null;
        if (!$variedad) {
            http_response_code(404);
            $this->vista('layout/error', ['titulo' => 'Variedad no encontrada', 'mensaje' => 'La variedad no existe.']);
            return;
        }
        $this->vista('variedades/detalle', [
            'titulo'    => 'Papa ' . $variedad['nombre'],
            'variedad'  => $variedad,
            'productos' => (new ProductoModel())->listarActivos(['id_variedad' => $id]),
        ]);
    }
}
