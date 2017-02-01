<?php
require_once (APP . 'core/model.php');
class PreflopActionModel extends Model{
  var $tableName = "preflop_actions";
  var $primaryKey = "id";
  public static function model(){
    return new PreflopActionModel();
  }
}
