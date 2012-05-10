<?php
  $tr = array();
  if ($_REQUEST['name'] != '') {
    array_push($tr,sprintf("<tr><td><b>%s</b></td><td>%s</td></tr>",'Name',$_REQUEST['name']));
  }
  if ($_REQUEST['owner'] != '') {
    array_push($tr,sprintf("<tr><td><b>%s</b></td><td>%s</td></tr>",'Owner',$_REQUEST['owner']));
  }
  if ($_REQUEST['model'] != '') {
    array_push($tr,sprintf("<tr><td><b>%s</b></td><td>%s</td></tr>",'Model',$_REQUEST['model']));
  }

  $h = '<table class="obsDetails"><tr>'.implode('</tr><tr>',$tr).'</tr></table>';

  echo json_encode(Array('html' => $h));
?>
