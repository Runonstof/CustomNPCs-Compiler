<?php

namespace App\Compiler\Exceptions;

use App\Console\Output;

class CompilerException extends \Exception
{
    public $jsCode;
    public $jsLineNumber;
    public $jsFile;

    public function __construct($message, $code = null, $lineNumber = null, $file = null)
    {
        parent::__construct($message);
        $this->jsCode = $code;
        $this->jsLineNumber = $lineNumber;
        $this->jsFile = $file;
    }

    public function outputError(Output $output)
    {
        $output->pprint('#f' . $this->getMessage(), 2);


        if ($this->code && $this->lineNumber && $this->file) {
            $codeLines = explode("\n", $this->jsCode);

            $offset = max(0, $this->jsLineNumber - 4);
            $length = 7 + min(0, $this->jsLineNumber - 4);

            $codeLines = array_slice($codeLines, $offset, $length);
            $output->print("\n" . $this->getMessage());
            $output->print("\n" . 'Error at: #8' . realpath($this->jsFile) . ':' . $this->jsLineNumber . "\n\n");

            $maxLineNumLength = strlen(strval($offset + $length + 1));

            foreach ($codeLines as $lineIndex => $codeLine) {
                if (empty($codeLine)) {
                    continue;
                }
                $lineNumber = str_pad($offset + $lineIndex + 1, $maxLineNumLength, ' ', STR_PAD_RIGHT);
                $output->print(($this->jsLineNumber == $lineNumber ? '#f#@4>' : ' ') . $lineNumber . ' | ' . $codeLine);
            }
        }
    }
}
