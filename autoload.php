<?php
include_once(__DIR__ . '/vendor/autoload.php'); 

//==============================================
// Register Autoloader
//==============================================
function noizulabs_core_frag_autoloader($header, $root, $class) {
    $header_length = strlen($header);
    if(substr($class,0,$header_length) == $header) {
        $file = $root . "/" . str_replace("\\", "/", substr($class,$header_length)) . ".php";
        if(defined('TRACE_AUTOLOAD')) echo "Attempting to include $file for $class ... ";
        if(file_exists($file)) {
            if(defined('TRACE_AUTOLOAD')) echo " file found ... \n";
            require_once($file);
        } else if (defined('TRACE_AUTOLOAD')) echo " file not found ... \n";
    }
}

function noizulabs_fragmentedkeys_autoloader($class)
{
    return noizulabs_core_frag_autoloader("NoizuLabs\\FragmentedKeys\\", __DIR__, $class);
}

spl_autoload_register('noizulabs_fragmentedkeys_autoloader');
