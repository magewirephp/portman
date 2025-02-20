<?php

namespace App\Portman;

class PackagistDownloader
{
public static function getPackagistSources(){
    return collect(portman_config('directories.source', []))->filter(function ($source) {
        if (!isset($source['composer'])) {
            return false;
        }
        if (!is_array($source['composer']) || !isset($source['composer']['name']) || !isset($source['composer']['version'])) {
            return false;
        }
        if (!is_string($source['composer']['name']) || !is_string($source['composer']['version'])) {
            return false;
        }

        return true;
    });
}
}