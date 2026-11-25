<?php

    session_start();
    
    $uid = $_SESSION["uid"] ?? "";
    $token = $_SESSION["token"] ?? "";

    require_once __DIR__ . "/../config.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play</title>
</head>
<body>
    <form id="playform">
        <button id="createbtn" type="button">Create Game</button>
        <p id = "status"></p>
    </form>

    <script>
    const api_url = <?= json_encode($apiurl) ?>;
    const api_token = <?= json_encode($token) ?>;

    document.getElementById("createbtn").addEventListener("click", async () =>{
        const status = document.getElementById("status");
            
        try{
            const resource = await fetch(`${api_url}/games`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Token": api_token
                }
            });

            const rawText = await resource.text();
            console.log("Status:", resource.status, "Body:", rawText);

            const data = JSON.parse(rawText);
            
            const data = await resource.json();
            console.log(data);

            if(resource.ok){
                status.textContent = `Game Created! ID: ${data.id ?? ""}`;
            }else{
                status.textContent = "Error Creating Game";
            }
        }catch(err){
            console.error(err);
            status.textContent = "Server Error";
        }
    })
    </script>
</body>
</html>

