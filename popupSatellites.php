<?php
  $h = '<table class="obsDetails"><tr><td style=\'text-align:center\'><a target=_blank href=\'http://marine.rutgers.edu/cool/sat_data/?nothumbs=0&product=sst_decloud\'>Provider information</a></td></tr></table>';
  if (isset($_REQUEST['descrip']) && $_REQUEST['descrip'] != '') {
    $p = explode(',',$_REQUEST['descrip']);
    $tr = array('<tr><td style=\'text-align:center\'>Provider information</td></tr>');
    for ($i = 0; $i < count($p); $i++) {
      array_push($tr,'<tr><td style=\'text-align:center\'><a target=_blank href=\''.$p[$i].'\'>'.$p[$i].'</a></td></tr>');
    }
    $h = '<table class="obsDetails"><tr>'.implode('</tr><tr>',$tr).'</tr></table>';
  }

  echo json_encode(Array('html' => $h));
?>
