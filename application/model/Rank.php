<?php
require_once (APP . 'core/model.php');
class RankModel extends Model{
  var $tableName = "rank";
  var $primaryKey = "id";
  public static function model(){
    return new RankModel();
  }
}
