<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension {

    public function getFilters() {
        return [
            new TwigFilter('remove_accent', [$this, 'removeAccent']),
        ];
    }
    
     public function getFunctions(): array
    {
        return [
            new TwigFunction('file_exists', [$this, 'fileExists']),
        ];
    }

    public function removeAccent(string $text): string {
        return strtr(utf8_decode($text), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
        //return iconv('UTF-8', 'US-ASCII//TRANSLIT', $text);
    }
    
    public function fileExists(string $text): string {
        if(file_exists(str_replace('\\', '/', $text))){
            if(filesize(str_replace('\\', '/', $text))){
                return true;
            }
            return false;
        }else{
            return false;
        }
    }

}
