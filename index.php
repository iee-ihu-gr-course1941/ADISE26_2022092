<?php 
	declare(strict_types=1);

	require_once __DIR__ . "/config.php";

	spl_autoload_register(function($class){
		$path = str_replace("\\", DIRECTORY_SEPARATOR, $class);
		$file = __DIR__ . "/api/{$path}.php";

		if(file_exists($file)){
			require_once $file;
		}
	});

	header("Content-Type: application/json; charset=UTF-8");

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	set_error_handler("errorHandler::handleError");
	set_exception_handler("errorHandler::handleException");

	//echo json_encode(["message" => "Hello world"]);

	try{
		$conn = mysqli_connect($host, $user, $pass, $db, $port, $socket);
		mysqli_set_charset($conn, "utf8mb4");

		$result = mysqli_query($conn, "SELECT DATABASE() AS current_db, USER() AS db_user");
		$info = mysqli_fetch_assoc($result);

		$sql = "SELECT * FROM ADISE26_2022092.users";
		$resulttable = $conn ->query($sql);
		$rows = $resulttable->fetch_all(MYSQLI_ASSOC);

		echo json_encode([
			"status" => "Connected successfully through ssh",
			"database" => $info["current_db"],
			"user" => $info["db_user"],
			"rows" => $rows
		]);
	}catch(mysqli_sql_exception $e){
		http_response_code(500);
		echo json_encode([
			"message" => "Connection through ssh failed",
			"error" => $e->getMessage()
		]);
	}
?>
