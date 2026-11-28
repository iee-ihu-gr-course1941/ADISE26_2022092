<?php
    //works 
    //TODO complete this file with color choice etc.
    //join works

    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    session_start();

    if (empty($_SESSION["auth"])) {
        header("Location: ../");
        exit;
    }
    
    $uid = $_SESSION["uid"] ?? "";
    $token = $_SESSION["token"] ?? "";

    require_once __DIR__ . "/../config.php";

    $ch = require __DIR__ . "/../curlinit.php";

    $createstatus = "";
    $joinstatus = "";

    if($_SERVER["REQUEST_METHOD"] === "POST"){

        $action = $_POST["action"] ?? "";

        if($action  === "create"){
            $color = $_POST["color"] ?? "r";

            curl_setopt_array($ch, [
                CURLOPT_URL => "$apiurl/games",
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "X-Token: $token"
                ],
                CURLOPT_POSTFIELDS => json_encode(["color" => "r"]) //TODO CHANGE THIS
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            $data = json_decode($response, true);
            if(empty($data)){
                $createstatus = "Error Creating Game";
            }else{
                $createstatus = $data["message"] . " ID: " . (string) $data["game"]["id"];
            }
            
        }elseif($action === "join"){
            $gameid = $_POST["gameid"] ?? "";

            if(empty($gameid)){
                $joinstatus = "Please select a game first.";
                curl_close($ch);
            }else{
                curl_setopt_array($ch, [
                    CURLOPT_URL => "$apiurl/games/$gameid",
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        "Content-Type: application/json",
                        "X-Token: $token",
                        "X-HTTP-Method-Override: PATCH"
                    ]
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $data = json_decode($response, true);

                if(empty($data)){
                    $createstatus = "Error Creating Game";
                }else{
                    $createstatus = $data["message"] . " ID: " . (string) $data["game"]["id"];
                }
            }
        }
    }

    $ch = require __DIR__ . "/../curlinit.php";

    $games = [];

    curl_setopt_array($ch, [
            CURLOPT_URL => "$apiurl/games",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-Token: $token"
            ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($response !== false && $httpCode === 200){
        $decoded = json_decode($response, true);
        $games = $decoded["games"] ?? (is_array($decoded) ? $decoded : []); 
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Play</h1>
    <div>
        <a href="home.php">Home</a>
        <a href="play.php">Play</a>
        <a href="about.php">About</a>
    </div>

    <form id="createform" method="POST">
        <input type="hidden" name="action" value="create">
        <div>
            <button>Create Game</button>
            <div class="dropdown"></div>
                <button type="button" id="dropdownbtn">Games</button>
                <div class="dropdown-content" id="dropdownmenu">RED</div>
                <div class="dropdown-content" id="dropdownmenu">BLUE</div>
            <p id = "createstatus"><?= $createstatus ?></p>
        </div>
    </form>

    <form id="joinform" method="POST">
        
        <input type="hidden" name="action" value="join">
        <input type="hidden" name="gameid" id="selectedgame" value="">

        <button>Join Game</button>
        <p id="joinstatus"><?= $joinstatus ?></p>

        <div class="dropdown">
            <button type="button" id="dropdownbtn">Games</button>
            <?php if(empty($games)): ?>
                <p>No games available</p>
            <?php else: ?>
                <div class="dropdown-content" id="dropdownmenu">
                    <?php foreach($games as $game):?>
                        <p class="dropdown-item" data-id="<?= $game["id"]?>">
                            Game #<?=(string) $game["id"] ?>
                            - Status:<?= $game["status"] ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <script src="dropdown.js"></script>
</body>
</html>

