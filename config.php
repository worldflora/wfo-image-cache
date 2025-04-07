<?php

define('WFO_FILE_CACHE', '../data/images/'); // end in a slash

// the derivative image sizes supported are these max dimensions
define('WFO_IMAGE_HEIGHTS', array(150, 500, 1000)); 


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
//error_reporting(E_ALL);
session_start();

// require_once('../../wfo_facet_secrets.php'); // things we don't put in github

/*
  We set the error handler to convert errors to exceptions
  so that we can use try and catch which is better for 
  calling files.
*/
set_error_handler(
  function ($severity, $message, $file, $line) {
      throw new ErrorException($message, $severity, $severity, $file, $line);
  }
);