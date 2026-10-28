<?php
/** Página de inicio: propuesta de valor y accesos al catálogo y a la publicación. */
class InicioController extends Controller
{
    public function index(): void
    {
        $variedades = (new VariedadModel())->listar();
        $productos  = (new ProductoModel())->listarActivos();
        $this->vista('inicio', [
            'titulo'      => 'Papa nativa directo del campo',
            'temporada'   => array_slice($variedades, 0, 4),
            'destacados'  => array_slice($productos, 0, 3),
            'totalActivos' => count($productos),
        ]);
    }
}
