<?php
  $o = array(
    sprintf(
       "<tr><td colspan=2 style='text-align:center'><b>%s</b></td></tr>"
      ,$_REQUEST['description']
    )
    ,'<tr><td>&nbsp;</td></tr>'
    ,sprintf(
       "<tr><td colspan=2 style='text-align:center'>%s - %s</td></tr>"
      ,$_REQUEST['start'] != '' ? date('M j, Y',strtotime($_REQUEST['start'])) : ''
      ,$_REQUEST['end'] != '' ? date('M j, Y',strtotime($_REQUEST['end'])) : 'currently active'
    )
    ,sprintf(
       "<tr><td colspan=2 style='text-align:center'>species : %s</td></tr>"
      ,$_REQUEST['species']
    )
    ,sprintf(
       "<tr><td colspan=2 style='text-align:center'>model : %s</td></tr>"
      ,$_REQUEST['model']
    )
    ,'<tr><td>&nbsp;</td></tr>'
    ,"<tr><td colspan=2 style='text-align:center'><font color=gray>Visit the <a target=_blank href='http://www.glfc.org/telemetry/overview.php'>GLATOS home page</a> for more information.</font></td></tr>"
  );

  echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
?>
