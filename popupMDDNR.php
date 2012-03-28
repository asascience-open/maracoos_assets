<?php
  include_once('util.php');

  date_default_timezone_set('UTC');

  $base = 'http://mddnr.chesapeakebay.net/newmontech/contmon/MACOORA_Station_data_download.cfm?station='.$_REQUEST['id'];
  $a = getMDDNR($base.'&day_count=14&last_date='.date("m/d/Y",time()));
  $data = $a['data'];
  $maxT = $a['maxT'];
  $obs = array();

  foreach ($data as $k => $v) {
    if (!preg_match("/Station|Date|Time/",$k)) {
      $a = convertUnits($data[$k][$maxT]['value'],$data[$k][$maxT]['units'],$_REQUEST['uom'] == 'english');
      $u = $a[0]["uom"];
      $v = $a[0]["val"];
      $cat = $a[0]["cat"];
      if ($cat == 'velocity' && preg_match('/wind/i',$k)) {
        $cat = 'windsVelocity';
      }
      $dEnd   = date('m/d/Y',time());
      $dBegin = 14;
      if ($v != '') {
        $uEscape = str_replace('"','\\"',"graph.php?name=$k&MDDNR=$base&last_date=$dEnd&day_count=$dBegin&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom'].'&lon='.$_REQUEST['lon'].'&lat='.$_REQUEST['lat'].'&cat='.$cat);
        $obs[$k] = array(array(
           'url' => $uEscape
          ,'val' => "$v $u"
        ));
        if (count($a) == 2) {
          array_push($obs[$k],array(
             'url' => str_replace('graph.php?','graph.php?uomB&',$uEscape)
            ,'val' => $a[1]["val"].' '.$a[1]["uom"]
          ));
        }
      }
    }
  }

  $o = array();
  foreach ($obs as $k => $v) {
    $companionUrl = '';
    if ($k == 'WindSpeed') {
      $companionUrl = ',"'.$obs['WindDirection'][0]['url'].'"';
    }
    else if ($k == 'WindDirection') {
      $companionUrl = ',"'.$obs['WindSpeed'][0]['url'].'"';
    }
    $extra = '';
    if (count($v) == 2) {
      $extra = "<br><a href='javascript:showObsTimeseries([\"".$v[1]['url']."\"$companionUrl])'><img src='img/graph.png' width=10 height=10></a> ".$v[1]['val'];
    }
    array_push($o,sprintf("<tr><td><b>%s</b></td><td><a href='javascript:showObsTimeseries([\"%s\"$companionUrl])'><img src='img/graph.png' width=10 height=10></a> %s$extra</td></tr>",$k,$v[0]['url'],$v[0]['val']));
  }

  if (count($o) == 0) {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><th style="text-align:center">No recent observations</th></tr></table>'));
  }
  else {
    array_unshift($o,sprintf("<tr><td colspan=2 style='text-align:center'><b>%s-%02d</b></td></tr>",date('M d G:i e',$maxT - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://mddnr.chesapeakebay.net/newmontech/contmon/eotb_results_graphs.cfm?station=".$_REQUEST['id']."'>More observations and station information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }
?>
