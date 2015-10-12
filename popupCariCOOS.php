<?php
  include_once('util.php');
  $url = sprintf(
     "http://gyre.umeoce.maine.edu/data/gomoos/buoy/archive/%s/ingest/%s_currents_10_ingest.txt"
    ,$_REQUEST['id']
    ,$_REQUEST['id']
  );
  $url = 'http://localhost/assets/PR1_currents_10_ingest.txt';

  $h = getCariCOOS(
     $url
    ,''
    ,false
  );
  $o = $h['table']['data'];
  $t = $h['table']['t'];

  if (count($o) == 0) {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><th style="text-align:center">No recent observations</th></tr></table>'));
  }
  else {
    array_unshift($o,sprintf("<tr><td colspan=2 style='text-align:center'><b>%s-%02d</b></td></tr>",date('M d G:i e',$t - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://buoybay.noaa.gov/'>More observations and station information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }

?>
