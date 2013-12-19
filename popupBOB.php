<?php
  include_once('util.php');

  date_default_timezone_set('UTC');
  $t = ''; // assume same time for all obs
  $o = Array();

  $dbh = new PDO('sqlite:bob.db');
  $sql = <<<EOSQL
select distinct
   station.id
  ,station.name
  ,station.lon
  ,station.lat
  ,obs.var
  ,obs.uom
  ,obs.t
  ,obs.val
from
   obs
  ,(
    select
      station
     ,var
     ,max(t) as t
    from
      obs
    group by
      station
     ,var
  ) as top_obs
  ,station
where
  obs.var = top_obs.var
  and obs.station = top_obs.station
  and obs.station = station.seq
  and obs.t = top_obs.t
  and obs.t >= strftime('%s','now','-1 month');
EOSQL;
  foreach ($dbh->query($sql) as $row) {
    $t = $row['t'];
    $n = $row['var'];
    $a = convertUnits($row['val'],$row['uom'],$_REQUEST['uom'] == 'english');
    $u = $a[0]["uom"];
    $v = $a[0]["val"];
    $dEnd   = date('Y-m-d\TH:i\Z');
    $dBegin = date('Y-m-d\TH:i\Z',time() - 60 * 60 * (24 * 30 + 1));
    $uEscape = str_replace('"','\\"',"graph.php?station=$row[id]&name=$n&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom'].'&cat='.$a[0]['cat']."&BOB&startDt=$dBegin&endDt=$dEnd");
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
    // array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://www.windalert.com/spot/$_REQUEST[id]'>Station information</a></td></tr>");
    // array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://www.weatherflow.com'>Provider information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }
?>
