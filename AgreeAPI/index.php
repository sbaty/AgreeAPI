<?php
	// Error Reporting
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	// Database Credentials
	$config = parse_ini_file("../agreeConfig.ini");
	$hostname = isset($config["hostname"]) ? $config["hostname"] : "localhost";
	$database = isset($config["database"]) ? $config["database"] : "agree";
	$username = isset($config["username"]) ? $config["username"] : "root";
	$password = isset($config["password"]) ? $config["password"] : "root";
	$port = isset($config["port"]) ? $config["port"] : NULL;
    require("Agree.php");
	$app = new Agree($hostname,$username,$password,$database,$port);
?>