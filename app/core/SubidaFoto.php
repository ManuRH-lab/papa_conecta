<?php
/** Guarda la foto del producto en uploads/productos con un nombre aleatorio (CU-02, CU-03). */
class SubidaFoto
{
    private const TIPOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * @return string|null ruta relativa (uploads/productos/xxx.jpg) o null si no se envió foto
     * @throws DomainException si el archivo no es una imagen válida o supera el tamaño máximo
     */
    public static function guardar(?array $archivo): ?string
    {
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $maxMb = (require RAIZ . '/config/config.php')['app']['foto_max_mb'];
        if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE
            || $archivo['size'] > $maxMb * 1024 * 1024) {
            throw new DomainException("La foto no debe pesar más de $maxMb MB.");
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            throw new DomainException('No se pudo subir la foto. Inténtalo de nuevo.');
        }
        // Se revisa el contenido real del archivo, no solo su extensión
        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!isset(self::TIPOS[$tipo]) || @getimagesize($archivo['tmp_name']) === false) {
            throw new DomainException('La foto debe ser una imagen JPG, PNG o WEBP.');
        }
        $nombre = 'uploads/productos/' . bin2hex(random_bytes(12)) . '.' . self::TIPOS[$tipo];
        if (!move_uploaded_file($archivo['tmp_name'], PUBLICO . '/' . $nombre)) {
            throw new DomainException('No se pudo guardar la foto en el servidor.');
        }
        return $nombre;
    }
}
