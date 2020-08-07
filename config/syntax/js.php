<?php

return [
    'import' =>  '/import\s+(?:"|\')([\S\-\w.\\\*\/]+\.[a-zA-Z\\*]+)(?:"|\')(?:\s+with\s+({[\s\S]*?}))?(?:\s+as\s+([\w]+))?\s*;/',
    'defineBlock' => '/@block ([\w]+)(?:\;)?([\w\W\r\n]+?)@endblock(?:\;)?/',
    'getBlock' => '/@yield ([\w\-]+);/',
    'forLoopTweak' => '/for\s*\(\s*(?:var\s+)?(\w+)\s+in\s+([\w.()\[\]\"\',+\-\/*\s]+)\s+as\s+(\w+)\s*\)\s*{/',
    'annotations' => '/@([\w]+)(?:\(([\s\S]+)\))?;/',

    //=== Decompiler regex
    'decompMCP' => '/import\s+(method|field)\s+([\w\.]+)\s+as\s+([\w]+)\s*;\s*/',
    'decompMCPGet' => '/\.@([\w]+)/',
];
