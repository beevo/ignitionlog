<?php
class Home extends Controller{
  function __construct(){
    $this->checkAccess('user');
  }

  public function import(){


	$this->_importTournaments();
	$this->index();
  }
  public function index(){
    if (isset($_GET['import'])) {
      $this->_importTournaments();
    }
    $histories = HistoryModel::model()->findAll();
    $this->renderView('home/index.php',array(
      'histories' => $histories
    ));
  }

  private function _importTournaments(){
    $history_dir = 'C:/Ignition/Hand History/14056034/';
    $history_files = scandir($history_dir);
    foreach($history_files as $file){
      if($file == '.' || $file == '..'){
        continue;
      }
      // Helper::log($file);
      $game = array();
      //echo $file . "<br>";
      $explode = explode(' - ',$file);

      $tId = explode('No.',$file);
      if (!isset($tId[1])) {
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
        Helper::log($file);
        Helper::log($buyinExplode);
        $buyinExplode = explode('-',$buyin);
        $buyin = $buyinExplode[0] + $buyinExplode[1];
      }
      $game['buyin'] = $buyin;
      $game['cashout'] = 0;
      mkdir("C:/Ignition/Hand History/logs/$tId/", 0700);
      copy($history_dir."/".$file,"C:/Ignition/Hand History/logs/$tId/$tId.txt");
      $handle = @fopen("C:/Ignition/Hand History/logs/$tId/$tId.txt", "r");
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
