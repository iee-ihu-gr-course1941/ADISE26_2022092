<?php 
	//class names should be lowercase cause debian on users cant find them
	//TODO try authentication through IT API
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

	$database = new database($host, $user, $pass, $db, $port, $socket);

	//this is for early dev, might wanna remove later
	//wont use conn in index.php
	$conn = $database->getConnection();

	$gateway = new gateway($database);

	//this is for early dev, might wanna remove later
	$result = mysqli_query($conn, "SELECT DATABASE() AS current_db, USER() AS db_user");
	$info = mysqli_fetch_assoc($result);

	$sql = "SELECT * FROM ADISE26_2022092.users";
	$resulttable = $conn ->query($sql);
	$rows = $resulttable->fetch_all(MYSQLI_ASSOC);
	/*
	echo json_encode([
		"status" => "Connected successfully through ssh",
		"database" => $info["current_db"],
		"user" => $info["db_user"],
		"rows" => $rows
	]);
	*/

	session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    if(isset($_GET["error"], $_GET["state"]) && $_GET["state"] == session_id()){
		http_response_code(401);
		echo json_encode([
			"message" => "User denied login",
			"error" => $_GET["error"]
		]);
		exit;
    }

    if(isset($_GET["code"], $_GET["state"]) && $_GET["state"] == session_id()){

		$tokenurl = "https://login.iee.ihu.gr/token";
		$infourl = "https://api.iee.ihu.gr/profile?access_token=";
		$redirecturl = "https://users.iee.ihu.gr/~michlits/ADISE26_2022092/";

		$data = "client_id={$client_id}&client_secret={$client_id_secret}&grant_type=authorization_code&code=".$_GET["code"]."";
		$additional_headers = array(
		'Accept: application/json',
		'Content-Type: application/x-www-form-urlencoded'
		);

		$getToken = curl_init($tokenurl);
		curl_setopt($getToken, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($getToken, CURLOPT_POSTFIELDS, $data);
		curl_setopt($getToken, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($getToken, CURLOPT_HTTPHEADER, $additional_headers);

		$server_output = curl_exec ($getToken);
		
		$res = json_decode($server_output);

		//GET info
		if(isset($res->error)){
			//handle error
			http_response_code(400);
			echo json_encode(["message" =>$res->error->message]);
			exit;
		}
		else {
			$getUserInfo = curl_init($infourl);
			curl_setopt($getUserInfo, CURLOPT_URL, $infourl . $res->access_token);
			curl_setopt($getUserInfo, CURLOPT_RETURNTRANSFER, true);

			$info_output = curl_exec ($getUserInfo);
			$user = json_decode($info_output);
			if(isset($user->id)){
				$_SESSION["auth"] = true;
				$_SESSION["userID"] = $user->id;
				$_SESSION["cn"] = $user->cn;
				$_SESSION["title"] = $user->title;

			}
		}

    }
    //echo json_encode([]);
    //If you have stored user on you database and dont need to update any of fields just get $res->user
    //$res->user = User ID
    /*
    if(isset($res->error)){
        //handel error
        echo $res->error->message;
    }
    else {
        check local db for $res->user
    }

    */

    if(!empty($_SESSION["auth"])){
    	echo json_encode([
			"authenticated" => true,
			"userID" => $_SESSION["userID"],
			"cn" => $_SESSION["cn"],
			"title" => $_SESSION["title"]
		]);
    }
    else {
        $loginUrl = "https://login.iee.ihu.gr/authorization/?" . http_build_query([
			"client_id"     => $client_id,
			"response_type" => "code",
			"scope"         => "id,cn,title",
			"redirect_uri"  => "https://users.iee.ihu.gr/~michlits/ADISE26_2022092/",
			"state"         => session_id()
		]);
		header("Location: " . $loginUrl);
		exit;
    }
	
	
?>
