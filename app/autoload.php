<?php
spl_autoload_register( 'library_autoloader' );

function library_autoloader($class) {  
    $class_path = $class;    
    $file =  __DIR__ . '\\..\\' . $class_path . '.php';
    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
        return;
    } 

    //treat last namespace as file name.
    $class_path = explode('\\', $class, -1);
    $file =  __DIR__ . '\\..\\' . implode('\\',  $class_path) . '.php';

    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // if the file does not exist, throw an error
    throw new \Exception("Unable to load class: $class. File not found: $file");
}