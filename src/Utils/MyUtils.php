<?php

namespace App\Utils;

class MyUtils {

    public function generateUniqueFileName(): string {
        return time() . "-" . rtrim(strtr(base64_encode(random_bytes(64) . "-" . time()), '+/', '-_'), '=');
    }

    public function slugify($string, $delimiter = '-') {
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');
        $clean = $string;
        try {
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        } catch (\Exception $e) {
            
        }
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower($clean);
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = trim($clean, $delimiter);
        setlocale(LC_ALL, $oldLocale);
        return $clean;
    }

    public function randomPassword($size = 8, $specialChar = true) {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        if ($specialChar) {
            $alphabet .= "@?§!_-+";
        }
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $size; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

}
