<?php
/**
 * Metodos para la construcción de estructuras.
 * 
 */

namespace Fw\Structures;

use Fw\Paths;

class BuildStructures
{
    public static function init(array $structures, array $args)
    {
        foreach ($structures as $structure) {
            if ( method_exists(static::class, $structure) ) {
                self::{$structure}($args);
            }
        }
    }

        
    /**
     * Se ejecuta el encargado de construir el Plugin Base.
     *
     * @param array $data
     * @return bool
     **/
    public static function basePlugin(array $data) : bool
    {
        return BasePlugin::buildBasePlugin($data);
    }
        
    /**
     * Generador del autoload de la App.
     *
     * @param array $args Argumentos requeridos para la creación de la estructura. 
     * @return void
     **/
    public static function autoload(array $args) : void
    {
        if ( !$args['autoload']) {
            return;
        }

        Autoload::buildAutoload([
            'composerPath' => Paths::buildPath($args['pluginPath'], 'autoload'),
            'uniqueName' => $args['autoload']['uniqueName'],
            'autoload' => [
                'psr-4' => $args['autoload']['psr-4'],
                'files' => $args['autoload']['files']
            ],
            'mode' => $args['mode']
        ]);
    }

    /**
     * Crear una carpeta en una ruta especifica.
     *
     * @param string $path Ruta del directorio.
     * @param int $permissions Permisos para el directorio.
     * @return void
     **/
    public static function createFolder(string $path, int $permissions) : void
    {
        $old_umask = umask(0);
        mkdir($path, $permissions);
        umask($old_umask);
    }

    /**
     * Crear un file en una ruta especifica.
     *
     * @param string $path Ruta del directorio.
     * @param string $content contenido para el file.
     * @return bool
     **/
    public static function createFile(string $path, string $content) : bool
    {
        # Se crea el archivo si no existe.
        $file = fopen($path, "w+b");
        if ( $file == false ) {
            echo "Error al crear el archivo: " . basename($path);
            return false;
        } else {
            # Se escribe el contenido.
            fwrite($file, $content);
            # Fuerza a que se escriban los datos pendientes en el buffer.
            fflush($file);
        }
        # Cerrar el archivo
        fclose($file);
        return true;
    }

    /**
     * Copia un file en una ruta especifica.
     *
     * @param string $source Ruta del file que se copiara (file/folder origen).
     * @param string $dest Ruta para el nuevo file (path destino).
     * @return bool
     **/
    public static function copyFile(string $source, string $dest) : bool
    {
        if ( !copy($source, $dest) ) {
            echo "Error al copiar: $source";
            return false;
        }
        return true;
    }

    /**
     * Copia recursivamente una estructura de folders y files.
     *
     * @param string $source Ruta de la estructura que se copiara .
     * @param string $dest Ruta para la nuevo ubicación de la estructura.
     * 
     **/
    public static function recursiveCopy(string $source, string $dest) {
        $dir = opendir($source);
        @mkdir($dest);
        while(( $file = readdir($dir)) ) {
            if ( ( $file != '.' ) && ( $file != '..' ) ) {
                # Se verifica si es un folder.
                if ( is_dir(Paths::buildPath($source, $file) ) ) {
                    self::recursiveCopy(
                        Paths::buildPath($source, $file),
                        Paths::buildPath($dest, $file)
                    );
                } else { 
                    # Si no es un folder se asume que es un file y se copia
                    copy(
                        Paths::buildPath($source, $file),
                        Paths::buildPath($dest, $file)
                    );
                }
            }
        }
        closedir($dir);
    }


    /**
     * undocumented function summary
     *
     * Undocumented function long description
     *
     * @param Type $structurePath Ruta de la estructura a copiar.
     * @param Type $newPath Ruta destino para la estructura.
     * @return bool
     **/
    public static function copyStructure(string $structurePath, string $newPath) : bool
    {
        if ( !file_exists($newPath) ) {
            self::createFolder($newPath, 0755);
        }

        try {
            self::recursiveCopy($structurePath, $newPath);
        } catch (\Exception $e) { 
            echo "Error al crear ($newPath) ",  $e->getMessage(), "\n";
            return false;
        }
        
        return true;
    }

    /**
     * Borra recursivamente folders y files de una ruta recibida.
     *
     * @param string $path Ruta del folder.
     * @return bool|string
     **/
    public static function remove(string $path) 
    {
        if ( !file_exists($path) ) {
            return true;
        }

        try {
            # Se obtienen y se recorren los folders/files a eliminar.
            $it = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
            $it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

            foreach($it as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($path);
        } catch (\Exception $e) { 
            echo "Error al eliminar ($path) ",  $e->getMessage(), "\n";
            return false;
        }
        
        return true;
    }
}
