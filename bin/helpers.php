<?php

use App\Compiler\SyntaxHelper;

function is_json($string)
{
    json_decode($string);

    return json_last_error() === JSON_ERROR_NONE;
}

function to_object($array, $recursive = true)
{
    $obj = new stdClass;
    foreach ($array as $key => $value) {
        $obj->{$key} = is_array($value) && $recursive ? to_object($value) : $value;
    }

    return $obj;
}

function syntax($lang = null)
{
    return new SyntaxHelper($lang);
}

function debug($string)
{
    $debugFile = __DIR__ . "\..\debug.txt";
    file_put_contents($debugFile, file_get_contents($debugFile) . "\n" . $string);
}

function debug_clear()
{
    $debugFile = __DIR__ . "\..\debug.txt";
    file_put_contents($debugFile, '');
}

function mkpath($path)
{
    if (@mkdir($path) || file_exists($path)) {
        return true;
    }
    return mkpath(dirname($path)) && mkdir($path);
}

function config($key, $default = null, $assoc = false)
{
    $keyParts = explode('.', $key);

    $phpFile = realpath(__DIR__ . '\..\\config\\' . $keyParts[0] . '.php');
    $jsonFile =  realpath(__DIR__ . '\..\\config\\' . $keyParts[0] . '.json');

    if (!file_exists($phpFile)) {
        return null;
    }
    $value = require $phpFile ?? [];

    if (file_exists($jsonFile)) {
        $value = array_merge($value, json_decode(file_get_contents($jsonFile), true));
    }

    if (!$assoc) {
        $value = to_object($value ?? []);
    }

    for ($i = 1; $i < count($keyParts); $i++) {
        $value = ($assoc ? $value[$keyParts[$i]] ?? $default : $value->{$keyParts[$i]} ?? $default);
    }

    return $value;
}

function kebabToCamel($string)
{
    return preg_replace_callback('/\-(\w)/', function ($matches) {
        return strtoupper($matches[1]);
    }, $string);
}


function strline($haystack, $needle, $startpos = 0, $startline = 1)
{
    $lines = explode("\r\n", $haystack);
    $needles = explode("\r\n", $needle ?? '');
    foreach ($lines as $i => $line) {
        if (strpos($line, $needles[0]) !== false) {
            if (
                strpos($haystack, $needle) < $startpos
                || $i + 1 < $startline
            ) {
                continue;
            }

            return intval($i) + 1;
        }
    }

    return false;
}

function rrmdir($path)
{
    $files = glob($path . '/*'); // get all file names
    foreach ($files as $file) { // iterate files
        if (is_file($file)) {
            unlink($file);
        } elseif (is_dir($file)) {
            rrmdir($file);
            rmdir($file);
        }
    }
}

function array_to_object($array)
{
    $obj = new stdClass;
    foreach ($array as $k => $v) {
        if (strlen($k)) {
            if (is_array($v)) {
                $obj->{$k} = array_to_object($v); //RECURSION
            } else {
                $obj->{$k} = $v;
            }
        }
    }
    return $obj;
}

function array_value_last($array)
{
    return empty($array) ? null : $array[array_key_last($array)];
}
