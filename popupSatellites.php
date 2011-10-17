<?php
  $h = '<table class="obsDetails"><tr><td style=\'text-align:center\'><a target=_blank href=\'http://marine.rutgers.edu/cool/sat_data/?nothumbs=0&product=sst_decloud\'>Provider information</a></td></tr></table>';

  echo json_encode(Array('html' => $h));
?>
