<?php

return [
    'js' => [
        App\Compiler\Plugin\JavaImporter::class,
        App\Compiler\Plugin\MCPDecompiler::class,
        App\Compiler\Plugin\GuiIdReserver::class,
        App\Compiler\Plugin\BabelRunner::class,
    ]
];
