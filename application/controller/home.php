<?php

class Line{
  var $content = "";
  var $isMe = false;
  public function between($start, $end){
    $string = ' ' . $this->content;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
  }
  public function contains($search){
    if(strpos($this->content, $search) !== false){
      return true;
    }else{
      return false;
    }
  }
  var $ignoreLine = array(
    'Table enter user',
    'Draw for dealer',
  );
  public function __construct($param) {
    $this->content = $param;
    $ignoreLine = false;
    foreach ($this->ignoreLine as $key => $ignore) {
      $ignoreLine = $this->contains($ignore);
    }
    if ($ignoreLine) {
      $this->content = "";
    }
    if ($this->contains('[ME]')) {

      $this->isMe = true;
      $this->content = str_replace('[ME]','',$this->content);
    }
  }
  public function getPlayer(){
    $content = $this->content;
    $this->content = '>'.$this->content;
    $player = $this->between('>',' :');
    $this->content = $content;
    return trim($player);
  }
  public function translateHand(){
    $hand = $this->between('[',']');
    $faceRanks = array(
      'T' => 1,
      'J' => 2,
      'Q' => 3,
      'K' => 4,
      'A' => 5
    );
    $suite = "o";
    if ($hand[1] == $hand[4]) {
      $suite = "s";
    }
    $card1 = $hand[0];
    $card2 = $hand[3];
    if ($card1 == $card2) {
      $suite = "";
    }
    if (is_numeric($card1) && !is_numeric($card2)) {
      $hand = $card2 . $card1 . $suite;
    }else if(!is_numeric($card1) && is_numeric($card2)) {
      $hand = $card1 . $card2 . $suite;
    }else if(!is_numeric($card1) && !is_numeric($card2)){
      if ($faceRanks[$card1] < $faceRanks[$card2]) {
        $hand = $card2 . $card1 . $suite;
      }else{
        $hand = $card1 . $card2 . $suite;
      }
    }else{
      if ($card1 > $card2) {
        $hand = $card1 . $card2 . $suite;
      }else{
        $hand = $card2 . $card1 . $suite;
      }
    }
    return $hand;
  }

  public function getChips(){
    $chips = $this->between('('," in chips)");
    $chips = str_replace(',','',$chips);
    return $chips;
  }

  public function getBlind(){
    $content = $this->content;
    $this->content = $this->content . "<";
    $blind = $this->between('blind ','<');
    $this->content = $content;
    return $blind;
  }

  public function getRaise(){

    $content = $this->content;
    $this->content = $this->content . "<";
    $raise = $this->between('to ',"<");
    $raise = str_replace(',','', $raise);
    $this->content = $content;
    return $raise;
  }
  public function getAllIn(){

    $content = $this->content;
    $this->content = $this->content . "<";
    $raise = $this->between('All-in ',"<");
    $raise = str_replace(',','', $raise);
    $this->content = $content;
    return $raise;
  }
  public function getCall(){

    $content = $this->content;
    $this->content = $this->content . "<";
    $raise = $this->between('Call ',"<");
    $raise = str_replace(',','', $raise);
    $this->content = $content;
    return $raise;
  }
}
class Home extends Controller{
  function __construct(){
    $this->checkAccess('user');
  }

  public function import(){

    $dir = 'C:/Ignition/Hand History/14056034';
    $histories = HistoryModel::model()->findAllByAttributes(array(
      'type' => 'STT'
    ));
    $players = array();
    $curHand = "";
    $bigBlind = 0;
    $smallBlind = 0;
    $betBlinds = 0;
    $allIn = false;
    $totalPlayers = 0;
    $previousBet = 0;
    $curPlayer = '';
    $lastPlayer = '';
    $curChips = array();
    $openLimp = true;
    $postflop = false;
    foreach ($histories as $key => $history) {
      $file = $dir."/".$history->tournament_id.".txt";
      $handle = @fopen($file, "r");
      if ($handle) {
        while (($buffer = fgets($handle, 4096)) !== false) {
          // Helper::log($buffer);
          $line = new Line($buffer);
          if ($line->content) {
            if ($line->contains('Ignition Hand #')) {
              $handId = $line->between('Hand #',':');
              $postflop = false;
              $players = array();
              $curHand = "";
              $bigBlind = 0;
              $smallBlind = 0;
              $betBlinds = 0;
              $allIn = false;
              $totalPlayers = 0;
              $previousBet = 0;
              $curPlayer = '';
              $lastPlayer = '';
              $curChips = array();
              $openLimp = true;

            }
            if ($line->contains('*** FLOP ***')) {
              $postflop = true;
            }
            if ($postflop) {
              continue;
            }

            if($line->contains(': Big blind ')){
              $bigBlind = $line->getBlind();
            }else if($line->contains('Small Blind : Small blind')){
              $smallBlind = $line->getBlind();
            }else if($line->contains(' in chips)')){
              $totalPlayers++;
              $player = $line->between(': ',' (');
              $chips = $line->getChips();
              $curChips[trim($player)] = $chips;
              $players[trim($player)] = array(
                'chips' => $chips,
                'action'    => ''
              );
            }else if($line->contains('Card dealt to a spot')){

              $hand = $line->translateHand();
              $player = $line->getPlayer();
              if ($players[$player]['chips']) {
                $bbRemaining = $players[$player]['chips']/$bigBlind;
              }
              $players[$player]['hand'] = $hand;
              $players[$player]['position'] = $player;
              $players[$player]['bbs_remaining'] = $bbRemaining;
              $players[$player]['total_players'] = $totalPlayers;
              $players[$player]['hand_id'] = $handId;
            }else if($line->contains(': Folds')){
              $player = $line->getPlayer();
              unset($curChips[$player]);
            }else if($line->contains('Raises ')){
              $openLimp = false;
              asort($curChips);
              // $line->content = '>'.$line->content;
              // Helper::log("blinds: ".$smallBlind . "/" . $bigBlind);
              $player = $line->getPlayer();
              $raise = $line->getRaise();
              $stackPercentage = $raise/$players[$player]['chips'];

              if ($stackPercentage > 0.8) {
                $players[$player]['action'] .= 'shove.';
              }else{
                if ($lastPlayer) {
                  $players[$player]['action'] .= 'raise.';
                }else{
                  $players[$player]['action'] .= 'open bet.';
                }
              }
              $players[$player]['bet'] = ($raise);
              $players[$player]['effective_bet_bbs'] = $players[$player]['bet']/$bigBlind;
              $players[$player]['last_raise_position'] = $lastPlayer;
              $players[$player]['isMe'] = $line->isMe;
              $players[$player]['hand_id'] = $handId;

              $players[$player]['effective_stack_bbs'] = array_values($curChips)[0]/$bigBlind;
              $curBet = $players[$player]['bet'];
              $lastPlayer = $curPlayer;
              $curPlayer = $player;
              //SAVE PLAYER HERE!
              Helper::log($players[$player]);
            }else if($line->contains(': All-in')){
              $openLimp = false;
              asort($curChips);
              $lastPlayer = $curPlayer;
              $player = $line->getPlayer();
              $shove = $line->getAllIn();
              $players[$player]['action'] .= 'call shove.';
              $players[$player]['effective_bet_bbs'] = $shove/$bigBlind;
              $players[$player]['effective_stack_bbs'] = array_values($curChips)[0]/$bigBlind;
              $players[$player]['last_raise_position'] = $lastPlayer;
              $players[$player]['isMe'] = $line->isMe;
              $players[$player]['hand_id'] = $handId;
              Helper::log($players[$player]);
              // Helper::log(">>>>>>> Last Player: $lastPlayer");
            }else if($line->contains(' Call ')){
              asort($curChips);
              $player = $line->getPlayer();
              $call = $line->getCall();
              if ($call == $bigBlind || $call < $bigBlind) {
                if ($openLimp) {
                  $players[$player]['action'] .= 'open limp.';
                }else{
                  $players[$player]['action'] .= 'call.';
                }
              }else{
                $players[$player]['action'] .= 'call.';
              }
              $players[$player]['effective_stack_bbs'] = array_values($curChips)[0]/$bigBlind;
              $players[$player]['effective_bet_bbs'] = $call/$bigBlind;
              $players[$player]['last_raise_position'] = $lastPlayer;
              $players[$player]['isMe'] = $line->isMe;
              $players[$player]['hand_id'] = $handId;
              Helper::log($players[$player]);
            }
          }
        }
        if (!feof($handle)) {
          die("error 1");
        }
        fclose($handle);
      }else{
        Helper::log("X");
      }
    }
  }
  public function index(){
    $dir = 'C:/Ignition/Hand History';
    $histories = scandir($dir);
    $games = array();
    foreach($histories as $history){
      if($history != "." && $history != ".."){
        $history_dir = 'C:/Ignition/Hand History/' . $history;
        $history_files = scandir($history_dir);
        foreach($history_files as $file){
          if($file == '.' || $file == '..'){
            continue;
          }

          $game = array();
          //echo $file . "<br>";
          $explode = explode(' - ',$file);

          $tId = explode('No.',$file);
          if (!isset($tdId[1])) {
            continue;
          }
          $tId = $tId[1];
          $tId = str_replace('.txt',"",$tId);
          if (!$tId) {
            continue;
          }
          $findHistory = HistoryModel::model()->findByAttributes(array(
            'tournament_id' => $tId
          ));
          if ($findHistory) {
            continue;
          }
          $date = substr($explode[0], 2, 8);
          $game['date'] = $date;
          $game['type'] = trim($explode[2]);
          $game['name'] = trim($explode[3]);
          if (strpos($game['name'], 'Satellite To Any') !== false) {
            $game['name'] .= "-" . $explode[4] . ", " . $explode[5];
            $game['game'] = trim($explode[7]);
            $game['buyin'] = trim($explode[6]);
          }else{
            $game['game'] = trim($explode[5]);
            $game['buyin'] = trim($explode[4]);
          }

          $game['tournament_id'] = $tId;
          $date = $game['date'];

          $date = substr($game['date'], 4, 2) .
            "-" . substr($game['date'], 5, 2) .
            "-" . substr($game['date'], 0, 4);
          $game['date'] = $date;
          $buyin = $game['buyin'];
          $buyin = str_replace('$','',$buyin);
          $game['notes'] = "";

          if(strpos($buyin,'TT') !== false){
            $buyin = str_replace('TT','',$buyin);
            $buyinExplode = explode('-',$buyin);
            $buyin = $buyinExplode[0] + $buyinExplode[1];
            $game['notes'] = "$".$buyin . " Ticket";
          }else{
            $buyinExplode = explode('-',$buyin);
            $buyin = $buyinExplode[0] + $buyinExplode[1];
          }
          $game['buyin'] = $buyin;
          $game['cashout'] = 0;
          copy($history_dir."/".$file,$history_dir."/".$tId.".txt");
          $handle = @fopen($history_dir."/".$tId.".txt", "r");
          if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
              $winnerString = "[ME] : Prize Cash";
              if(strpos($buffer, $winnerString) !== false){
                $prizeExplode = explode('$',$buffer);
                $prize = $prizeExplode[1];
                $prize = str_replace(']','',$prize);
                $game['cashout'] = $prize;
              }
              $rankString = "[ME] : Ranking";
              if(strpos($buffer,$rankString) != false){
                $rankExplode = explode('Ranking ',$buffer);
                $rank = $rankExplode[1];
                $game['rank'] = $rank;
              }
            }
            if (!feof($handle)) {
              die("error 1");
            }
            fclose($handle);
          }
          $games[] = $game;
          HistoryModel::model()->create($game);
        }
      }
    }
    $histories = HistoryModel::model()->findAll();
    $this->renderView('home/index.php',array(
      'histories' => $histories
    ));
  }

}
