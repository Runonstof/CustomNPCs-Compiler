<?php

namespace App\Compiler\Plugin;

use App\Compiler\FileCompiler;
use App\Compiler\Plugin;
use Tightenco\Collect\Support\Collection;

class MCPDecompiler extends Plugin
{
    private static $rgxDecompMethod = '/^MD:\s+([\w\S]+)\s+\([\w\S]*\)[\w\S]*\s+[\w\S]+\/([\w]+)/';
    private static $rgxDecompField = '/^FD:\s+([\w\S]+)\s+[\w\S]+\/(\w+)/';

    private static $rgxNamespaceNum = '/\$(\d+)$/';

    public static $srgFile = __DIR__ . '\..\..\..\config\mcp\mcp.srg';

    private static $decompMethodCache = [];
    private static $decompFieldCache = [];

    private static $mcpImportCache;

    public static function init()
    {
        if (!self::$mcpImportCache) {
            self::$mcpImportCache = new Collection;
        }
    }

    private static function getMCPMethod($methodPath, $overload=0, $srgFile=null, $useDotNotation=true)
    {
        return self::getMCP('method', $methodPath, $overload, $srgFile, $useDotNotation);
    }

    private static function getMCPField($fieldPath, $overload=0, $srgFile=null, $useDotNotation=true)
    {
        return self::getMCP('field', $fieldPath, $overload, $srgFile, $useDotNotation);
    }

    

    private static function getMCP($type, $mcpPath, $overload=0, $srgFile=null, $useDotNotation=true)
    {
        self::init();
        if ($useDotNotation) {
            $mcpPath = str_replace('.', '/', $mcpPath);
        }

        $mcpOverloadPath = $mcpPath . '$' . strval($overload);
        if (isset(self::$decompMethodCache[$mcpOverloadPath])) {
            return self::$decompMethodCache[$mcpOverloadPath];
        }

        $srgFile = realpath($srgFile ?? self::$srgFile);

        $useRegex = self::${$type == 'method' ? 'rgxDecompMethod' : 'rgxDecompField'};

        $handle = @fopen($srgFile, "r");
        if ($handle) {
            $overloaded = -1;
            while (($buffer = fgets($handle)) !== false) {
                if (preg_match($useRegex, $buffer, $matches)) {
                    // dump(trim($mcpPath), $matches[1]);
                    if (trim($mcpPath) != $matches[1]) {
                        continue;
                    }
                    $overloaded++;
                    if($overloaded != $overload) {
                        continue;
                    }

                    

                    self::$decompMethodCache[$mcpOverloadPath] = $matches[2];

                    return $matches[2];
                }
            }
            if (!feof($handle)) {
                echo "Error: unexpected fgets() fail\n";
            }
            fclose($handle);
        }

        return null;
    }

    public function prerun(FileCompiler $fileCompiler)
    {
        self::init();
        $this->event('run');

        $rgxMcpMulti = syntax($this->language)->get('decompMCPMulti');
        
        preg_match_all($rgxMcpMulti, $fileCompiler->content, $matches);
      
        foreach($matches[0] as $i=>$mcpImport) {
            $mcpType = str_starts_with($matches[1][$i], 'method') ? 'method' : 'field';
            $mcpArgs = explode(',', trim($matches[2][$i]));
            $mcpNamespace = trim($matches[3][$i]);
            $mcpNamespaceAlias = trim($matches[4][$i]);
            if(!empty($mcpNamespaceAlias)) {
                $mcpNamespaceAlias .= '_';
            }

            foreach($mcpArgs as $mcpArg) {
                $mcpArg = trim($mcpArg);
                $mcpArgInfo = preg_split('/\s+as\s+/', $mcpArg);
                $mcpArgName = $mcpArgInfo[0];
                preg_match(self::$rgxNamespaceNum, $mcpArgName, $nsMatches);
                $mcpOverload = 0;
                if(!empty($nsMatches)) {
                    $mcpArgName = preg_replace(self::$rgxNamespaceNum, '', $mcpArgName);
                    $mcpOverload = intval($nsMatches[1] ?? 0);
                }

                $mcpArgAlias = $mcpArgInfo[1] ?? $mcpArgName;
                $mcpAlias = $mcpNamespaceAlias . $mcpArgAlias;
                
                self::$mcpImportCache = self::$mcpImportCache
                ->reject(function($cached) use($mcpAlias){
                    return $cached->alias == $mcpAlias;
                })    
                ->push($dd = (object)[
                    'type' => $mcpType,
                    'namespace' => $mcpNamespace . '.' . $mcpArgName,
                    'overload' => $mcpOverload,
                    'alias' => $mcpAlias,
                    'import' => $mcpImport
                ]);
                // dd(self::$mcpImportCache);
                //TODO: Push no doubles
                // dump($dd);
            }
            // dump(compact('mcpType', 'mcpArgs', 'mcpNamespace', 'mcpNamespaceAlias'),"==========");
            $fileCompiler->content = str_replace($mcpImport, '', $fileCompiler->content);
            
        }
        // dd($matches);
        
        $rgxMcp = syntax($this->language)->get('decompMCP');

        preg_match_all($rgxMcp, $fileCompiler->content, $matches);
        
        
        foreach ($matches[0] as $i=>$mcpImport) {
            $mcpType = $matches[1][$i];
            $mcpNamespace = $matches[2][$i];
            $mcpAlias = $matches[3][$i];

            $cached = self::$mcpImportCache
                ->where('type', $mcpType)
                ->where('namespace', $mcpNamespace)
                ->where('alias', $mcpAlias)
                ->first();
            if (!$cached) {
                preg_match(self::$rgxNamespaceNum, $mcpArgName, $nsMatches);
                $mcpOverload = 0;
                if(!empty($nsMatches)) {
                    $mcpArgName = preg_replace(self::$rgxNamespaceNum, '', $mcpArgName);
                    $mcpOverload = intval($nsMatches[1] ?? 0);
                }
                self::$mcpImportCache->push($dd = (object)[
                    'type' => $mcpType,
                    'namespace' => $mcpNamespace,
                    'overload' => $mcpOverload,
                    'alias' => $mcpAlias,
                    'import' => $mcpImport
                ]);
            }

            $fileCompiler->content = str_replace($mcpImport, '', $fileCompiler->content);
        }
    }

    public function run(FileCompiler $fileCompiler)
    {
        $blockData = [];
        $rgxMcpGet = syntax($this->language)->get('decompMCPGet');

        preg_match_all($rgxMcpGet, $fileCompiler->content, $matches);

        foreach ($matches[0] as $i=>$mcpImportGet) {
            $mcpImportGetAlias = $matches[1][$i];
            $decomped = self::$mcpImportCache->where('alias', $mcpImportGetAlias)->first();

            if ($decomped) {
                $decompedItem = self::getMCP($decomped->type, $decomped->namespace, $decomped->overload);

                if ($decompedItem) {
                    $blockData[$decomped->alias] = $decompedItem;
                    $fileCompiler->content = str_replace($mcpImportGet, '.'.$decompedItem, $fileCompiler->content);
                }
            }
        }

        $rgxMcpGetShort = syntax($this->language)->get('decompMCPGetShort');

        preg_match_all($rgxMcpGetShort, $fileCompiler->content, $matches);

        foreach ($matches[0] as $i=>$mcpImportGet) {
            $mcpImportGetAlias = $matches[1][$i];
            $decomped = self::$mcpImportCache->where('alias', $mcpImportGetAlias)->first();

            if ($decomped) {
                $decompedItem = self::getMCP($decomped->type, $decomped->namespace, $decomped->overload);
                if ($decompedItem) {
                    $blockData[$decomped->alias] = $decompedItem;
                    $fileCompiler->content = str_replace($mcpImportGet, $decompedItem, $fileCompiler->content);
                }
            }
        }

        $block = new \stdClass;
        // dump($matches);

        $block->content = '';
        $block->name = '_MCP';
        $block->innerContent = '/* MCP Deobfuscation Map */' . "\n";
        $block->file = 'MCP Deobfuscator';
        $block->lineNumber = -1;

        $block->innerContent .= 'var _MCP = ' . json_encode($blockData, JSON_PRETTY_PRINT);

        $block->innerContent .= '/* End MCP Deobfuscation Map */';

        $fileCompiler->blocks->push($block);
    }
}
