<?php
  $dbh = sqlite_open('gliders.db',0666,$error);
  sqlite_exec($dbh,file_get_contents('gliders.sql'));
?>
