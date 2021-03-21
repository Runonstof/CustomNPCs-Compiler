<?php

namespace App;

use App\Compiler\FileCompiler;
use App\Compiler\HunterObfuscator;
use JShrink\Minifier;
use Tightenco\Collect\Support\Collection;

class Compiler
{
    public $files;
    public $language = 'js';
    public $outputFiles;
    public $sourceComments;
    public $minify;
    public $obfuscate;
    public $console;

    protected $scriptsFolder;
    protected $buildFolder;
    protected $outputFolder;


    private $cache;

    public static function create($options)
    {
        return new self($options->files ?? [], $options->syntax, count($options->output) ? $options->output : null, $options->sourceComments ?? false, $options->minify ?? false, $options->obfuscate ?? false, $options->app ?? null);
    }

    public function __construct(array $files = [], string $language = null, array $outputFiles = null, bool $sourceComments = false, bool $minify = false, bool $obfuscate = false, Console $console = null)
    {
        $this->scriptsFolder = realpath(__DIR__ . "\..\\") . "\scripts\\";
        $this->buildFolder = realpath(__DIR__ . '\..\\') . '\build\\';
        $this->outputFolder = realpath(__DIR__ . '\..\\') . '\build\\';
        $this->files = new Collection($files);
        $this->language = $language ?? 'js';
        $this->outputFiles = $outputFiles ?? ['{FileDir}/{FileBaseName}'];
        $this->minify = $minify;
        $this->obfuscate = $obfuscate;
        $this->sourceComments = $sourceComments;
        $this->console = $console;

        $this->cache = new Collection($this->getNewFileCompilers());
    }

    public function setBuildFolder($directory)
    {
        $this->buildFolder = realpath(__DIR__ . '\..\\') . '\\' . $directory;
        return $this;
    }

    public function each(callable $callback)
    {
        foreach ($this->getFileCompilers() as $fileCompiler) {
            $callback($fileCompiler);
        }

        return $this;
    }

    public function getFilePaths()
    {
        $files = [];

        foreach ($this->files as $globFile) {
            foreach (glob($this->scriptsFolder . $globFile) as $file) {
                $filePath = realpath($file);
                if (!in_array($filePath, $files)) {
                    $files[] = $filePath;
                }
            }
        }

        return $files;
    }

    public function getRelativeFilePaths()
    {
        $files = [];

        foreach ($this->getFilePaths() as $filePath) {
            $files[] = str_replace(trim($this->scriptsFolder, '\\/'), '', $filePath);
        }

        return $files;
    }

    /**
     * Get NEW instances of all FileCompilers
     *
     * @return array $compilers
     */
    public function getNewFileCompilers()
    {
        $compilers = [];

        foreach ($this->getFilePaths() as $filePath) {
            $compiler = $compilers[] = new FileCompiler($filePath, $this->sourceComments);

            $compiler->language = $this->language;
        }

        return $compilers;
    }

    /**
     * Get the associated FileCompilers, will be cached so it will return the same FileCompilers
     *
     * @return array $compilers
     */
    public function getFileCompilers()
    {
        $compilers = [];
        foreach ($this->getFilePaths() as $filePath) {
            $cached = $this->cache->filter(function (FileCompiler $cachedCompiler) use ($filePath) {
                return $cachedCompiler->getFilePath() == $filePath;
            })->first();

            if ($cached) {
                $compilers[] = $cached;
                continue;
            }

            $compiler = new FileCompiler($filePath, $this->sourceComments);

            $this->cache->push($compiler);
            $compiler->language = $this->language;
            $compilers[] = $compiler;
        }

        return $compilers;
    }

    public function getRelativeOutputPaths()
    {
        $paths = $this->getOutputFilePaths();

        $outputPaths = [];

        foreach ($paths as $path) {
            $outputPaths[] = str_replace($this->buildFolder, '', $path);
        }

        return $outputPaths;
    }

    public function getOutputFilePaths()
    {
        $fileCompilers = $this->getFileCompilers();
        $outputFiles = [];

        foreach ($fileCompilers as $i => $fileCompiler) {
            $outputFile = $this->outputFiles[$i % count($this->outputFiles)];;
            $outputFiles[] = $this->generateOutputFilePath($outputFile, $fileCompiler->getRelativeFilePath('scripts\\'));
        }

        return $outputFiles;
    }

    /**
     * Insert output variables in $outputFile based on $originFile
     *
     * @param string $outputFile Path to fill with file variables
     * @param string $originFile Relative path from root to origin file
     * @return string
     */
    public function generateOutputFilePath($outputFile, $originFile)
    {
        $path = $outputFile;

        $fileInfo = pathinfo($originFile);

        $path = str_replace([
            '{FileName}',
            '{FileBaseName}',
            '{FileExt}',
            '{FileDir}'
        ], [
            $fileInfo['filename'] ?? '',
            $fileInfo['basename'] ?? '',
            $fileInfo['extension'] ?? '',
            $fileInfo['dirname'] ?? ''
        ], $path);

        return $this->buildFolder . $path;
    }

    public function run(&$status = 0): self
    {
        $failed = false;
        foreach ($this->getPlugins() as $pluginClass) {
            $plugin = (new $pluginClass($this->language));
            if (method_exists($plugin, 'preCompile')) {
                if ($plugin->preCompile($this, $status) === false) {
                    $status = 1;
                    return $this;
                    break;
                }
            }
        }

        $fileCompilers = $this->getFileCompilers();


        foreach ($fileCompilers as $i => $fileCompiler) {
            $outputFile = $this->outputFiles[$i % count($this->outputFiles)];

            $outputFile = $this->generateOutputFilePath($outputFile, $fileCompiler->getrelativeFilePath('scripts\\'));

            $fileCompiler->run();

            $outputDir = dirname($outputFile);
            if (!file_exists($outputDir)) {
                mkpath($outputDir);
            }
            $insertContent = $fileCompiler->content;
            if ($this->minify) {
                $insertContent = Minifier::minify($insertContent);
            }

            if ($this->obfuscate) {
                // $packer = new Packer($insertContent, 'Normal', false, true, true);
                // $insertContent = $packer->pack();
                $obfuscator = new HunterObfuscator($insertContent);
                $insertContent = $obfuscator->Obfuscate();
            }

            $failed = false;
            foreach ($this->getPlugins() as $pluginClass) {
                $plugin = (new $pluginClass($this->language));
                if (method_exists($plugin, 'postSaved')) {
                    if ($plugin->postSaved($fileCompiler) === false) {
                        $failed = true;
                        break;
                    }
                }
            }

            if (!$failed) {
                // $putContents[$outputFile] = $insertContent;
                file_put_contents($outputFile, $insertContent);
            }
        }

        $failed = false;
        foreach ($this->getPlugins() as $pluginClass) {
            $plugin = (new $pluginClass($this->language));
            if (method_exists($plugin, 'postCompile')) {
                if ($plugin->postCompile($this, $status) === false) {
                    $failed = true;
                    break;
                }
            }
        }

        // if (!$failed) {
        //     foreach ($putContents as $outputFile => $insertContent) {
        //         file_put_contents($outputFile, $insertContent);
        //     }
        // }


        return $this;
    }

    public function copyOutput($copyPaths = [])
    {
        $outputFiles = $this->getRelativeOutputPaths();

        foreach ($outputFiles as $outputFile) {
            if (!file_exists($this->outputFolder . '\\' . $outputFile)) {
                continue;
            }
            foreach ($copyPaths as $copyPath) {
                $copyTo = $copyPath . '\\' . $outputFile;



                $copyToDir = dirname($copyTo);

                if (!file_exists($copyToDir)) {
                    mkpath($copyToDir);
                }

                copy(realpath($this->outputFolder . $outputFile), $copyTo);
            }
        }

        return $this;
    }

    public function resetCache()
    {
        $this->cache = new Collection;
    }

    public function getPlugins()
    {
        return config('plugins.' . $this->language);
    }

    public function __call($method, $args)
    {
        $this->each(function (FileCompiler $fileCompiler) use ($method, $args) {
            call_user_func_array([$fileCompiler, $method], $args);
        });

        return $this;
    }

    public function getProfile($profile)
    {
        $profile = null;

        config('profiles.' . $profile);


        return $profile;
    }
}
