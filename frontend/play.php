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
                $status = "Error Creating Game";
            }else{
                $status = $data["message"] . " ID: " . (string) $data["game"]["id"];
            }
        }else{
            $status = "Error Creating Game";
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
    <div>
        <a href="home.php">Home</a>
        <a href="play.php">Play</a>
        <a href="about.php">About</a>
    </div>

    <form id="createform" method="POST">
        <button>Create Game</button>
        <p id = "status"><?= $status ?></p>
    </form>

    <form id="joinform" method="GET">
    <!--make this dropdown -->
        <div class="dropdown">
            <?php if(empty($games)): ?>
                <p>No games available</p>
            <?php else: ?>
                <?php foreach($games as $game):?>
                    <div class="dropdown-content">
                        Game #<?=(string) $game["id"] ?>
                        - Status:<?= $game["status"] ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </form>

    <script>
    </script>
</body>
</html>

