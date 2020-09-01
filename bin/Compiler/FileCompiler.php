<?php

namespace App\Compiler;

use Closure;
use Tightenco\Collect\Support\Collection;

class FileCompiler
{
    private static $libraries = null;
    private static $libraryCache = [];
    
    private $filePath;
    public $content;
    private $original;
    public $blocks = null;
    public $modules = null;
    private $plugins;
    public $language = 'js';
    private $imported;
    public $sourceComments = false;
    public $meta;

    public function __construct($filePath, $sourceComments = false)
    {
        $this->meta = new \stdClass;
        $this->filePath = realpath($filePath);
        $this->plugins = [];
        $this->sourceComments = $sourceComments;
        $this->fullReset();
    }

    public function load()
    {
        $this->__construct($this->filePath, $this->sourceComments);
        if (file_exists($this->filePath) && !is_dir($this->filePath)) {
            $this->original = $this->content = file_get_contents($this->filePath);
        }

        return $this;
    }

    public function reset()
    {
        $this->blocks = new Collection;
        $this->modules = new Collection;
        $this->imported = [];
        $this->content = $this->original;

        return $this;
    }

    public function fullReset()
    {
        $this->unload();
        $this->reset();

        return $this;
    }
    
    public function unload()
    {
        $this->original = '';
        $this->content = '';

        return $this;
    }

    public function getYields()
    {
        $yieldRgx = syntax($this->language)->get('getBlock');

        preg_match_all($yieldRgx, $this->content, $matches);
        // dump($matches);
        $yields = new Collection;

        foreach ($matches[0] as $i=>$match) {
            $yield = new \stdClass;

            $yield->content = $match;
            $yield->name = $matches[1][$i];

            $yields->push($yield);
        }

        return $yields;
    }

    public function scanBlocks()
    {
        $blockRgx = syntax($this->language)->get('defineBlock');

        preg_match_all($blockRgx, $this->content, $matches);
        $blocks = new Collection;

        foreach ($matches[0] as $i=>$match) {
            $block = new \stdClass;

            $sourceCommentLine = '';
            $endSourceCommentLine = '';
            $lineNumber = strline($this->content, $match);

            if ($this->sourceComments) {
                $sourceCommentLine = "/* === Block @{$matches[1][$i]} - Import File: " . $this->getRelativeFilePath() . ":$lineNumber */\n";
                $endSourceCommentLine = "\n/* === End Block @{$matches[1][$i]} */";
            }

            $block->content = $match;
            $block->name = $matches[1][$i];
            $block->innerContent = $sourceCommentLine.$matches[2][$i].$endSourceCommentLine;
            $block->file = $this->filePath;
            $block->lineNumber = $lineNumber;
            

            $blocks->push($block);
        }

        return $blocks;
    }

    public function scanModules($from='content', $scanContent = null)
    {
        $content = '';
        if (!is_null($from)) {
            $content = $this->{$from} ?? null;
        } else {
            $content = $content ?: $scanContent;
        }

        $moduleRgx = syntax($this->language)->get('defineModule');

        preg_match_all($moduleRgx, $content, $matches);
        $modules = new Collection;

        foreach ($matches[0] as $i=>$match) {
            $sourceCommentLine = '';
            $endSourceCommentLine = '';
            $lineNumber = strline($content, $match);
    
            if ($this->sourceComments) {
                $sourceCommentLine = "/* === Module @{$matches[1][$i]} - Import File: " . $this->getRelativeFilePath() . ":$lineNumber */\n";
                $endSourceCommentLine = "\n/* === End Module @{$matches[1][$i]} */";
            }

            $module = new \stdClass;
            
            $module->content = $match;
            $module->name = $matches[1][$i];
            $module->constructor = $matches[2][$i];
            $module->requires = $matches[3][$i];
            $module->innerContent = $sourceCommentLine.$matches[4][$i].$endSourceCommentLine;
            $module->file = $this->filePath;
            $module->lineNumber = $lineNumber;

            $modules->push($module);

            // $innerModules = $this->scanModules(null, $matches[4][$i]);
            // $modules = $modules->merge($innerModules);
        }

        return $modules;
    }

    public function getImports()
    {
        $importRgx = syntax($this->language)->get('import');

        preg_match_all($importRgx, $this->content, $matches);

        $libs = $this->getLibraries()->{$this->language} ?? null;

        $imports = new Collection;
        foreach ($matches[0] as $i=>$match) {
            $rawPath = $matches[2][$i] ?: $matches[3][$i];
            $importPath = $rawPath;
            if (empty($matches[2][$i])) {
                $rawPath = str_replace('.', '/', $rawPath) . '.js';
            }
            $rawPath = realpath(__DIR__ . '/../../src') . '\\' . $rawPath;
            
            //end replace
            $rawFiles = glob($rawPath);

            $relativeFilePaths = collect($rawFiles)->map(function ($rawFile) {
                return str_replace(realpath(__DIR__ . '\..\..\\') . '\\', '', $rawFile);
            })->toArray();
            
            if ($libs) {
                if ($lib = ($libs->{$importPath} ?? false)) {
                    $libModules = explode(',', trim($matches[1][$i]));
                    
                    foreach ($libModules as $libModuleName) {
                        $libModuleName = trim($libModuleName);
                        if (!isset($lib->{$libModuleName})) {
                            continue;
                        }
                        $libModuleUrl = $lib->{$libModuleName};
                        $libModulePath = $this->loadLibrary($importPath, $libModuleName, $libModuleUrl);

                        $rawFiles[] = $libModulePath;
                    }
                }
            }
    
            $import = new \stdClass;
            $import->statement = $match;
            $import->modules = $matches[1][$i];
            $import->importPath = $importPath;
            $import->rawPath = $rawPath;
            $import->filePaths = $rawFiles;
            $import->relativeFilePaths = $relativeFilePaths;
            $import->payload = is_json($matches[4][$i]) ? json_decode($matches[4][$i]) : [];
            $import->alias = $matches[5][$i];
    
    
            $imports->push($import);
        }

        return $imports;
    }

    public function runYields()
    {
        foreach ($this->getYields() as $yield) {
            $replaceContent = '';
       
            foreach ($this->blocks->where('name', $yield->name) as $block) {
                $replaceContent .= $block->innerContent;
            }

            $this->content = str_replace($yield->content, $replaceContent, $this->content);
        }

        

        return $this;
    }

    public function runBlocks(&$blocks=null)
    {
        foreach ($this->scanBlocks() as $block) {
            $this->content = str_replace($block->content, '', $this->content);

            if (!is_null($blocks)) {
                $blocks->push($block);
            } else {
                $this->blocks->push($block);
            }
        }
        return $this;
    }

    public function runModules(&$modules=null)
    {
        foreach ($this->scanModules() as $module) {
            $this->content = str_replace($module->content, '', $this->content);

            if (!is_null($modules)) {
                $modules->push($module);
            } else {
                $this->modules->push($module);
            }
        }
        return $this;
    }

    public function runImports(&$imported=[], &$blocks=null, &$modules=null)
    {
        $imports = $this->getImports();

        $blocks = $blocks ?? $this->blocks;
        $modules = $modules ?? $this->modules;

        foreach ($imports as $import) {
            $importContent = '';

            foreach ($import->filePaths as $filePath) {
                if (array_key_exists($filePath, $imported)) {
                    if (!empty($import->modules)) {
                        foreach (explode(',', $import->modules) as $importModule) {
                            $importModule = trim($importModule);
                            $importModuleStatement = $importModule;


                            if (in_array($importModuleStatement, $imported[$filePath]->modules)) {
                                continue;
                            }
                            $importAlias = preg_replace('/.+?\s+as\s+([\w\W]+)/', '$1', $importModule);
                            $importModule = preg_replace('/(.+?)\s+as\s+[\w\W]+/', '$1', $importModule);
                            
                            $module = $imported[$filePath]->fileCompiler->scanModules('original')
                                ->filter(function ($importedFile) use ($importModule) {
                                    return fnmatch($importModule, $importedFile->name);
                                })
                                ->where('file', $imported[$filePath]->fileCompiler->getFilePath())
                                ->first();
                            
                                
                            if ($module) {
                                if (!empty(preg_replace('/\w+/', '', $importAlias))) {
                                    $importAlias = $module->name;
                                }
                                $moduleContent = $module->innerContent;
                                $moduleContent = preg_replace('/_@\$/m', $importAlias, $moduleContent);
                                $moduleContent = preg_replace('/__\$/m', $module->constructor, $moduleContent);
                                $moduleContent = preg_replace('/function\s+(_\$)/m', 'function ' . $importAlias, $moduleContent);
                                $moduleContent = preg_replace('/_\$/m', $importAlias, $moduleContent);
                                $importContent = $moduleContent . "\n" . $importContent;
                                $imported[$filePath]->modules[] = $importModuleStatement;
                            }
                        }
                    }
                } else {
                    $importFile = new FileCompiler($filePath, $this->sourceComments);
                    $importCached = $imported[$filePath] = new \stdClass;

                    $importCached->fileCompiler = $importFile;
                    $importCached->modules = [];
    
                    
                    $importFile->addPlugins($this->plugins);
                    $importFile->run($imported, $blocks, $modules);

                    
                    $sourceCommentLine = '';
                    $sourceCommentEndLine = '';
                    if ($this->sourceComments) {
                        $importRelativeFilePath = $importFile->getRelativeFilePath();
                        $sourceCommentLine = "\n/* Import File: " . $importRelativeFilePath . " */\n";
                        $sourceCommentEndLine = "\n/* End Import File: " . $importRelativeFilePath . " */";
                    }

                    $content = $importFile->content;
                    $import->modules = $import->modules ?: $importFile->scanModules('original')->pluck('name')->join(',');

                    foreach (explode(',', $import->modules) as $importModule) {
                        $importModule = trim($importModule);
                        $importModuleStatement = $importModule;
                        if (in_array($importModuleStatement, $importCached->modules)) {
                            continue;
                        }
                        $importAlias = preg_replace('/.+?\s+as\s+([\w\W]+?)/', '$1', $importModule);
                        $importModule = preg_replace('/(.+?)\s+as\s+[\w\W]+/', '$1', $importModule);
                        
                        
                        $module = $importFile->scanModules('original')
                            ->filter(function ($importedFile) use ($importModule) {
                                return fnmatch($importModule, $importedFile->name);
                            })
                            ->where('file', $importFile->getFilePath())
                            ->first();
                        if ($module) {
                            if (!empty(preg_replace('/\w+/', '', $importAlias))) {
                                $importAlias = $module->name;
                            }
                            $moduleContent = $module->innerContent;
                            $moduleContent = preg_replace('/_@\$/m', $importAlias, $moduleContent);
                            $moduleContent = preg_replace('/__\$/m', $module->constructor, $moduleContent);
                            $moduleContent = preg_replace('/function (_\$)/m', 'function ' . $importAlias, $moduleContent);
                            $moduleContent = preg_replace('/(_\$)/m', $importAlias, $moduleContent);
                            $importContent = $moduleContent . "\n" . $importContent;
                            
                            $importCached->modules[] = $importModuleStatement;
                        }
                    }
    
                    $importContent .= $sourceCommentLine . $content . $sourceCommentEndLine;
                }
            }

            $this->content = str_replace($import->statement, $importContent, $this->content);
        }

        return $this;
    }

    public function runPlugins()
    {
        foreach ($this->plugins as $plugin) {
            $pluginClass = (new $plugin($this->language));
            if (method_exists($pluginClass, 'run')) {
                $pluginClass->run($this);
            }
        }

        return $this;
    }

    public function preRunPlugins()
    {
        foreach ($this->plugins as $plugin) {
            $pluginClass = (new $plugin($this->language));
            if (method_exists($pluginClass, 'preRun')) {
                $pluginClass->preRun($this);
            }
        }

        return $this;
    }

    public function run(&$imported=null, &$blocks=null, &$modules=null)
    {
        if (is_null($imported)) {
            $imported = &$this->imported;
        }
        if (is_null($blocks)) {
            $blocks = &$this->blocks;
        }
        if (is_null($modules)) {
            $modules = &$this->modules;
        }


        $this
            ->load()
            ->addPlugins(Plugin::all($this->language))
            ->preRunPlugins()
            ->runBlocks($blocks)
            ->runModules($modules)
            ->runImports($imported, $blocks, $modules)
            ->runYields()
            ->runPlugins();

        $this->content = preg_replace("/(?:\r\n|\r|\n){3}/", "\n", $this->content);
        
        return $this;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getRelativeFilePath($removeDir='')
    {
        return str_replace(realpath(__DIR__ . '\\..\\..\\') . '\\' . $removeDir, '', $this->filePath);
    }

    public function eachRecursiveImport(Closure $callback, Collection &$imports = null, $level = 0)
    {
        $imports = $imports ?? new Collection;

        foreach ($this->getImports() as $import) {
            foreach ($import->filePaths as $filePath) {
                if ($imports->contains('filePath', $filePath)) {
                    continue;
                }
    
                $imports->push($import);
                $importCompiler = new self($filePath);
    
                $importCompiler->load();
    
                $break = call_user_func_array($callback, [$level+1, $importCompiler]) === false;
                
                if (!$break) {
                    $importCompiler->eachRecursiveImport($callback, $imports, $level+1);
                }
            }
        }
    }

    public function getAllImports(Collection &$imports = null)
    {
        $imports = $imports ?? new Collection;

        foreach ($this->getImports() as $import) {
            foreach ($import->filePaths as $filePath) {
                if ($imports->contains('filePath', $filePath)) {
                    continue;
                }
    
                $imports->push($import);
    
                $importCompiler = new self($filePath);
    
                $importCompiler->load();
                $importCompiler->getAllImports($imports);
            }
        }

        return $imports;
    }

    public function addPlugin(Plugin $plugin)
    {
        if (!in_array($plugin, $this->plugins)) {
            $this->plugins[] = $plugin;
        }

        return $this;
    }

    public function addPlugins($plugins)
    {
        foreach ($plugins as $plugin) {
            $this->plugins[] = $plugin;
        }

        return $this;
    }

    public function getPlugins()
    {
        return $this->plugins;
    }

    public function getLibraries()
    {
        if (is_null(self::$libraries)) {
            $libs = require BASEDIR . DIRECTORY_SEPARATOR . 'config/libraries.php';
            $libs = array_merge_recursive($libs, json_decode(file_get_contents(BASEDIR . DIRECTORY_SEPARATOR . 'config/libraries.json'), true));

            self::$libraries = array_to_object($libs);
        }

        return self::$libraries;
    }

    public function loadLibrary($importPath, $module, $url)
    {
        $importPath = str_replace('.', '/', $importPath);
        if (!isset(self::$libraryCache[$importPath])) {
            self::$libraryCache[$importPath] = [];
        }
        if (!isset(self::$libraryCache[$importPath][$module])) {
            $sep = DIRECTORY_SEPARATOR;
            $putDir = BASEDIR . "{$sep}lib$sep" . $importPath . $sep;
            $putFile = $putDir . $module . "." . $this->language;

            if (!file_exists($putFile)) {
                mkpath($putDir);
                file_put_contents($putFile, file_get_contents($url));
            }
            self::$libraryCache[$importPath][$module] = $putFile;
        }
        return self::$libraryCache[$importPath][$module];
    }
}
