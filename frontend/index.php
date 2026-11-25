<?php 
    declare(strict_types=1);

	date_default_timezone_set("Europe/Athens");

    session_start();

    //var_dump($_SESSION);

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
<script>
    
</script>
<body>
    <form method="POST">
        <div>
            <a href="" style="text-decoration: underline;">Home</a>
            <a href="">Play</a>
            <a href="">About</a>
        </div>
        <h>Hello, <?= $name ?></h>
    </form>
</body>
</html>

