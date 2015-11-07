<?php

function HttpClientAutoLoader($class) {
    if (strpos($class, '_') > 0) {
        $dsPos = strrpos($class, '_');
        $path = substr($class, 0, $dsPos);
        $path = str_replace('_', DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR;
        $class = substr($class, $dsPos + 1);
    } else {
        $path = '';
    }

    if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $path . $class . '.php')) {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . $path . $class . '.php');
    }


}

spl_autoload_register('HttpClientAutoLoader');
