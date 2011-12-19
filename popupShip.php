<?php
  include_once('util.php');

  date_default_timezone_set('UTC');
  $xml = simplexml_load_file('xml/shipobs.xml');
  $t = ''; // assume same time for all obs
  $o = Array();

  // find max time
  foreach ($xml->{'record'} as $r) {
    $a = $r->attributes();
    if (sprintf("%s",$a->{'shef_id'}) == $_REQUEST['id']) {
      if ($t == '' || strtotime(sprintf("%s",$a->{'ObTime'})) > $t) {
        $t = strtotime(sprintf("%s",$a->{'ObTime'}));
      }
    }
  }

  foreach ($xml->{'record'} as $r) {
    $a = $r->attributes();
    if (sprintf("%s",$a->{'shef_id'}) == $_REQUEST['id'] && $t == strtotime(sprintf("%s",$a->{'ObTime'}))) {
      $n = sprintf("%s",$a->{'var'});
      $info = varInfo(sprintf("%s",$a->{'var'}));
      $a = convertUnits(sprintf("%s",$a->{'data_value'}),$info[1],$_REQUEST['uom'] == 'english');
      $u = $a[0]["uom"];
      $v = sprintf("%.02f",$a[0]["val"]);
      array_push($o,sprintf("<tr><td><b>%s</b></td><td>$v $u</td></tr>",$info[0]));
    }
  }

  if (count($o) == 0) {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><th style="text-align:center">No recent observations</th></tr></table>'));
  }
  else {
    array_unshift($o,sprintf("<tr><td colspan=2 style='text-align:center'><b>%s-%02d</b></td></tr>",date('M d G:i e',$t - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://www.sailwx.info/shiptrack/shipposition.phtml?call=".$_REQUEST['id']."'>Ship status report</a></td></tr>");
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://madis.noaa.gov/madis_sfc.html'>Provider information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }

  function varInfo($s) {
    if ($s == 'V-SLP') {
      return array('sea level pressure','bar');
    }
    else if ($s == 'V-T') {
      return array('air temperature','kelvin');
    }
    else if ($s == 'V-SST') {
      return array('sea surface temperature','kelvin');
    }
    else if ($s == 'V-DD') {
      return array('wind direction','degrees');
    }
    else if ($s == 'V-FF') {
      return array('wind speed','m/s');
    }
    else if ($s == 'V-FFGUST') {
      return array('wind gust','m/s');
    }
    else if ($s == 'V-PSWDIR') {
      return array('direction of primary swell waves','degrees');
    }
    else if ($s == 'V-PSWHT') {
      return array('primary swell wave height','m');
    }
    else if ($s == 'V-WAVEHT') {
      return array('wave height','m');
    }
    else if ($s == 'V-WAVEPER') {
      return array('wave period','sec');
    }
    else if ($s == 'V-WWVEPER') {
      return array('wind wave period','sec');
    }
    else if ($s == 'V-WWVEHT') {
      return array('wind wave height','m');
    }
    else if ($s == 'V-PSWPER') {
      return array('primary swell wave period','sec');
    }
    else {
      return array($s,'');
    }
  }
?>
