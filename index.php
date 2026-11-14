<?php 
	declare(strict_types=1);

	require_once __DIR__ . "/config.php";

	spl_autoload_register(function($class){
		$path = str_replace("\\", DIRECTORY_SEPARATOR, $class);
		$file = __DIR__ . "/{$path}.php";

		if(file_exists($file)){
			require_once $file;
		}
	});

	header("Content-Type: application/json; charset=UTF-8");

	set_error_handler("errorHnadler::handleError");
	set_exception_handler("errorHandler::handleException");

	echo json_encode(["message" => "Hello world"]);

?>
