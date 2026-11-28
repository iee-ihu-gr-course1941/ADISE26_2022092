<?php 

    //TODO join works, fix create with color choice with dropdown menu
    //TODO implement a board and play
    //TODO implement match history
    //TODO implement scoreboard page

    //Used for debugging on frontend
    //ini_set('display_errors', 1);
    //error_reporting(E_ALL);
    //var_dump($_SESSION);
    declare(strict_types=1);

	date_default_timezone_set("Europe/Athens");

    session_start();

    if(empty($_SESSION)){
        header("Location: ../");
        exit;
    }

    $name = $_SESSION["uid"];
    $token = $_SESSION["token"];
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quoridor</title>
</head>

<body>
    <script>
    
    </script>
    <form method="POST">
        <div>
            <a href="home.php">Home</a>
            <a href="play.php">Play</a>
            <a href="about.php">About</a>
        </div>
        <h>Hello, <?= $name ?></h>
    </form>
</body>
</html>

