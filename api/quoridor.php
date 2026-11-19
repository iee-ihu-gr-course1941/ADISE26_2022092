<?php 
    class quoridor {

        private array $pawns = [];
        private array $hWalls = [];//Horizontal Walls
        private array $vWalls = [];//Vertical Walls
        private array $wallsRemaining = [];
        private string $activeColor;

        public function __construct(string $state = "e1/e9 - - r 10/10"){
            $this->loadFromState($state);
        }

        public function loadFromState(string $state) : void {

            $parts = explode(" ", trim($state));

            if(count($parts) !== 5){
                throw new InvalidArgumentException("Invalid Quoridor Format");
            }

            [$pawnStr, $hWallStr, $vWallStr, $color, $wallCounts] = $parts;

            $pawnParts = explode("/", $pawnStr);

            if(count($pawnParts) !== 2){
                throw new InvalidArgumentException(("Invalid Pawn Coordinates"));
            }

            $this->pawns["r"] = $this->squareToCoordinates($pawnParts[0]);
            $this->pawns["b"] = $this->squareToCoordinates($pawnParts[1]);

            $this->vWalls = [];

            if($vWallStr !== "-"){
                foreach(explode(",", $vWallStr) as $sq){
                    $this->vWalls[] = $this->squareToCoordinates($sq);
                }
            }

            if(!in_array($color, ["r", "b"], true)){
                throw new InvalidArgumentException("Invalid Active Color: must be 'r' or 'b'");
            }

            $this->activeColor = $color;

            $counts = explode("/", $wallCounts);

            if(count($counts) !== 2){
                throw new InvalidArgumentException("Invalid Wall Counts");
            }

            $this->wallsRemaining["r"] = (int) $counts[0];
            $this->wallsRemaining["b"] = (int) $counts[0];
        }

        public function toState() : string {
            $redPos = $this-> coordinatesToSquare($this->pawns["r"][0], $this->pawns["r"][1]);
            $bluePos = $this-> coordinatesToSquare($this->pawns["b"][0], $this->pawns["b"][1]);

            $hStr = empty($this->hWalls) ? "-" : implode(",", array_map(
                fn($w) => $this->coordinatesToSquare($w[0], $w[1]),
                $this->hWalls
            ));

            $vStr = empty($this->hWalls) ? "-" : implode(",", array_map(
                fn($w) => $this->coordinatesToSquare($w[0], $w[1]),
                $this->hWalls
            ));

            return sprintf(
                "%s/%s %s %s %s %d/%d",
                $redPos,
                $bluePos,
                $hStr,
                $vStr,
                $this->activeColor,
                $this->wallsRemaining["r"],
                $this->wallsRemaining["b"]
            );
        }

        public function move(string $notation) : bool {
            $notation = strtolower(trim($notation));

            if(strlen($notation) === 2){
                return $this->movePawn($notation);
            }

            if(strlen($notation) === 3 && in_array($notation[2], ["h", "v"], true)){
                return $this->placeWall(substr($notation, 0, 2), $notation[2]);
            }

            return false;
        }

        public function movePawn(string $to) : bool {
            
            if($this->isGameOver()){
                return false;
            }

            [$toRow, $toCol] = $this->squareToCoordinates($to);

            if(!$this->isInsideBoard($toRow, $toCol)){
                return false;
            }

            [$fromRow, $fromCol] = $this->pawns[$this->activeColor];
            $opponent = ($this->activeColor === "r") ? "b" : "r";
            [$oppRow, $oppCol] = $this->pawns[$opponent];

            if((($toRow === $fromRow && $toCol === $fromCol) || ($toRow === $oppRow && $toCol === $oppCol))){
                return false;
            }

            $dRow = $toRow - $fromRow;
            $dCol = $toCol - $fromCol;
            $absRow = abs($dRow);
            $absCol = abs($dCol);

            $isValidMove = false;

            //step 1 square
            if(($absRow +$absCol) === 1){
                if(!$this->isBlockedByWall($fromRow, $fromCol, $toRow, $toCol)){
                    $isValidMove = true;
                }   
            }//step straight over opponent
            elseif(($absRow === 2 && $dCol === 0) || ($absCol === 2 && $dRow === 0)){
                $midRow = $fromRow + ($dRow / 2);
                $midCol = $fromCol + ($dCol / 2);

                if($midRow === $oppRow && $midCol === $oppCol){
                    if(
                        !$this->isBlockByWall($fromRow, $fromCol, $midRow, $midCol) &&
                        !$this->isBlockByWall($midRow, $midCol, $toRow, $toCol)
                    ){
                        $isValidMove = true3;
                    }
                }
            }//step diagonal over opponent
            elseif($absRow === 1 && $absCol === 1){
                
                if($oppRow === 1 && $absCol === 1){
                    if($oppRow === ($fromRow + $dRow) && $oppCol === $fromCol){
                        $behindOppRow = $oppRow + $dRow;
                        $straightBlocked = !$this->isInsideBoard($behindOppRow, $oppCol) ||
                                            $this->isBlockedByWall($oppRow, $oppCol, $behindOppRow, $oppCol)
                        ;

                        if(
                            $straightBlocked &&
                            !$this->isBlockedByWall($fromRow, $fromCol, $oppRow, $oppCol) &&
                            !$this->isBlockedByWall($oppRow, $oppCol, $toRow, $toCol)
                        ){
                            $isValidMove = true;
                        }
                    }elseif ($oppRow === $fromRow && $oppCol === ($fromCol + $dCol)){
                        $behindOppCol = $oppCol + $dCol;
                        $straightBlocked = !$this->isInsideBoard($oppRow, $behindOppCol) ||
                                            $this->isBlockedByWall($oppRow, $oppCol, $oppRow, $behindOppCol)
                        ;

                        if (
                            $straightBlocked &&
                            !$this->isBlockedByWall($fromRow, $fromCol, $oppRow, $oppCol) &&
                            !$this->isBlockedByWall($oppRow, $oppCol, $toRow, $toCol)
                        ){
                            $isValidMove = true;
                        }
                    }   
                }
            }

            if(!$isValidMove){
                return false;
            }

            $this->pawns[$this->activeColor] = [$toRow, $toCol];
            $this->switchTurn();

            return true;
        }
    }
?>