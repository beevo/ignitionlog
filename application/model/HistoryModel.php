<?php
require_once (APP . 'core/model.php');
class HistoryModel extends Model{
  var $tableName = "history";
  var $primaryKey = "id";
  public static function model(){
    return new HistoryModel();
  }
}
