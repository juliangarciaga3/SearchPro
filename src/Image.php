<?php
namespace Bitsystem\Backend;

// use Doctrine\ORM\EntityManager;
// use Symfony\Component\HttpFoundation\RequestStack;
// use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Gumlet\ImageResize;
// use BurgerBundle\Components\image_optimizer\lib;

class Image{

    private $sizeThumbnail = [];
    private $folderAbsolute;
    private $fileSystem;
    private $binaryImage;
    private $webDir;

    //Parametros por defecto
    private $nameImage = 'default';
    private $folder = 'images';
    private $width = 1920;
    private $height = 1280;

    public function __construct(){
        $this->webDir = getcwd().'/uploads';   
        $this->folderAbsolute = $this->webDir;  // Ruta de las imagenes 
        $this->fileSystem = new Filesystem();   //Encargado de administrar los ficheros de Symfony
    }

    public function setName($string){
        $this->nameImage = $this->seo_friendly_url($string);
        return $this;
    }

    /**
    *   Cambia un string en un formato amigable para usarla en una url
    *   
    *   @param      $string         String
    *   @return     string
    */
    public function seo_friendly_url($string){
        $string = strtolower($string); // Elimino las mayusculas del String
        $string = str_replace(' ', '_', $string); // Reemplaza todos los espacios con guiones.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '_', $string); // Elimino los caracteres especiales.
        return preg_replace('/_+/', '_', $string);   // Reemplaza múltiples guiones por uno solo.
    }

    /**
    *   Registra las dimensiones de las miniatura
    *
    *   @param      $width          int
    *   @param      $height         int
    *   @return     $this
    */
    public function thumbnail($width = 300, $height = 300){
        try{
            if(!is_null($width) || !is_null($height)){
                $this->sizeThumbnail[] = [ 'width' => (int)$width, 'height' => (int)$height ];
                return $this;
            }else{
                throw new Exception("Inserte los parametros necesarios");
            }
        }catch(Exception $e){
            echo 'Excepcion capturada: '.$e->getMessage().' en el metodo '.__FUNCTION__.'()';
        }
    }

    /**
    *   Crea carpetas en el directorio que indiquemos como primer parametro.
    *   La funcion se encarga de crear las carpetas segun las carpetas que 
    *   existan en el servidor. Guarda hasta 500 imagenes por carpeta.
    *   
    *   @return     Folder Path     String
    */
    public function createFolder(){
        try{

            $this->folder = $this->seo_friendly_url($this->folder);
            $uuid = $this->generate_uuid();

            // Ruta absoluta donde se guardaran todas las imagenes por fecha
            $folderAbsolute = $this->folderAbsolute.'/'.$this->folder.'/'.date("y/m/d");
            $this->fileSystem->mkdir($folderAbsolute.'/'.$uuid, 0755, true);

            return $this->folder.'/'.date("y/m/d").'/'.$uuid;

        }catch(Exception $e){
            echo "La excepción se creó en la línea: " . $e->getLine();
            //$e->getMessage();
        }
    }

    public function insertImage($image, $folder = NULL, $callback){
        try{
            if($image instanceof UploadedFile || !is_string($folder)){
                throw new Exception('Error en el parametro 1, debe de ser un String');
            }
            if(!is_callable($callback)){
                throw new Exception('Error en el parametro 2, debe de ser una funcion');
            }else if(!is_null($folder) && !empty($folder)){
                // Retorno This para recoger los parametros
                $this->binaryImage = $image;
                $this->folder = $folder;
                $callback($this);
                return $this;
            }
        }catch(\FatalThrowableError $e){
            $e->getMessage();
        }
    }

    /**
    *   Un UUID en su forma canónica, está representado por 8 caracteres alfanumericos
    *   Example: fabe4b4e
    *   
    *   @return     String
    */
    public function generate_uuid(){
        return sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }

    /**
    *   Guarda las imagenes en las carpetas del servidor y devuelve un array
    *   con las rutas de las imagenes.
    *
    *   @return     Array
    */
    public function save(){

        // Creo la carpeta donde alojar la imagen
        $routePublic = $this->createFolder();

        // Extension fichero 
        $extension = $this->binaryImage->guessExtension();

        // Ruta de la imagen
        $imageRoute = $this->folderAbsolute.'/'.$routePublic.'/'.$this->nameImage.'.'.$extension;

        //Muevo la imagen de /tmp a $route para ser 
        $this->binaryImage->move($this->folderAbsolute.'/'.$routePublic, $this->nameImage.'.'.$extension);

        // Saca el Alto, Ancho, Tipo y atributos de la imagen original
        list($ancho,$alto,$type,$atributos) = getimagesize($imageRoute); 

        //Guarda la imagen en la carpeta
        $image = new ImageResize($imageRoute);
        //$image->quality_jpg = 85;
        $image->crop($this->width, $this->height, true, ImageResize::CROPCENTER);
        $image->save($this->folderAbsolute.'/'.$routePublic.'/'.$this->nameImage.'-'.$this->width.'x'.$this->height.'.'.$extension,IMAGETYPE_JPEG);
        $imageInfo = [
            'route' => $routePublic.'/'.$this->nameImage.'-'.$this->width.'x'.$this->height.'.'.$extension,
            'original' => $routePublic.'/'.$this->nameImage.'.'.$extension,
            'width' => $ancho,
            'height' => $alto,
        ];

        $imageThumbnailsInfo = [];
        foreach ($this->sizeThumbnail as $key => $size){

            //Guarda la imagen en la carpeta
            $image = new ImageResize($imageRoute);
            //$image->quality_jpg = 95;
            $image->crop($size['width'], $size['height'], true, ImageResize::CROPCENTER);
            $image->save($this->folderAbsolute.'/'.$routePublic.'/'.$this->nameImage.'-'.$size['width'].'x'.$size['height'].'.'.$extension,IMAGETYPE_JPEG);

            $imageThumbnailsInfo[$key] = [
                'route' => $routePublic.'/'.$this->nameImage.'-'.$size['width'].'x'.$size['height'].'.'.$extension,
                'width' => $size['width'],
                'height' => $size['height']
            ];
        }
        return [
            'image' => $imageInfo,
            'thumbnails' => $imageThumbnailsInfo
        ];
    }
}
?>