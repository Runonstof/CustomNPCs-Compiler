<?php

namespace App\Importer;

use Tightenco\Collect\Support\Collection;

class DependencyManager
{
    private static $packagesFile = 'runon-packages.json';
    private $data;

    public function __construct($data = [])
    {
        $this->data = collect($data);
    }

    public static function defaults()
    {
        return [
            'dependencies' => [
                
            ]
        ];
    }
    
    
    public static function create($packagesFile = null)
    {
        $file = BASEPATH . '/' . ($packagesFile ?? self::$packagesFile);
        
        return new self(json_decode(file_get_contents($file)));
    }
}
