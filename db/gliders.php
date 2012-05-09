<?php
  header('Content-type: text/plain');
  $dbh = new PDO('sqlite:gliders.sqlite');
  $q = $dbh->exec(file_get_contents('gliders.sql'));
  echo $dbh->errorCode();
?>
