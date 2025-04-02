<?php

define('WFO_FILE_CACHE', '../data/images/'); // end in a slash

// the derivative image sizes supported are these max dimensions
define('WFO_IMAGE_HEIGHTS', array(150, 500, 1000)); 


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
//error_reporting(E_ALL);
session_start();

require_once('../../wfo_facet_secrets.php'); // things we don't put in github

// create and initialise the database connection
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);  

// connect to the database
if ($mysqli->connect_error) {
  echo $mysqli->connect_error;
}

if (!$mysqli->set_charset("utf8mb4")) {
  echo printf("Error loading character set utf8: %s\n", $mysqli->error);
}