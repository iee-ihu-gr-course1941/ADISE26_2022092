<?php 
	//class names should be lowercase cause debian on users cant find them
	//works for GET
	//TODO implement curl and use controller->processRequest for different HTTP methods
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

	session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

	$redirecturl = "https://users.iee.ihu.gr/~michlits/ADISE26_2022092/";

	$database = new database($host, $user, $pass, $db, $port, $socket);

	$gateway = new gateway($database);

    if(isset($_GET["error"], $_GET["state"]) && $_GET["state"] == session_id()){
		http_response_code(401);
		echo json_encode([
			"message" => "User denied login",
		]);
		exit;
    }

    if(isset($_GET["code"], $_GET["state"]) && $_GET["state"] == session_id()){

		$tokenurl = "https://login.iee.ihu.gr/token";
		$infourl = "https://api.iee.ihu.gr/profile";

		//POST for access token
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
		
		$res = json_decode((string)$server_output);

		//GET info
		if(isset($res->error)){
			//handle error
			http_response_code(400);
			echo json_encode(["message" =>$res->error->message]);
			exit;
		}else{
			//POST FOR USER INFO
			$getUserInfo = curl_init($infourl);
			curl_setopt($getUserInfo, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($getUserInfo, CURLOPT_HTTPHEADER, [
				"x-access-token: " . $res->access_token,
				"Content-Type: application/json"
			]);

			$info_output = curl_exec ($getUserInfo);
			$user = json_decode($info_output);//!!! JSON OBJECT

			if(isset($user->id)){

				$token = $gateway->getTokenOAuthUser($user->uid);
				$_SESSION["auth"] = true;
				$_SESSION["uid"] = $user->uid;
				//$_SESSION["profile"] = $user; whole profile
				//$_SESSION["profile"]->token = $token; add token to profile
				$_SESSION["token"] = $token;
				header("Location: " . $redirecturl);
				exit;

			} else {
				http_response_code(400);
				echo json_encode(["message" => "Profile failed", "raw" => $info_output]);
				exit;
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

	$apiToken = $_SERVER["HTTP_X_TOKEN"] ?? $_GET["token"] ?? null;
	//check if not auth but uses token
	if(empty($_SESSION["auth"]) && !empty($apiToken)){

		$user = $gateway->getUserByToken((string) $apiToken);
		if($user){
			$_SESSION["auth"] = true;
			$_SESSION["uid"] = $user["uid"];
			$_SESSION["token"] = $user["token"];
		}else{
			http_response_code(401);
			echo json_encode(["message" => "Invalid API token"]);
			exit;
		}
	}

    if(empty($_SESSION["auth"])){
    	//GET for the CODE
        $loginUrl = "https://login.iee.ihu.gr/authorization/?" . http_build_query([
			"client_id"     => $client_id,
			"response_type" => "code",
			"scope"         => "profile",
			"redirect_uri"  => $redirecturl,
			"state"         => session_id()
		]);
		header("Location: " . $loginUrl);
		exit;	
    }
	//HERE ONLY AFTER OAUTH 2.0 protocol 
	$rawPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?? "";
	$path = trim(str_replace(["/~michlits/ADISE26_2022092/", "/ADISE26_2022092/", "/ADISE/", "index.php"], "", $rawPath), "/");
	$parts = array_values(array_filter(explode("/", $path)));

	//NORMAL GET
	if(empty($parts)){
		echo json_encode([
			"auth" => true,
			"uid" => $_SESSION["uid"],
			"token" => $_SESSION["token"]
		]);
		exit;
	}
	
	if($parts[0] !== "api" || empty($parts[1])){
		http_response_code(404);
		echo json_encode(["message" => "Endpoint Not Found"]);
		exit;
	}
	

	$resource = $parts[1];
	$id = $parts[2] ?? null;
	$profile = $parts[3] ?? null;

	$method = $_SERVER["REQUEST_METHOD"];
	
	$controller = new controller($gateway);

	$controller->processRequest($method, $resource, $id, $profile);
	

	/* works, if not check with this
	echo json_encode([
		"status" => "Connected successfully through ssh",
		"database" => $info["current_db"],
		"user" => $info["db_user"],
		"rows" => $rows
	]);
	*/

?>
