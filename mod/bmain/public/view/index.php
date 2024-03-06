<?php
header('Content-Type: application/json');

function stop()
{
    header("HTTP/1.0 404 Not Found");
    exit;
}

require_once('config.class.php');

if (!preg_match('/\.json$/', $_SERVER["REQUEST_URI"])) stop();

$data = preg_split('/(\/|\.)/', preg_replace('/(.*\/view\/)|(\.json$)/', '', $_SERVER["REQUEST_URI"]));

$modul_azon = array_shift($data);

$from_file = realpath( BORDER_PATH_BORDERLIB . 'bmain/mod/' . $modul_azon . '/views/' . implode('/', $data) . '.vue');

if (!$from_file) stop();

$to_file = realpath( BORDER_PATH_BORDERLIB . 'bmain/storage/vue') . DIRECTORY_SEPARATOR . $modul_azon . '.' . implode('.', $data) . '.json';

if (file_exists($to_file) && filemtime($from_file) <= filemtime($to_file)) {
    echo file_get_contents($to_file);
    exit;
}

function vueTag($string, $tagname)
{
    $pattern = "#<\s*?$tagname\b[^>]*>(.*?)</$tagname\b[^>]*>#s";
    preg_match($pattern, $string, $matches);
    return array_key_exists(1, $matches) ? $matches[1] : '';
}

function vueMin(&$str)
{
    $str = str_replace("\n", "", $str);
    $str = str_replace("\r", "", $str);
    $str = preg_replace('/\s+/', ' ', $str);
}

$file = file_get_contents($from_file);

// preg_match('/<style[^>]*>(.*?)<\/style>/s', $file, $matches);
$style = vueTag($file, 'style');
$style = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $style);
vueMin($style);
//css törlése
$file = preg_replace('/<style.*$/s', '', $file);

$script = vueTag($file, 'script');
//script törlése
$file = preg_replace('/<script.*$/s', '', $file);
$script = preg_replace('/^\s*export\s*default\s*/', '', trim($script));
$script = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $script);
vueMin($script);

//csak a template marad
$file = preg_replace('/^\s*<template>/', '', trim($file));
$file = preg_replace('/<\/template>\s*$/', '', trim($file));
vueMin($file);
$file = preg_replace('/<!--(.|\s)*?-->/', '', $file);
$out = json_encode(['t' => $file, 's' => $script, 'c' => $style]);
file_put_contents($to_file, $out);
echo $out;
