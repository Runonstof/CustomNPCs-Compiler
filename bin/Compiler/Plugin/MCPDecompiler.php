<?php

namespace App\Compiler\Plugin;

use App\Compiler\FileCompiler;
use App\Compiler\Plugin;
use Tightenco\Collect\Support\Collection;

class MCPDecompiler extends Plugin
{
    private static $rgxDecompMethod = '/^MD:\s+([\w\S]+)\s+\([\w\S]*\)[\w\S]*\s+[\w\S]+\/([\w]+)/';
    private static $rgxDecompField = '/^FD:\s+([\w\S]+)\s+[\w\S]+\/(\w+)/';
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

    private static function getMCPMethod($methodPath, $srgFile=null, $useDotNotation=true)
    {
        return self::getMCP('method', $methodPath, $srgFile, $useDotNotation);
    }

    private static function getMCPField($fieldPath, $srgFile=null, $useDotNotation=true)
    {
        return self::getMCP('field', $fieldPath, $srgFile, $useDotNotation);
    }

    

    private static function getMCP($type, $mcpPath, $srgFile=null, $useDotNotation=true)
    {
        self::init();
        if ($useDotNotation) {
            $mcpPath = str_replace('.', '/', $mcpPath);
        }

        if (isset(self::$decompMethodCache[$mcpPath])) {
            return self::$decompMethodCache[$mcpPath];
        }

        $srgFile = realpath($srgFile ?? self::$srgFile);

        $useRegex = self::${$type == 'method' ? 'rgxDecompMethod' : 'rgxDecompField'};

        $handle = @fopen($srgFile, "r");
        if ($handle) {
            while (($buffer = fgets($handle)) !== false) {
                if (preg_match($useRegex, $buffer, $matches)) {
                    if (trim($mcpPath) != $matches[1]) {
                        continue;
                    }

                    self::$decompMethodCache[$mcpPath] = $matches[2];

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

    public function run(FileCompiler $fileCompiler)
    {
        self::init();
        $this->event('run');
        
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
                self::$mcpImportCache->push((object)[
                    'type' => $mcpType,
                    'namespace' => $mcpNamespace,
                    'alias' => $mcpAlias,
                    'import' => $mcpImport
                ]);
            }

            $fileCompiler->content = str_replace($mcpImport, '', $fileCompiler->content);
        }

        $rgxMcpGet = syntax($this->language)->get('decompMCPGet');

        preg_match_all($rgxMcpGet, $fileCompiler->content, $matches);

        foreach ($matches[0] as $i=>$mcpImportGet) {
            $mcpImportGetAlias = $matches[1][$i];
            $decomped = self::$mcpImportCache->where('alias', $mcpImportGetAlias)->first();

            if ($decomped) {
                $decompedItem = self::getMCP($decomped->type, $decomped->namespace);

                if ($decompedItem) {
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
                $decompedItem = self::getMCP($decomped->type, $decomped->namespace);

                if ($decompedItem) {
                    $fileCompiler->content = str_replace($mcpImportGet, $decompedItem, $fileCompiler->content);
                }
            }
        }
    }
}
