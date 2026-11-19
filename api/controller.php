<?php 
//TODO CREATE GAME
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
                    break;
                case "matchhistory":
                    break;
                default:
                    break;
            }
        }

        public function handleUsers(string $method, ?int $id) : void {

            $token = $this->getApiToken();

            if($id !== null){

                switch($method){
                    //not really needed
                    case "PATCH":

                        if(!$token){
                            http_response_code(401);
                            echo json_encode(["message" => "Missing API token"]);
                            return;
                        }
                        $data = (array) json_decode(file_get_contents("php://input"), true);
                        $user = $this->gateway->getUserByToken($token);

                        if($user === null){
                            http_response_code(404);
                            echo json_encode("User Not Found");
                            return;
                        }

                        $upduser = $this->gateway->updateUser($user);

                        if($upduser === null){
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

                        if(!$token){
                            http_response_code(401);
                            echo json_encode(["message" => "Missing API token"]);
                            return;
                        }
                        $data = (array) json_decode(file_get_contents("php://input"), true);
                        $user = $this->gateway->getUserByToken($token);

                        if($user === null){
                            http_response_code(404);
                            echo json_encode("User Not Found");
                            return;
                        }

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
                        $data = (array) json_decode(file_get_contents("php://input"), true);
                        
                        break;
                    case "GET":
                        $users = $this->gateway->getAllUsers();

                        if(empty($users)){
                            http_response_code(404);
                            echo json_encode([
                                "message" => "No Users Found",
                            ]);
                            return;
                        }

                        echo json_encode($users);
                        break;
                    default:
                        break;
                }
            }
        }

        public function handleGames(string $method, ?int $id) : void {

            $token = $this->getApiToken();


            if($id !== null){

                switch($method){

                    case "GET":
                        $game = $this->gateway->getGame($id);
                        
                        if($game === null){

                            http_response_code(404);
                            echo json_encode(["message" => "Game Not Found"]);
                            return;
                        }

                        echo json_encode($game);
                        break;
                }
            }else{

                switch($method){

                    case "POST":

                        if(!$token){
                            http_response_code(401);
                            echo json_encode(["message" => "Missing API token"]);
                            return;
                        }

                        $user = $this->gateway->getUserByToken($token);

                        if($user === null){
                            http_response_code(404);
                            echo json_encode("Didnt find user with the token used");
                            return;
                        }

                        $data = (array) json_decode(file_get_contents("php://input"), true);

                        if(empty($data["uid"]) || empty($data["color"])){
                            http_response_code(422);
                            echo json_encode(["message" => "userid and color are required"]);
                            return;
                        }

                        $game = $this->gateway->getGameByUser($data["uid"]);

                        if(($game) !== null){
                            http_response_code(422);
                            echo json_encode(["message" => "User is already participating in a game"]);
                            return;
                        }

                        $game = $this->gateway->createGame($data);

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
                }
            }
        }
    }
?>