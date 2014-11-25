<?php
  require_once('util.php');
  require_once('./nusoap/lib/nusoap.php');
  nusoap_base::setGlobalDebugLevel(0);
  $wsdl = new nusoap_client('http://cdmo.baruch.sc.edu/webservices2/requests.cfc?wsdl');

  $stations = explode('|',$_REQUEST['stations']);
  $params   = explode('|',$_REQUEST['params']);

  $data = array();
  for ($i = 0; $i < count($stations); $i++) {
    $result = $wsdl->call('exportSingleParamXML',array('tbl' => $stations[$i],'numrecs' => '1','param' => $params[$i]));
    $col2idx = array();
    foreach ($result['nds']['data']['r'] as $r) {
      $d = array();
      // figure out the col mapping
      if (count($col2idx) == 0) {
        foreach ($r['c'] as $c) {
          array_push($col2idx,$c['!v']);
        }
      }
      else {
        foreach ($r['c'] as $c) {
          if (rtrim($c['!v']) != 'null') {
            $d[$col2idx[count($d)]] = rtrim($c['!v']);
          }
        }
      }
    }
    $d['station'] = $stations[$i];
    array_push($data,$d);
  }

  date_default_timezone_set('UTC');
  $t = ''; // assume same time for all obs
  $o = Array();

  foreach ($data as $d) {
    foreach ($d as $k => $v) {
      if ($k != 'DateTimeStamp' && $k != 'station') {
        $dEnd   = date('m/d/Y H:i');
        $dBegin = date('m/d/Y H:i',time() - 60 * 60 * (24 * 1 + 1));
        $a = convertUnits($v,nerrsUnits($k),$_REQUEST['uom'] == 'english');
        $u = $a[0]["uom"];
        $v = $a[0]["val"];
        $uEscape = str_replace('"','\\"',"graph.php?name=$k&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom'].'&lon='.$_REQUEST['lon'].'&lat='.$_REQUEST['lat'].'&cat='.$a[0]['cat']."&NERRS=".$d['station']."&startDt=$dBegin&endDt=$dEnd");
        $extra = '';
        if (count($a) == 2) {
          $extra = "<br><a href='javascript:showObsTimeseries([\"".str_replace('graph.php?','graph.php?uomB&',$uEscape)."\"])'><img src='img/graph.png' width=10 height=10></a> ".$a[1]["val"].' '.$a[1]["uom"];
        }
        array_push($o,sprintf("<tr><td><b>%s</b></td><td><a href='javascript:showObsTimeseries([\"$uEscape\"])'><img src='img/graph.png' width=10 height=10></a> $v $u$extra</td></tr>",$k));
      }
    }
  }

  if (count($data) > 0) {
    $t = $data[0]['DateTimeStamp'];
  }
  if ($t == '') {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><td style="text-align:center">No recent observations</td></tr></table>'));
  }
  else {
    array_unshift($o,sprintf("<tr><td style='text-align:center' colspan=2><b>%s %s</b></td></tr>",date('M d G:i',strtotime($t)),'LST'));
    foreach ($stations as $s) {
      array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://nerrsdata.org/get/realTime.cfm?stationCode=$s'>More observations and station information</a></td></tr>");
    }
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }
?>
