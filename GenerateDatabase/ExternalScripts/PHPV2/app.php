<?php

use Professionator\Utils;

require_once(__DIR__ . '/vendor/autoload.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-type: text/plain");
//set_time_limit(10);

//ini_set('curl.cainfo', realpath(__DIR__ . '/../cacert.pem'));

function dj()
{
    die(str_replace('\\/', '/', json_encode(count(func_get_args()) > 1 ? func_get_args() : func_get_args()[0])));
}

function l($value)
{
    return Utils::escape($value);
}