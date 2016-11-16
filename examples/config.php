<?php
/**
 * Display Errors
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

/**
 * Composer autoload
 */
require_once '../vendor/autoload.php';

/**
 * Include Dotenv library
 * @var Dotenv
 */
$dotenv = new Dotenv\Dotenv('../src/config');
$dotenv->load();
