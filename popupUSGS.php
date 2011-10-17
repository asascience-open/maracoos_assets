<?php
  include_once('util.php');

  $base = 'http://waterservices.usgs.gov/nwis/iv?format=waterml&site='.$_REQUEST['id'];

  date_default_timezone_set('UTC');
  $t = ''; // assume same time for all obs
  $o = Array();
  $xml = @simplexml_load_file($base);
  foreach ($xml->children('http://www.cuahsi.org/waterML/1.1/')->{'timeSeries'} as $ts) {
    $var     = $ts->children('http://www.cuahsi.org/waterML/1.1/')->{'variable'}[0];
    $p       = sprintf("%s",$var->children('http://www.cuahsi.org/waterML/1.1/')->{'variableCode'}[0]);
    preg_match('/(.*), ([^,]*)/',sprintf("%s",$var->children('http://www.cuahsi.org/waterML/1.1/')->{'variableName'}[0]),$matches);
    $n       = $matches[1];
    $uomOrig = preg_replace('/&\#...;/','',$matches[2]);
    $noData  = sprintf("%s",$var->children('http://www.cuahsi.org/waterML/1.1/')->{'noDataValue'}[0]);
    $val     = $ts->children('http://www.cuahsi.org/waterML/1.1/')->{'values'}[0]->children('http://www.cuahsi.org/waterML/1.1/')->{'value'}[0];
    if ($val) {
      $t       = sprintf("%s",$val->attributes()->{'dateTime'});
      $v       = sprintf("%s",$val);
      if ($v != $noData) {
        $a = makeMks($v,$uomOrig);
        $uomOrig = $a["uom"];
        $v = $a["val"];
        $a = convertUnits($v,$uomOrig,$_REQUEST['uom'] == 'english');
        $u = $a[0]["uom"];
        $v = $a[0]["val"];
        $dEnd   = date('Y-m-d\TH:i:00\Z');
        $dBegin = date('Y-m-d\TH:i:00\Z',time() - 60 * 60 * (24 * 1 + 1));
        if ($v != '') {
          $uEscape = str_replace('"','\\"',"graph.php?name=$n&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom']."&USGS=$base&parameterCd=$p&startDt=$dBegin&endDt=$dEnd");
          $extra = '';
          if (count($a) == 2) {
            $extra = "<br><a href='javascript:showObsTimeseries(\"".str_replace('graph.php?','graph.php?uomB&',$uEscape)."\")'><img src='img/graph.png' width=10 height=10></a> ".$a[1]["val"].' '.$a[1]["uom"];
          }
          array_push($o,sprintf("<tr><td><b>%s</b></td><td><a href='javascript:showObsTimeseries(\"$uEscape\")'><img src='img/graph.png' width=10 height=10></a> $v $u$extra</td></tr>",$n));
        }
      }
    }
  }

  $h = '';
  if ($t == '') {
    $h = '<table class="obsDetails"><tr><td style="text-align:center">No recent observations</td></tr></table>';
  }
  else {
    array_unshift($o,sprintf("<tr><td style='text-align:center' colspan=2><b>%s-%02d</b></td></tr>",date('M d G:i e',strtotime($t) - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://waterdata.usgs.gov/usa/nwis/uv?site_no=".$_REQUEST['id']."'>More observations and station information</a></td></tr>");
    $h = '<table class="obsDetails">'.implode('',$o).'</table>';
  }

  echo json_encode(Array('html' => $h));
?>
