<?php
  $u = substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'&u=')+3);

  $o = array(
    sprintf("<tr><td colspan=2 style='text-align:center'><b>%s</b></td></tr>",$_REQUEST['t'])
    ,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='".$u."'>More observations and glider information</a></td></tr>"
  );

  echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
?>
