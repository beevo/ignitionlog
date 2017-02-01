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
      $this->content = str_replace('[ME] ','',$this->content);
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
