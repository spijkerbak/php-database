<?php

if (!defined('BASE')) {
    $__p = explode('/', $_SERVER['PHP_SELF']);
    define('BASE', "{$__p[1]}");
    define('HOST', $_SERVER['PHP_SELF']);
    define('ROOT', 'https://' . 'HOST');
}