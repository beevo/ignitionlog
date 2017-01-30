<?php
class Admin extends Controller{
  /**
   *   Cehck for authority to the admin panel
   **/
  function __construct(){
    $this->checkAccess('admin');
  }
  /**
   *   Renders the Admin Panel Index Page
   **/
  public function index(){
    // Helper::log("asd");
      $this->renderView('admin/index.php');
  }
}
