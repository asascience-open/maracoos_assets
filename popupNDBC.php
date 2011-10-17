<?php
  include_once('util.php');

  $base = $_REQUEST['getObservation']
    .'?request=GetObservation&service=SOS&version=1.0.0'
    .'&offering='.$_REQUEST['procedure']
    .'&procedure='.$_REQUEST['procedure'];

  date_default_timezone_set('UTC');
  $t = ''; // assume same time for all obs
  $o = Array();
  foreach (explode(',',$_REQUEST['properties']) as $p) {
    $xml = @simplexml_load_file("$base&observedProperty=$p".'&responseFormat=text/xml;schema="ioos/0.6.1"');
    if ($xml->children('http://www.opengis.net/om/1.0')->{'result'}) {
      $t = sprintf("%s",$xml
        ->children('http://www.opengis.net/om/1.0')->{'result'}[0]
        ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}[0]
        ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
        ->children('http://www.noaa.gov/ioos/0.6.1')->{'Array'}[0]
        ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
        ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}[0]
        ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
        ->children('http://www.noaa.gov/ioos/0.6.1')->{'Array'}[0]
        ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
        ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}[0]
        ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
        ->children('http://www.noaa.gov/ioos/0.6.1')->{'CompositeContext'}[0]
        ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
        ->children('http://www.opengis.net/gml/3.2')->{'TimeInstant'}[0]
        ->children('http://www.opengis.net/gml/3.2')->{'timePosition'}[0]
      );
      foreach ($xml
          ->children('http://www.opengis.net/om/1.0')->{'result'}[0]
          ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}[0]
          ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
          ->children('http://www.noaa.gov/ioos/0.6.1')->{'Array'}[0]
          ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
          ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}[0]
          ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
          ->children('http://www.noaa.gov/ioos/0.6.1')->{'Array'}[0]
          ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
          ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}[0]
          ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
          ->children('http://www.noaa.gov/ioos/0.6.1')->{'CompositeValue'}[0]
          ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
          ->children('http://www.noaa.gov/ioos/0.6.1')->{'Quantity'}
        as $q
      ) {
        $n = sprintf("%s",$q->attributes()->name);
        $a = convertUnits(sprintf("%s",$q),sprintf("%s",$q->attributes()->uom),$_REQUEST['uom'] == 'english');
        $u = $a[0]["uom"];
        $v = $a[0]["val"];
        $dEnd   = date('Y-m-d\TH:i\Z');
        $dBegin = date('Y-m-d\TH:i\Z',time() - 60 * 60 * (24 * 1 + 1));
        if ($v != '') {
          $uEscape = str_replace('"','\\"',"graph.php?$base&observedProperty=$p".'&responseFormat=text/xml;schema="ioos/0.6.1"'."&name=$n&eventTime=$dBegin/$dEnd&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom']);
          $extra = '';
          if (count($a) == 2) {
            $extra = "<br><a href='javascript:showObsTimeseries(\"".str_replace('graph.php?','graph.php?uomB&',$uEscape)."\")'><img src='img/graph.png' width=10 height=10></a> ".$a[1]["val"].' '.$a[1]["uom"];
          }
          array_push($o,sprintf("<tr><td><b>%s</b></td><td><a href='javascript:showObsTimeseries(\"$uEscape\")'><img src='img/graph.png' width=10 height=10></a> $v $u$extra</td></tr>",$n));
        }
      }
    }
    else {
      $f = fopen("$base&observedProperty=$p".'&responseFormat=text/csv','r');
      $col2idx = Array();
      $u = '';
      $v = '';
      while (($data = fgetcsv($f)) !== FALSE) {
        if (count($col2idx) == 0) {
          foreach ($data as $k => $v) {
            $col2idx[$v] = count($col2idx);
            if (preg_match("/^$p \((.*)\)$/",$v,$matches)) {
              $u = $matches[1];
            }
          }
        }
        else {
          $v = $data[$col2idx["$p ($u)"]];
        }
      }
      fclose($f);
      $a = convertUnits($v,$u,$_REQUEST['uom'] == 'english');
      $u = $a[0]["uom"];
      $v = $a[0]["val"];
      $dEnd   = date('Y-m-d\TH:i\Z');
      $dBegin = date('Y-m-d\TH:i\Z',time() - 60 * 60 * (24 * 1 + 1));
      if ($v != '') {
        $n = underscoreCaps($p);
        $uEscape = str_replace('"','\\"',"graph.php?$base&observedProperty=$p".'&responseFormat=text/csv'."&name=$n&eventTime=$dBegin/$dEnd&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom']);
        if (count($a) == 2) {
          $extra = "<br><a href='javascript:showObsTimeseries(\"".str_replace('graph.php?','graph.php?uomB&',$uEscape)."\")'><img src='img/graph.png' width=10 height=10></a> ".$a[1]["val"].' '.$a[1]["uom"];
        }
        array_push($o,sprintf("<tr><td><b>%s</b></td><td><a href='javascript:showObsTimeseries(\"$uEscape\")'><img src='img/graph.png' width=10 height=10></a> $v $u$extra</td></tr>",$n));
      }
    }
  }

  if (count($o) == 0) {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><th style="text-align:center">No recent observations</th></tr></table>'));
  }
  else {
    array_unshift($o,sprintf("<tr><td colspan=2 style='text-align:center'><b>%s-%02d</b></td></tr>",date('M d G:i e',strtotime($t) - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://www.ndbc.noaa.gov/station_page.php?station=".$_REQUEST['id']."'>More observations and station information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }
?>
