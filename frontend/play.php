<?php
//works 
//TODO complete this file with color choice etc.

    session_start();

    if (empty($_SESSION["auth"])) {
        header("Location: ../");
        exit;
    }
    
    $uid = $_SESSION["uid"] ?? "";
    $token = $_SESSION["token"] ?? "";

    require_once __DIR__ . "/../config.php";

    $ch = require __DIR__ . "/../curlinit.php";

    $status = "";

    if($_SERVER["REQUEST_METHOD"] === "POST"){
        $color = $_POST["color"] ?? "r";

        curl_setopt_array($ch, [
            CURLOPT_URL => "$apiurl/games",
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-Token: $token"
            ],
            CURLOPT_POSTFIELDS => json_encode(["color" => "r"]) // CHANGE THIS
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if($response !== false && $httpCode === 201){
            $data = json_decode($response, true);

            if(empty($data)){
                $status = $data["message"];
            }else{
                $status = $data["message"] . " ID: " . (string) $data["game"]["id"];
            }
        }else{
            $status = "Error Creating Game";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play</title>
</head>
<body>
    <form id="playform" method="POST">
        <button>Create Game</button>
        <p id = "status"><?= $status ?></p>
    </form>

    <script>
    </script>
</body>
</html>

