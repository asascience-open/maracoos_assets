<?php
  include_once('util.php');

  date_default_timezone_set('UTC');
  $t = ''; // assume same time for all obs
  $o = Array();

  $dBegin = date('Y-m-d',time() - 60 * 60 * (24 * 7 + 1));
  $json = json_decode(file_get_contents('http://pro-bob.com/data/api/records?min_time='.$dBegin.'&format=arrays&errorcode=False&buoy='.$_REQUEST['id']),true);

  foreach ($json[$_REQUEST['id']] as $var) {
    $t = strtotime(array_pop($var['times']));
    $n = $var['variable']['name'];
    $a = convertUnits(array_pop($var['values']),$var['variable']['unit'],$_REQUEST['uom'] == 'english');
    $u = $a[0]["uom"];
    $v = $a[0]["val"];
    $dBegin = date('Y-m-d',time() - 60 * 60 * (24 * 7 + 1));
    $uEscape = str_replace('"','\\"',"graph.php?station=".$_REQUEST['id']."&name=$n&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom'].'&cat='.$a[0]['cat']."&BOB&startDt=$dBegin");
    $extra = '';
    if (count($a) == 2) {
      $extra = "<br><a href='javascript:showObsTimeseries([\"".str_replace('graph.php?','graph.php?uomB&',$uEscape)."\"])'><img src='img/graph.png' width=10 height=10></a> ".$a[1]["val"].' '.$a[1]["uom"];
    }
    if ($v != '') {
      array_push($o,sprintf("<tr><td><b>%s</b></td><td><a href='javascript:showObsTimeseries([\"$uEscape\"])'><img src='img/graph.png' width=10 height=10></a> $v $u$extra</td></tr>",$n));
    }
  }

  if (count($o) == 0) {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><th style="text-align:center">No recent observations</th></tr></table>'));
  }
  else {
    array_unshift($o,sprintf("<tr><td colspan=2 style='text-align:center'><b>%s-%02d</b></td></tr>",date('M d G:i e',$t - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://pro-bob.com/'>More observations and station information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }
?>
