<?php

namespace App;

use App\Console\Input;
use App\Console\Output;
use App\Importer\DependencyManager;

define('BASEPATH', realpath(__DIR__ . '/../'));

class Importer
{
    private $input;
    private $output;
    private $repos = [
        'www.ronanstoffers.nl/api/customnpcs-compiler/repo.json'
    ];

    public function __construct()
    {
        $this->output = new Output;
        $this->input = new Input;

        $this->input->mergeOptions([
            ['option' => 'package::', 'name'=>'package', 'type' => 'string'],
            ['option' => 'version::', 'name'=> 'version', 'type' => 'string', 'default' => 'latest'],
            ['option' => 'remove::', 'name'=> 'remove', 'type' => 'bool']
        ]);
    }

    public function prepareOptions(&$options)
    {
        return $this;
    }

    public function validateOptions($options)
    {
        return $this;
    }

    public function run()
    {
        $options = $this->input->getOptionInput();
        
        $dependencyManager = DependencyManager::create();

        dump($dependencyManager);
    }
}
