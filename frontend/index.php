<?php 
    declare(strict_types=1);

	date_default_timezone_set("Europe/Athens");

    session_start();

    var_dump($_SESSION);

    $creds = $_SESSION;

    foreach($creds as $cred){
        echo "{$cred}\n";
    }

    
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
    <form>
        <h>Hello World</h>
    </form>
</body>
</html>

