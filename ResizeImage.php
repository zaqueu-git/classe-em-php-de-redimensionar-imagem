<?php
namespace application\libraries\ResizeImage;

class ResizeImage 
{
    /**
     * Atributo para referenciar o depurador.
     *
     * @var bol
     */
    private $debugger;            
    /**
     * Atributo para referenciar a imagem.
     *
     * @var obj
     */
    private $image;    
    /**
     * Atributo para referenciar a largura.
     *
     * @var int
     */
    private $width;    
    /**
     * Atributo para referenciar a altura.
     *
     * @var int
     */
    private $height;    
    /**
     * Atributo para referenciar a imagem redimensionada.
     *
     * @var obj
     */
    private $imageResized;

    /**
     * Classe construtora
     * Instância o método para verificar e definir a imagem.
     */    
    function __construct($fileName) 
    {
        $this->image = $this->openImage($fileName);
    }

    private function getSizeByFixedHeight($newHeight) 
    {
        $ratio = $this->width / $this->height;
        $newWidth = $newHeight * $ratio;
        return $newWidth;
    }

    private function getSizeByFixedWidth($newWidth) 
    {
        $ratio = $this->height / $this->width;
        $newHeight = $newWidth * $ratio;
        return $newHeight;
    }

    private function getSizeByAuto($newWidth, $newHeight) 
    {
        if ($this->height < $this->width)
        // A imagem a ser redimensionada é mais ampla (paisagem)
        {
            $optimalWidth = $newWidth;
            $optimalHeight= $this->getSizeByFixedWidth($newWidth);
        }
        elseif ($this->height > $this->width)
        // A imagem a ser redimensionada é mais alta (retrato)
        {
            $optimalWidth = $this->getSizeByFixedHeight($newHeight);
            $optimalHeight= $newHeight;
        }
        else
        // A imagem a ser redimensionada é um quadrado
        {
            if ($newHeight < $newWidth) {
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);
            } else if ($newHeight > $newWidth) {
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;
            } else {
                // Quadrado sendo redimensionado para um quadrado
                $optimalWidth = $newWidth;
                $optimalHeight= $newHeight;
            }
        }

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    private function getOptimalCrop($newWidth, $newHeight) 
    {

        $heightRatio = $this->height / $newHeight;
        $widthRatio  = $this->width /  $newWidth;

        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }

        $optimalHeight = $this->height / $optimalRatio;
        $optimalWidth  = $this->width  / $optimalRatio;

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight) 
    {
        // Localizar centro - isso será usado para o corte
        $cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
        $cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );

        $crop = $this->imageResized;
        //imagedestroy($this->imageResized);

        // Agora corte do centro para o tamanho exato solicitado
        $this->imageResized = imagecreatetruecolor($newWidth , $newHeight);
        imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
    }
    
    /**
     * Método para salvar a imagem
     *
     * @param  string $savePath
     * @param  string $imageQuality
     * @return void
     */
    public function saveImage($savePath, $imageQuality="100") 
    {
        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);

        switch($extension)
        {
            case '.jpg':
            case '.jpeg':
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($this->imageResized, $savePath, $imageQuality);
                }
                break;

            case '.gif':
                if (imagetypes() & IMG_GIF) {
                    imagegif($this->imageResized, $savePath);
                }
                break;

            case '.png':
                // Qualidade da escala de 0-100 a 0-9
                $scaleQuality = round(($imageQuality/100) * 9);

                // Inverta a configuração de qualidade como 0 é o melhor, não 9
                $invertScaleQuality = 9 - $scaleQuality;

                if (imagetypes() & IMG_PNG) {
                        imagepng($this->imageResized, $savePath, $invertScaleQuality);
                }
                break;

            default:
                break;
        }

        imagedestroy($this->imageResized);
    }
    
    /**
     * Método para selecionar o tipo de dimensão da imagem.
     *
     * @param  int $newWidth - nova largura
     * @param  int $newHeight - nova altura
     * @param  array $option (exact, portrait, landscape, auto, crop) - opção (exato, retrato, panorama, auto, colheita).
     * @return void
     */
    private function getDimensions($newWidth, $newHeight, $option) 
    {
        switch ($option) {
            case 'exact':
                $optimalWidth = $newWidth;
                $optimalHeight= $newHeight;
                break;
            case 'portrait':
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;
                break;
            case 'landscape':
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);
                break;
            case 'auto':
                $optionArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
            case 'crop':
                $optionArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
        }
        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    /**
     * Método para criar uma nova imagem redimensionada.
     * Define largura e altura ideais, com base na opção.
     * Se a opção for cortar, é cortado.
     *
     * @param  int $newWidth - nova largura.
     * @param  int $newHeight - nova altura.
     * @param  int $option (exact, portrait, landscape, auto, crop) - opção (exato, retrato, panorama, auto, colheita).
     */
    public function resize($newWidth, $newHeight, $option="auto") 
    {
        $optionArray = $this->getDimensions($newWidth, $newHeight, $option);
        $optimalWidth  = $optionArray['optimalWidth'];
        $optimalHeight = $optionArray['optimalHeight'];

        $this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
        imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);

        if ($option == 'crop') {
            $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
        }
    }    
    
    /**
     * Método para criar uma nova imagem a partir de um arquivo ou URL, tipos aceitos jpeg, gif ou png.
     *
     * @param  string $file - arquivo.
     * @return obj
     */
    private function openImage($file) {
        $extension = strtolower(strrchr($file, '.'));

        switch($extension) {
            case '.jpg':
            case '.jpeg':
                $img = @imagecreatefromjpeg($file);
                break;
            case '.gif':
                $img = @imagecreatefromgif($file);
                break;
            case '.png':
                $img = @imagecreatefrompng($file);
                break;
            default:
                $img = false;
                break;
        }

        if ($img) {
            $this->width  = imagesx($this->image);
            $this->height = imagesy($this->image);
        }

        return $img;
    }

    /**
     * Método para mostrar as mensagens do depurador.
     *
     * @param  array $phases - fases.
     * @return html
     */
    private function debuggerOutput($phases) 
    {
        if ($this->debugger) {
            echo "<div style='display: flex; flex-direction: column; align-items: flex-start; width: 320px; padding: 20px; margin: 20px; background: #eeeeee; border: 1px solid #9e9e9e;'>";
            foreach ($phases as $key => $value) {

                $object = (object) $value;
                if ($object->value) {
                    echo "<div>{$object->name} :: <span style='color: #4caf50; font-weight: 800;'>OK</span></div>";
                    continue;                    
                }

                echo "<div>{$object->name} :: <span style='color: #ff0000; font-weight: 800;'>ERRO</span></div>";
            }
            echo "</div>";            
        }
    }
    
    /**
     * Método para ativar o depurador.
     *
     * @param  bol $debugger - depurador.
     * @return bol
     */
    public function debugger($debugger) 
    {
        $this->debugger = $debugger;
    }    
}
?>
