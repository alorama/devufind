<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overridding configuration values from modules, etc.  
 * You would place values in here that are agnostic to the environment and not 
 * sensitive to security. 
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source 
 * control, so do not include passwords or other sensitive information in this 
 * file.
 */


/*
        if (!isset($_COOKIE['mylib'])) {  // mylibcookie
                $myhost = explode('.',$_SERVER['HTTP_HOST']);
                 error_log(__file__ . ' line ' . __line__ . ' $myhost=' . $myhost,0);
                if (is_numeric($myhost[0]))
                {
                  $mylib  = $myhost[0];
                } else {
                    $mylib = $_GET['mylib'];        
                }
                if (is_numeric($mylib)) {
                setcookie('mylib', $mylib, null, '/');
                }
        }
*/


return array(
    // ...
);
