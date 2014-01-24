<?php




if(file_exists(__DIR__ . "/../vendor/autoload.php")) {
   require_once(__DIR__ . "/../vendor/autoload.php"); 
} else if (file_exists(__DIR__ . "/../../../autoload.php")) {
   require_once( __DIR__ . "/../../../vendor/autoload.php");
} else {
   trigger_error("Unable To Locate Required Autoloader for the NoizuLabs/FragmentedKeys Libraries"); 
}
