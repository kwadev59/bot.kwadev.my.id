<?php
// Load configuration
require_once 'config/config.php';

// Load helpers
require_once 'helpers/general_helper.php';
require_once 'helpers/SimpleXLSX.php';

// Autoload core classes
spl_autoload_register(function($class) {
    if (file_exists('../app/core/' . $class . '.php')) {
        require_once '../app/core/' . $class . '.php';
    }
});
