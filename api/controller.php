<?php 
//TODO implement play
    class controller{

        public function __construct(private gateway $gateway){
            
        }

        private function getApiToken() : ? string {

            if(!empty($_SERVER["HTTP_X_TOKEN"])){
                return $_SERVER["HTTP_X_TOKEN"];
            }

            //check case sensitive headers
            if(function_exists("getallheaders")){
                $headers = array_change_key_case(getallheaders(), CASE_LOWER);
                return $headers["x-token"] ?? null ;
            }

            return null;
        } 

        public function processRequest(string $method, ?string $resource, ?string $id) : void {

            switch($resource){
                case "users":
                    $this->handleUsers($method, $id !== null ? (int) $id : null);
                    break;
                case "games":
                    $this->handleGames($method, $id !== null ? (int) $id : null);
                    break;
                case "play":
                    $this->handlePlay($method, $id !== null ? (int) $id : null);
                    break;
                case "matchhistory":
                    $this->handleMatchHistory($method, $id !== null ? (int) $id : null);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(["message" => "Resource Not Found"]);
                    break;
            }
        }

        public function handleUsers(string $method, ?int $id) : void {

            $token = $this->getApiToken();

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Missing API token"]);
                return;
            }

            $user = $this->gateway->getUserByToken($token);

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find user with the token used"]);
                return;
            }

            if($id !== null){

                switch($method){
                    //not really needed
                    case "PATCH":

                        $upduser = $this->gateway->updateUser($user);

                        if(!$upduser){
                            http_response_code(403);
                            echo json_encode([
                                "message" => "Couldnt Update User",
                            ]);
                            return;
                        }
                        
                        http_response_code(200);
                        echo json_encode([
                            "message" => "User Updated Successfully",
                            "Updated User" => $upduser
                        ]);
                        break;
                    case "DELETE":

                        $rows = $this->gateway->deleteUser($user["id"]);

                        if($rows === 0){
                            http_response_code(400);
                            echo json_encode([
                                "message" => "Couldnt Delete User",
                                "rows" => $rows
                            ]);
                            return;
                        }

                        http_response_code(200);
                        echo json_encode([
                            "message" => "User Deleted",
                            "rows affected" => $rows
                        ]);
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: PATCH, DELETE");
                        break;
                    
                }
            }else{
                
                switch($method){

                    case "POST":
                       
                        break;
                    case "GET":
                        echo json_encode($user);
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: POST, GET");
                        break;
                }
            }
        }

        public function handleGames(string $method, ?int $id) : void {
            
            $token = $this->getApiToken();

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Missing API token"]);
                return;
            }

            $user = $this->gateway->getUserByToken($token);

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find user with the token used"]);
                return;
            }

            if($id !== null){

                switch($method){

                    case "GET":
                        $game = $this->gateway->getGame($id);
                        
                        if(!$game){

                            http_response_code(404);
                            echo json_encode(["message" => "Game Not Found"]);
                            return;
                        }

                        echo json_encode($game);
                        break;
                    case "PATCH":

                        $game = $this->gateway->getGameByUser($user["uid"]);

                        if($game){
                            http_response_code(422);
                            echo json_encode(["message" => "This User is Arleady In-Game or Waiting"]);
                            return;
                        }

                        $game = $this->gateway->getGame($id);

                        if(!$game){
                            http_response_code(404);
                            echo json_encode(["message" => "Game Not Found"]);
                            return;
                        }

                        if($game["status"] === "playing"){
                            http_response_code(409);
                            echo json_encode([
                                "message" => "This Game is Already In-Progress",
                                "game" => $game
                            ]);
                            return;
                        }

                        if($game["status"] === "finished"){
                            http_response_code(409);
                            echo json_encode([
                                "message" => "This Game is Already Finished",
                                "game" => $game
                            ]);
                            return;
                        }

                        $rows = $this->gateway->joinGame($user["uid"], $id);

                        if($rows === 0){
                            http_response_code(409);
                            echo json_encode(["message" => "Could Not Join Game"]);
                            return;
                        }

                        $updatedGame = $this->gateway->getGame($id);

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Game Joint Successfully",
                            "rows" => $rows,
                            "game" => $updatedGame 
                        ]);

                        break;

                    default:
                        http_response_code(405);
                        header("Allow: GET, PATCH");
                        break;
                }
            }else{

                switch($method){

                    case "POST":

                        $data = (array) json_decode(file_get_contents("php://input"), true);

                        if(empty($data["color"])){
                            http_response_code(422);
                            echo json_encode(["message" => "color is required"]);
                            return;
                        }

                        $game = $this->gateway->getGameByUser($user["uid"]);

                        if($game){
                            http_response_code(422);
                            echo json_encode(["message" => "User is already participating in a game"]);
                            return;
                        }

                        $game = $this->gateway->createGame($user["uid"], $data["color"]);

                        if(!$game){
                            http_response_code(422);
                            echo json_encode(["message" => "Couldnt Create Game"]);
                            return;
                        }

                        http_response_code(201);
                        echo json_encode([
                            "message" => "Game Created Successfully",
                            "game" => $game
                        ]);

                        break;
                
                    case "GET":
                        
                        $games = $this->gateway->getAvailableGames();

                        if(!$games){
                            http_response_code(400);
                            echo json_encode([
                                "message" => "No Available Games",
                            ]);
                            return;
                        }

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Available Games",
                            "games" => $games
                        ]);

                        break;

                    default:
                        http_response_code(405);
                        header("Allow: GET, POST");
                        break;
                }
            }
        }

        public function handleMatchHistory(string $method, ?int $id) : void {

            $token = $this->getApiToken();

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Missing API token"]);
                return;
            }

            $user = $this->gateway->getUserByToken($token);

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find user with the token used"]);
                return;
            }

            if($id !== null){
                
                switch($method){
                    
                    case "GET":
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: GET");
                        break;
                }
            }else{

                switch($method){
                    case "GET":

                        $games = $this->gateway->getMatchHistory();

                        if(!$games){
                            http_response_code(400);
                            echo json_encode([
                                "message" => "No Games Recorded"
                            ]);
                            return;
                        }

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Match History",
                            "games" => $games
                        ]);

                        break;
                    default:
                        http_response_code(405);
                        header("Allow: GET");
                        break;
                }
            }
        }

        public function handlePlay(string $method, ?int $id) : void{

            $token = $this->getApiToken();

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Missing API token"]);
                return;
            }

            $user = $this->gateway->getUserByToken($token);

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find user with the token used"]);
                return;
            }

            if($id !== null){

                switch($method){
                    case "PATCH"://forfeit
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: PATCH");
                        break;
                }

            }else{
                switch($method){
                    case "POST"://play move

                        $data = (array) json_decode(file_get_contents("php://input"), true);

                        $game = $this->gateway->getGameByUser($user["uid"]);


                        break;
                    default:
                        http_response_code(405);
                        header("Allow: POST");
                        break;
                }

            }
        }
    }
?>