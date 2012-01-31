<?php
  $o = array(
    sprintf("<tr><td colspan=2 style='text-align:center'><b>%s</b></td></tr>",$_REQUEST['t'])
  );
  if (isset($_REQUEST['u']) && $_REQUEST['u'] != '') {
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='".$_REQUEST['u']."'>More observations and glider information</a></td></tr>");
  }

  echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
?>
