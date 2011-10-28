<?php
  $o = array(
    sprintf(
       "<tr><td colspan=2 style='text-align:center'><b>%s - %s</b></td></tr>"
      ,$_REQUEST['start'] != '' ? date('M j, Y',strtotime($_REQUEST['start'])) : ''
      ,$_REQUEST['end'] != '' ? date('M j, Y',strtotime($_REQUEST['end'])) : 'currently active'
    )
  );

  echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
?>
