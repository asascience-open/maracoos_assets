<?php
  include_once('util.php');

  $url = 'http://cbibs-dev.asa.rocks/cdrh_rpc';
  $key = '0b0e81fe763a79660716bcee98a9ccbea653c8bd';

  date_default_timezone_set('UTC');
  $t = Array();
  $o = Array();

  $readings = request($url,$key,array(
     'method' => 'RetrieveCurrentReadings'
    ,'params' => array(
       $_REQUEST['constellation']
      ,$_REQUEST['id']
      ,$key
    )
    ,'id' => 1
  ));

  $i = 0;
  foreach ($readings['result']['report_name'] as $var) {
    array_push($t,strtotime($readings['result']['time'][$i].'Z'));
    $n = $var;
    $a = convertUnits((float)sprintf("%.04f",$readings['result']['value'][$i]),$readings['result']['units'][$i],$_REQUEST['uom'] == 'english');
    $u = $a[0]["uom"];
    $v = $a[0]["val"];
    $dEnd   = date('m/d/Y H:i');
    $dBegin = date('m/d/Y H:i',time() - 60 * 60 * (24 * 7 + 1));
    $uEscape = str_replace('"','\\"',"graph.php?constellation=".$_REQUEST['constellation']."&station=".$_REQUEST['id']."&name=".$readings['result']['measurement'][$i]."&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom'].'&cat='.$a[0]['cat']."&CBIBS&startDt=$dBegin&endDt=$dEnd");
    $extra = '';
    if (count($a) == 2) {
      $extra = "<br><a href='javascript:showObsTimeseries([\"".str_replace('graph.php?','graph.php?uomB&',$uEscape)."\"])'><img src='img/graph.png' width=10 height=10></a> ".$a[1]["val"].' '.$a[1]["uom"];
    }
    if ($v != '') {
      $tidy_n = substr($n,0,20).(strlen($n) > 20 ? '...' : '');
      array_push($o,sprintf("<tr><td><b><span title='%s'>%s</span></b></td><td><a href='javascript:showObsTimeseries([\"$uEscape\"])'><img src='img/graph.png' width=10 height=10></a> $v $u$extra</td></tr>",$n,$tidy_n));
    }
    $i++;
  }

  if (count($o) == 0) {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><th style="text-align:center">No recent observations</th></tr></table>'));
  }
  else {
    asort($t);
    $t = array_pop($t);
    array_unshift($o,sprintf("<tr><td colspan=2 style='text-align:center'><b>%s-%02d</b></td></tr>",date('M d G:i e',$t - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://buoybay.noaa.gov/'>More observations and station information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }

  function request($url,$key,$d) {
    $options = array(
      'http' => array(
        'header'  => array(
           'Content-Type: application/json'
          ,'Accept: application/json'
        )
        ,'method'  => 'POST'
        ,'content' => json_encode($d)
      )
    );

    $context = stream_context_create($options);
    $result  = file_get_contents(
       $url
      ,false
      ,$context
    );

    return json_decode($result,TRUE);
  }
?>
