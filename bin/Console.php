<?php

namespace App;

use App\Compiler;
use App\Console\Input;
use App\Console\Output;
use App\Compiler\FileCompiler;
use Tightenco\Collect\Support\Collection;

class Console
{
    public $profiles;
    private $input;
    public $output;

    //Hello world

    protected $doCliOutput = true;

    public function __construct()
    {
        $this->profiles = new Collection;
        $this->output = new Output;
        $this->input = new Input;

        foreach (config('profiles', [], true) as $name=>$profile) {
            $this->profiles->put($name, (object) $profile);
        }
    
        $this->input->options->add(['option' => 'files::', 'name' => 'files', 'type' => 'array']);
        $this->input->options->add(['option' => 'output::', 'name' => 'output', 'type' => 'array']);
        $this->input->options->add(['option' => 'profile::', 'name' => 'profile', 'type' => 'string']);
        $this->input->options->add(['option' => 'syntax::', 'name' => 'syntax', 'type' => 'string', 'default' => 'js']);
        $this->input->options->add(['option' => 'console::', 'name' => 'console', 'type' => 'bool']);
        $this->input->options->add(['option' => 'watch::', 'name' => 'watch', 'type' => 'bool']);
        $this->input->options->add(['option' => 'no-output::', 'name' => 'no-output', 'type' => 'bool']);
        $this->input->options->add(['option' => 'copy-to::', 'name' => 'copy-to', 'type' => 'array']);
        $this->input->options->add(['option' => 'verbose::', 'name' => 'verbose', 'type' => 'bool']);
        $this->input->options->add(['option' => 'tree::', 'name' => 'tree', 'type' => 'int', 'default' => -1]);
        $this->input->options->add(['option' => 'minify::', 'name' => 'minify', 'type' => 'bool']);
        $this->input->options->add(['option' => 'obfuscate::', 'name' => 'obfuscate', 'type' => 'bool']);
        $this->input->options->add(['option' => 'source-comments::', 'name' => 'source-comments', 'type' => 'bool']);
        $this->input->options->add(['option' => 'pre-run-script::', 'name' => 'pre-run-script', 'type' => 'string', 'default' => null]);
        $this->input->options->add(['option' => 'with::', 'name' => 'with', 'type' => 'json', 'default' => null]);
    }

    public function validateOptions($options)
    {
        if (!empty($options->profile)) {
            if (is_null(config('profiles.'.$options->profile))) {
                $this->output->print("#@4Profile '{$options->profile}' doesn't exist!");
            }
        }
    }

    public function injectProfileOptions(object &$options, $profileName = null)
    {
        $options->app = $this;
        $useProfile = $profileName ?? $options->profile ?? null;
        if (!empty($useProfile)) {
            $profile = $this->profiles->get($useProfile);
            
            foreach ($profile??[] as $key => $value) {
                $profile->{kebabToCamel($key)} = $value;
            }


            
            if (!is_null($profile)) {
                foreach ($profile->profiles??[] as $importProfile) {
                    $this->injectProfileOptions($options, $importProfile);
                }
                
                
                foreach ($profile->files??[] as $file) {
                    if (in_array($file, $options->files)) {
                        continue;
                    }
                    $options->files[] = $file;
                }
                
                $options->syntax = $profile->syntax??$options->syntax;

                foreach ($profile->output??[] as $output) {
                    if (in_array($output, $options->output)) {
                        continue;
                    }
                    $options->output[] = $output;
                }

                $options->plugins = $profile->plugins??$options->plugins??[];
                $options->sourceComments = $profile->sourceComments??$options->sourceComments??false;

                foreach ($profile->copyTo??[] as $copy) {
                    if (in_array($copy, $options->copyTo)) {
                        continue;
                    }
                    $options->copyTo[] = $copy;
                }
            }
        }
    }

    public function load($language)
    {
        foreach (config('plugins.'.$language) as $pluginClass) {
            $plugin = new $pluginClass;
            if (method_exists($plugin, 'preLoad')) {
                $plugin->preLoad($language);
            }
        }
    }

    public function run()
    {
        $options = $this->input->getOptionInput();
        $this->injectProfileOptions($options);
        $this->validateOptions($options);
        $this->toggleCliOutput(!$options->noOutput);

        $this->load($options->syntax);
        
        $compiler = Compiler::create($options);


        // if ($options->watch) {
        $this->output->clear();
        // }
        $this->output->print("#2Custom NPCs Compiler #r- Created by #eRunonstof");

        if ($options->console) {
            readline_read_history('.console-history');
            $compiler->addPlugins($compiler->getPlugins());
            $compiler->addPlugins($options->plugins??[]);

            $f = $compiler->getFileCompilers();

            //Pre run console scripts for debugging the compiler
            if (!empty($options->preRunScript)) {
                include __DIR__ . '/../config/console/' . $options->preRunScript.'.php';
            }

            console:
            $cmd = readline(" >>> ");
            readline_add_history($cmd); //Adds last command to history so you can use up-arrow-key to navigate to history
            readline_write_history('.console-history');
            $cmdOutput = @eval("return $cmd;");

            if ($this->doCliOutput) {
                dump($cmdOutput);
            }

            goto console;
        }



        if ($options->tree >= 0) {
            $compiler->fullReset()->load();

            $fileCompilers = $compiler->getFileCompilers();

            if (count($fileCompilers) == 1) {
                $options->tree = 1;
            }

            foreach ($fileCompilers as $fileCompilerIndex=>$fileCompiler) {
                if ($options->tree > 0 && $fileCompilerIndex+1 != $options->tree) {
                    continue;
                }

                $this->output->print("#8[ Recursive file import tree of #7".$fileCompiler->getRelativeFilePath()." #8]");
                $this->output->print("#2+ #e".$fileCompiler->getRelativeFilePath(). ($options->tree == 0 ? " #3(Do #c--tree=" . ($fileCompilerIndex + 1) . "#3 to show all imports of this file.)" : ''));

                if ($options->tree == 0) {
                    continue;
                }

                $imports = new Collection;
                $outputtedImports = new Collection;

                $fileCompiler->load()->eachRecursiveImport(function ($level, FileCompiler $importCompiler) use (&$imports, $outputtedImports) {
                    $colors = [
                        '2',
                        '1',
                        '5',
                        '4',
                    ];

                    $lines = '';
                    for ($i = 0; $i < $level; $i++) {
                        $lines .= '#' . $colors[$i % count($colors)] . '-|';
                    }

                    $alreadyImported = $imports->contains(function ($value, $key) use ($importCompiler) {
                        return $value == $importCompiler->getRelativeFilePath();
                    });
                    $alreadyOutputted = $outputtedImports->contains(function ($value, $key) use ($importCompiler) {
                        return $value == $importCompiler->getRelativeFilePath();
                    });

                    if (!$alreadyImported) {
                        $imports->push($importCompiler->getRelativeFilePath());
                    } else {
                        if ($alreadyOutputted) {
                            return false;
                        }
                        $outputtedImports->push($importCompiler->getRelativeFilePath());
                    }
                    $importPath = $importCompiler->getRelativeFilePath();
                    $showPath = str_replace(basename($importPath), '#2'.basename($importPath), $importPath);
                    
                    $this->output->print("$lines #e" . $showPath . ($alreadyImported ? ' #4[Already imported, skipped]' : ''));
                });
                $this->output->print('#8==============================================');
            }
            exit;
        }

        

        if (count($options->files) > 0) {
            $compiler->addPlugins($compiler->getPlugins());
            $compiler->addPlugins($options->plugins??[]);

            compile:
            if (!$options->watch) {
                $compiler->run($status);
                if ($status == 0) {
                    $compiler->copyOutput($options->copyTo??[]);
                    if (!$options->noOutput && !$options->watch) {
                        $this->output->pprint("#rSuccessfully compiled #e" . count($compiler->getFilePaths()) . "#r file(s). ");
                        if (!empty($options->copyTo??[])) {
                            $copyToCount = count($options->copyTo);
                            $this->output->pprint('#fCopied compiled files to #e' . $copyToCount . '#f ' . ($copyToCount == 1 ? 'directory' : 'directories') . '.');
                            $this->output->pprint('#3-> #bNow do #e/noppes script reload#b in-game#3 <-', null);
                        }
                    }
                }
            }

            if ($options->watch) {
                $watchInit = false;
                $compileCache = [];




                compileWatch:
                
                $filePathCount = 0;
                $importFileCount = 0;
                $checked = new Collection;
                if (!$watchInit) {
                    foreach ($compiler->getFileCompilers() as $fileCompiler) {
                        $filePathCount++;
                        
                        $fileCompiler->load()->eachRecursiveImport(function ($level, FileCompiler $importCompiler) use (&$compileCache, &$importFileCount, &$checked) {
                            if ($checked->contains($importCompiler->getFilePath())) {
                                return false;
                            }
                            $checked->push($importCompiler->getFilePath());
                            $compileCache[$importCompiler->getFilePath()] = intval(filemtime($importCompiler->getFilePath()));
                            $importFileCount++;
                        });
                    }
                }
                if (!$watchInit) {
                    $this->output->print('');
                    $this->output->clear();
                    $this->output->print("#fWatching #e".$filePathCount." entry point#f files and #2".$importFileCount." imported#f files for changes.");
                    $this->output->print("Press #eCTRL+C#r to quit.");
                }
                $compiler
                ->fullReset()
                ->run($status);
                if ($status == 0) {
                    $copyTo = $options->copyTo??[];
                    $compiler->copyOutput($copyTo);
                    if (!empty($copyTo)) {
                        $copyToCount = count($copyTo);
                        $this->output->pprint('#fCopied compiled files to #e' . $copyToCount . '#f ' . ($copyToCount == 1 ? 'directory' : 'directories') . '.');
                        $this->output->pprint('#3-> #bNow do #e/noppes script reload#b in-game#3 <-', null);
                    }
                }
                $status = 0;
                
                watch:
                
                $triggered = false;
                $watched = new Collection;
                // dump($options);
                $outputText = [];

                foreach ($compiler->getFileCompilers() as $ii=>$fileCompiler) {
                    $fileCompilerPath = $fileCompiler->getFilePath();
                    $mtime = filemtime($fileCompilerPath);
                    if (intval($compileCache[$fileCompilerPath] ?? 0) != intval($mtime)) {
                        $triggered = true;
                        if (!$options->noOutput && isset($compileCache[$fileCompilerPath])) {
                            $showPath = str_replace(BASEDIR . DIRECTORY_SEPARATOR, '', $fileCompilerPath);
                            $outputText[] = "#fDetected change in #e{$showPath} #2(Entry Point File)";
                        }
                    }
                    $compileCache[$fileCompilerPath] = intval($mtime);
                    
                    $fileCompiler->load()->eachRecursiveImport(function ($level, FileCompiler $importCompiler) use (&$watched, &$triggered, &$compileCache, $options) {
                        $importPath = $importCompiler->getFilePath();
                        
                        if ($watched->contains($importPath)) {
                            return false;
                        }
                        
                        $cacheFileTime = $compileCache[$importPath] ?? null;
                        if (is_null($cacheFileTime)) {
                            $triggered = true;
                            if (!$options->noOutput) {
                                $showPath = str_replace(BASEDIR . DIRECTORY_SEPARATOR, '', $importPath);
                                $outputText[] = "#fDetected new import file #e{$showPath}";
                            }
                            $compileCache[$importPath] = intval(filemtime($importPath));
                        } elseif (intval($cacheFileTime) != intval(filemtime($importPath))) {
                            $triggered = true;
                            if (!$options->noOutput) {
                                $showPath = str_replace(BASEDIR . DIRECTORY_SEPARATOR, '', $importPath);
                                $outputText[] = "#fDetected change in #e{$showPath} #2(Imported File)";
                            }
                            $compileCache[$importPath] = intval(filemtime($importPath));
                        }
                        $watched->push($importPath);
                    });
                }

                if (!$watchInit) {
                    $triggered = false;
                }

                $watchInit = true;
                clearstatcache();
                if ($triggered) {
                    $this->output->clear();
                    foreach ($outputText as $outputLine) {
                        $this->output->pprint($outputLine);
                    }
                    goto compileWatch;
                }
                usleep(500000);
                goto watch;
            }
        }
    }

    public function toggleCliOutput($doOutput=null)
    {
        if (is_null($doOutput)) {
            $this->doCliOutput = !$this->doCliOutput;
        } else {
            $this->doCliOutput = $doOutput;
        }

        return $this;
    }
}

define('BASEDIR', realpath(__DIR__ . '/../'));
