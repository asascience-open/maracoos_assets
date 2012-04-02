<?php
  ob_start();
  session_start();
  require_once("auth.php");
  clearsessionscookies();
  header("location: .");
?>
