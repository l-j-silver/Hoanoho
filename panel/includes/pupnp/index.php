<?php
$template = 'index';

if (isset($_GET['mode']) && file_exists(dirname(__FILE__) . '/templates/' . $_GET['mode'] . '.php')) {

    $template = $_GET['mode'];
}

if(!file_exists('cache'.DIRECTORY_SEPARATOR.'devices')) {
    mkdir('cache'.DIRECTORY_SEPARATOR.'devices', 0770, true);
}

if(!file_exists('cache'.DIRECTORY_SEPARATOR.'albumImages')) {
        mkdir('cache'.DIRECTORY_SEPARATOR.'albumImages', 0770, true);
}

$flash = '';
if (isset($_SESSION['flash'])) {

    $flash = '<div class="flash">' . $_SESSION['flash'] . '</div>';
    unset($_SESSION['flash']);
}

require_once(dirname(__FILE__) . '/templates/' . $template . '.php');
