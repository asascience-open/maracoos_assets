<?php
  echo file_get_contents('http://db1.charthorizon.com/iw-prod-htdocs/nws/getWWA.php?lon='.$_REQUEST['lon'].'&lat='.$_REQUEST['lat']);
?>
