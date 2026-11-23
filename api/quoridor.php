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

            $this->hWalls = [];
            if ($hWallStr !== "-") {
                foreach (explode(",", $hWallStr) as $sq) {
                    $this->hWalls[] = $this->squareToCoordinates($sq);
                }
            }

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
            $this->wallsRemaining["b"] = (int) $counts[1];
        }

        public function toState() : string {
            $redPos = $this-> coordinatesToSquare($this->pawns["r"][0], $this->pawns["r"][1]);
            $bluePos = $this-> coordinatesToSquare($this->pawns["b"][0], $this->pawns["b"][1]);

            $hStr = empty($this->hWalls) ? "-" : implode(",", array_map(
                fn($w) => $this->coordinatesToSquare($w[0], $w[1]),
                $this->hWalls
            ));

            $vStr = empty($this->vWalls) ? "-" : implode(",", array_map(
                fn($w) => $this->coordinatesToSquare($w[0], $w[1]),
                $this->vWalls
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

            try{
                if(strlen($notation) === 2){
                    return $this->movePawn($notation);
                }

                if(strlen($notation) === 3 && in_array($notation[2], ["h", "v"], true)){
                    return $this->placeWall(substr($notation, 0, 2), $notation[2]);
                }

            }catch (InvalidArgumentException){
                return false;
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
                        !$this->isBlockedByWall($fromRow, $fromCol, $midRow, $midCol) &&
                        !$this->isBlockedByWall($midRow, $midCol, $toRow, $toCol)
                    ){
                        $isValidMove = true;
                    }
                }
            }//step diagonal over opponent
            elseif($absRow === 1 && $absCol === 1){

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

                    if(
                        $straightBlocked &&
                        !$this->isBlockedByWall($fromRow, $fromCol, $oppRow, $oppCol) &&
                        !$this->isBlockedByWall($oppRow, $oppCol, $toRow, $toCol)
                    ){
                        $isValidMove = true;
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

        public function placeWall(string $square , string $orientation) : bool {

            if($this->isGameOver()){
                return false;
            }

            if($this->wallsRemaining[$this->activeColor] <= 0){
                return false;
            }

            [$row, $col] = $this->squareToCoordinates($square);

            if($row < 1 || $row > 8 || $col < 0 || $col > 7){
                return false;
            }

            if($orientation === "h"){
                foreach($this->hWalls as [$wr, $wc]){
                    if($wr === $row && abs($wc - $col) <=1){
                        return false;
                    }
                }
                foreach($this->vWalls as [$wr, $wc]){
                    if($wr === $row && $wc === $col){
                        return false;
                    }
                }

                $savedWalls = $this->hWalls;
                $this->hWalls[] = [$row, $col];

                if(!$this->hasPathToGoal("r") || !$this->hasPathToGoal("b")){
                    $this->hWalls = $savedWalls;
                    return false;
                }
            }elseif($orientation === "v"){
                foreach($this->vWalls as [$wr, $wc]){
                    if($wc === $col && abs($wr - $row) <= 1){
                        return false;
                    }
                }
                foreach ($this->hWalls as [$wr, $wc]) {
                    if ($wr === $row && $wc === $col) {
                        return false;
                    }
                }

                $savedWalls = $this->vWalls;
                $this->vWalls[] = [$row, $col];

                if (!$this->hasPathToGoal('r') || !$this->hasPathToGoal('b')) {
                    $this->vWalls = $savedWalls;
                    return false;
                }
            }else{
                return false;
            }

            $this->wallsRemaining[$this->activeColor]--;
            $this->switchTurn();

            return true;
        }

        private function squareToCoordinates(string $square): array {
            if (!preg_match("/^[a-i][1-9]$/i", $square)){
                throw new InvalidArgumentException("Invalid square notation: $square");
            }

            $file = ord(strtolower($square[0])) - ord("a");
            $rank = (int) $square[1];
            $row = 9 - $rank;

            return [$row, $file];
        }

        private function isBlockedByWall(int $r1, int $c1, int $r2, int $c2): bool {
            if ($c1 === $c2) {
                $lowerRow = max($r1, $r2);
                foreach ($this->hWalls as [$wr, $wc]) {
                    if ($wr === $lowerRow && ($wc === $c1 || $wc + 1 === $c1)) {
                        return true;
                    }
                }
            }

            if ($r1 === $r2) {
                $leftCol = min($c1, $c2);
                foreach ($this->vWalls as [$wr, $wc]) {
                    if ($wc === $leftCol && ($wr === $r1 || $wr - 1 === $r1)) {
                        return true;
                    }
                }
            }

            return false;
        }

        private function hasPathToGoal(string $color): bool {
            $goalRow = ($color === "r") ? 0 : 8;
            [$startRow, $startCol] = $this->pawns[$color];

            if ($startRow === $goalRow) {
                return true;
            }

            $visited = array_fill(0, 9, array_fill(0, 9, false));
            $queue = [[$startRow, $startCol]];
            $visited[$startRow][$startCol] = true;

            $head = 0;
            while ($head < count($queue)) {
                [$r, $c] = $queue[$head++];

                foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dr, $dc]) {
                    $nr = $r + $dr;
                    $nc = $c + $dc;

                    if ($this->isInsideBoard($nr, $nc) && !$visited[$nr][$nc]) {
                        if (!$this->isBlockedByWall($r, $c, $nr, $nc)) {
                            if ($nr === $goalRow) {
                                return true;
                            }
                            $visited[$nr][$nc] = true;
                            $queue[] = [$nr, $nc];
                        }
                    }
                }
            }

            return false;
        }

        public function coordinatesToSquare(int $row, int $col) : string {
            $file = chr(ord("a") + $col);
            $rank = 9 - $row;

            return "$file$rank";
        }

        private function isInsideBoard(int $row, int $col): bool {
            return $row >= 0 && $row < 9 && $col >= 0 && $col < 9;
        }

        private function switchTurn() : void {
            $this->activeColor = ($this->activeColor === "r") ? "b" : "r";
        }

        public function getActiveColor() : string {
            return $this->activeColor;
        }

        public function isGameOver() : bool {
            return $this->getWinner() !== null;
        }

        public function getWinner() : ?string {
            if($this->pawns["r"][0] === 0){
                return "r";
            }
            if($this->pawns["b"][0] === 8){
                return "b";
            }

            return null;
        }
    }
?>