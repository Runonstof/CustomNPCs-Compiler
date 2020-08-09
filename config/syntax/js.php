<?php

return [
    // 'import' =>  '/import\s+(?:"|\')([\S\-\w.\\\*\/]+\.[a-zA-Z\\*]+)(?:"|\')(?:\s+with\s+({[\s\S]*?}))?(?:\s+as\s+([\w]+))?\s*;/',
    'import' =>  '/^(?!\/\/\s*)import\s+(?:{([\s\S]*?)}\s+from\s+)?(?:(?:"|\')([\S\-\w.\\\*\/]+\.[a-zA-Z\\*]+)(?:"|\')|([\w\S.]+))(?:\s+with\s+({[\s\S]*?}))?(?:\s+as\s+([\w]+))?\s*;/m',
    'defineBlock' => '/@block\s+([\w]+)(?:\;)?([\w\W\r\n]+?)@endblock(?:\;)?/',
    'getBlock' => '/@yield ([\w\-]+);/',
    'forLoopTweak' => '/for\s*\(\s*(?:var\s+)?(\w+)\s+in\s+([\w.()\[\]\"\',+\-\/*\s]+)\s+as\s+(\w+)\s*\)\s*{/',
    'annotations' => '/@([\w]+)(?:\(([\s\S]+)\))?;/',
    'functionTweak' => '/function(?:\s+([\w]+))?\s*\(([\w\S\s]*?)\)[\s]*{/',
    'functionTweakArgs' => '/([\w]+)(?:[\s]*=[\s]*([\w\S]+)(?:\s*,|\s*$))?/',

    //=== Decompiler regex
    'decompMCP' => '/import\s+(method|field)\s+([\w\.]+)\s+as\s+([\w]+)\s*;\s*/',
    'decompMCPGet' => '/\.@([\w]+)/',

    //==Gui ID Reserver
    'guiIdFunc' => '/@(id\([\w\s\W]+?\))\$/',

    //JSModules
    'defineModule' => '/@module\s+(?:function\s+)?([\w]+)(?:\(([\w\W]*?)\)(?:\s*:\s*[\w\S\s]+?)?)?(?:\s+requires\s+{([\s\S]*?)})?\;([\w\W\r\n]+?)@endmodule(?:\s+\1+)?;/'
];
