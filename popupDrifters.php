<?php
  $u = substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'&u=')+3);

  $o = array(
    sprintf("<tr><td colspan=2 style='text-align:center'><b>%s</b></td></tr>",$_REQUEST['t'])
  );
  if (isset($_REQUEST['u'])) {
    array_push($o,"<tr><td colspan=2 style='text-align:center'><br>Archived data is served via OPeNDAP. For details, call 508-566-4080 or email james.manning@noaa.gov.<br><br></td></tr>");
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://www.nefsc.noaa.gov/drifter'>More observations and drifter information</a></td></tr>");
  }

  echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
?>
