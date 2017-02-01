<?php
class Preflop extends Controller{
  function __construct(){
    $this->checkAccess('user');
  }

  public function import(){
    $actions = $this->_importPreflopActions();
    Helper::log("Actions to import: ". count($actions));
    foreach ($actions as $key => $action) {
      $preflopAction = PreflopActionModel::model()->findByAttributes(array(
        'hand_id' => $action['hand_id'],
        'action'  => $action['action'],
        'position'  => $action['position']
      ));
      if (!$preflopAction) {
        PreflopActionModel::model()->create($action);
      }
    }
    $this->redirect('import');
  }

  public function index(){
    $actions = PreflopActionModel::model()->findAll();
  	$params =	array(
  		'action LIKE' => 'shove%',
  		'AND effective_bet_bbs <' => 10,
  		'AND is_me =' => 1
  	);
  	$actions = PreflopActionModel::model()->search($params);
  	//Helper::log($actions);
  	$ranks = RankModel::model()->findAllByAttributes(array(
  		'chart' => 'Equity Squared'
  	));
  	//Helper::log(count($ranks));
  	$newRanks = array();
  	foreach($ranks as $rank){
  		$percentage = $rank->ranking/count($ranks);
  		$percentage = $percentage*100;
  		$rank->percentage = round($percentage);
  		$newRanks[$rank->hand] = $rank;
  	}

    $params = $this->_getParams();
    $resultRadio = $this->_getResultRadios();
    $betComps = $this->_getComps('e_bet_comp');
    $playerComps = $this->_getComps('player_comp');
    $this->renderView('preflop/index.php',array(
      'ranks' => $newRanks,
      'actions' => $actions,
      'params'  => $params,
      'resultRadio' => $resultRadio,
      'playerComps' => $playerComps,
      'betComps'    => $betComps
    ));
  }

  public function ranks(){
  	$ranks = RankModel::model()->findAllByAttributes(array(
  		'chart' => 'Equity Squared'
  	));
  	$newRanks = array();
  	foreach($ranks as $rank){
  		$percentage = $rank->ranking/count($ranks);
  		$percentage = $percentage*100;
  		$rank->percentage = round($percentage);
  		$newRanks[$rank->hand] = $rank;
  	}
    $this->renderView('preflop/ranks.php',array(
      'ranks' => $newRanks
    ));
  }

  private function _getComps($key){
    $getVal = $this->fetchFromGET($key);
    $eq = (object)array(
      'alias' 	=> 'Equal To',
      'value'		=> 'eq',
      'checked' => ''
    );
    $lt = (object)array(
      'alias' 	=> 'Less Than',
      'value'		=> 'lt',
      'checked' => ''
    );
    $gt = (object)array(
      'alias' 	=> 'Greater Than',
      'value'		=> 'gt',
      'checked' => ''
    );
    $comps = array($eq, $lt, $gt);

    foreach ($comps as $key => $comp) {
      if ($comp->value == $getVal) {
        $comp->checked = 'checked';
      }else{
        $comp->checked = '';
      }
    }
    if (!$getVal) {
      $comps[0]->checked = 'checked';
    }
    return $comps;
  }
  private function _getResultRadios(){
    $getVal = $this->fetchFromGET('is_me');

    $eq = (object)array(
      'alias' 	=> 'Include All Results',
      'value'		=> '-1',
      'checked' => 'checked'
    );
    $lt = (object)array(
      'alias' 	=> 'Include Only My Results',
      'value'		=> 1,
      'checked' => ''
    );
    $gt = (object)array(
      'alias' 	=> 'Exclude My Results',
      'value'		=> 0,
      'checked' => ''
    );

    $resultRadio = array($eq, $lt, $gt);
    foreach ($resultRadio as $key => $radio) {
      if ($radio->value == $getVal) {
        $radio->checked = 'checked';
      }else{
        $radio->checked = '';
      }
    }
    if (!$getVal) {
      $resultRadio[0]->checked = 'checked';
    }
    return $resultRadio;
  }
  private function _getParams(){
    $params = $_GET;
    $defaultParams = array(
      'players' => 6,
      'e_bet' => 10,
      'is_me' => -1,
      'position' => 'UTG'
    );
    foreach ($defaultParams as $key => $param) {
      if (!isset($params[$key])) {
        $params[$key] = $param;
      }
    }

    return $params;
  }
  private function _importPreflopActions(){
    $dir = 'C:/Ignition/Hand History/logs';
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
    $actions = array();

    foreach ($histories as $key => $history) {
      $tournamentId = $history->tournament_id;
      $file = $dir."/$tournamentId/$tournamentId.txt";
      $handle = @fopen($file, "r");
      if ($handle) {

        // if (!in_array($tournamentId,$trouble)) {
        //   continue;
        // }
        // Helper::log("Tournament id: ".$tournamentId);

        while (($buffer = fgets($handle, 4096)) !== false) {
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
            }else if($line->contains('Big Blind : All-in')){
              //TODO keep going
              if (!$bigBlind) {
                $bigBlind = $smallBlind*2;
              }
            }else if($line->contains('Small Blind : All-in')){
              $smallBlind = $bigBlind/2;
            }else if($line->contains('Small Blind : Small blind')){

              $smallBlind = $line->getBlind();
              $bigBlind = $smallBlind*2;
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
              $players[$player]['history_id'] = $history->id;
              $players[$player]['hand'] = $hand;
              $players[$player]['position'] = $player;
              $players[$player]['bbs_remaining'] = $bbRemaining;
              $players[$player]['total_players'] = $totalPlayers;
              $players[$player]['hand_id'] = $handId;
            }else if($line->contains(': Folds')){
              $player = $line->getPlayer();
              unset($curChips[$player]);
            }else if($line->contains('Raises ') || $line->contains('(raise) ')){
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
              $players[$player]['is_me'] = $line->isMe;
              $players[$player]['hand_id'] = $handId;
              $players[$player]['players_to_act'] = count($curChips)-1;
              $players[$player]['big_blind'] =$bigBlind;

              $players[$player]['effective_stack_bbs'] = array_values($curChips)[0]/$bigBlind;
              $curBet = $players[$player]['bet'];
              $lastPlayer = $curPlayer;
              $curPlayer = $player;
              //SAVE PLAYER HERE!
              $actions[] = $players[$player];
              // Helper::log($players[$player]);
            }else if($line->contains(': All-in')){
              $openLimp = false;
              asort($curChips);
              $lastPlayer = $curPlayer;
              $player = $line->getPlayer();
              $shove = $line->getAllIn();
              $players[$player]['action'] .= 'call shove.';
              $players[$player]['bet'] = $shove;
              $players[$player]['effective_bet_bbs'] = $shove/$bigBlind;
              $players[$player]['effective_stack_bbs'] = array_values($curChips)[0]/$bigBlind;
              $players[$player]['last_raise_position'] = $lastPlayer;
              $players[$player]['is_me'] = $line->isMe;
              $players[$player]['hand_id'] = $handId;
              $players[$player]['players_to_act'] = count($curChips)-1;
              $players[$player]['big_blind'] =$bigBlind;
              // Helper::log($players[$player]);
              // Helper::log(">>>>>>> Last Player: $lastPlayer");
              $actions[] = $players[$player];
            }else if($line->contains('(raise)')){
              die("X");
              $openLimp = false;
              asort($curChips);
              $lastPlayer = $curPlayer;
              $player = $line->getPlayer();
              $shove = $line->getRaise();
              $players[$player]['bet'] = $shove;
              $players[$player]['action'] .= 'raise shove.';
              $players[$player]['effective_bet_bbs'] = $shove/$bigBlind;
              $players[$player]['effective_stack_bbs'] = array_values($curChips)[0]/$bigBlind;
              $players[$player]['last_raise_position'] = $lastPlayer;
              $players[$player]['is_me'] = $line->isMe;
              $players[$player]['players_to_act'] = count($curChips)-1;
              $players[$player]['hand_id'] = $handId;
              $players[$player]['big_blind'] =$bigBlind;
              // Helper::log($players[$player]);
              $actions[] = $players[$player];
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
              $players[$player]['bet'] = 0;
              $players[$player]['effective_stack_bbs'] = array_values($curChips)[0]/$bigBlind;
              $players[$player]['effective_bet_bbs'] = $call/$bigBlind;
              $players[$player]['last_raise_position'] = $lastPlayer;
              $players[$player]['is_me'] = $line->isMe;
              $players[$player]['hand_id'] = $handId;
              $players[$player]['players_to_act'] = count($curChips)-1;
              $players[$player]['big_blind'] =$bigBlind;
              // Helper::log($players[$player]);
              $actions[] = $players[$player];
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
    return $actions;
  }
  private function _importRank($chart){
  	$handle = @fopen('C:/xampp/htdocs/ignitionlog/application/controller/ranks.txt', "r");
  	if ($handle) {
  		$rank = 1;
  		while (($buffer = fgets($handle, 4096)) !== false) {

  			$suited = 'o';

  			if($buffer[0] == '('){
  				$buffer = str_replace('(','',$buffer);
  				$buffer = str_replace(')','',$buffer);
  				$suited = 's';
  			}else if($buffer[0] == $buffer[1]){
  				$suited = "";
  			}
  			$buffer = trim($buffer);
  			$buffer .=  $suited;
  			$params = array(
  				'hand' => $buffer,
  				'ranking' => $rank,
  				'chart'	=> 'Equity Squared'
  			);
  			$ranking = RankModel::model()->findAllByAttributes(array(
  				'chart' => $chart,
  				'hand' => $buffer
  			));
  			if(!$ranking){
  				RankModel::model()->create($params);
  				$rank++;
  			}

  		}
  	}
  }
}
