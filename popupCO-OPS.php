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
    if ($p != 'sea_surface_height_amplitude_due_to_equilibrium_ocean_tide') { // don't want predicted
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
          $dEnd   = date('Y-m-d\TH:i:00\Z');
          $dBegin = date('Y-m-d\TH:i:00\Z',time() - 60 * 60 * (24 * 1 + 1));
          if ($v != '') {
            $uEscape = str_replace('"','\\"',"graph.php?$base&observedProperty=$p".'&responseFormat=text/xml;schema="ioos/0.6.1"'."&name=$n&eventTime=$dBegin/$dEnd&tz=".$_REQUEST['tz'].'&uom='.$_REQUEST['uom'].'&lon='.$_REQUEST['lon'].'&lat='.$_REQUEST['lat'].'&cat='.$a[0]['cat']);
            $extra = '';
            if (count($a) == 2) {
              $extra = "<br><a href='javascript:showObsTimeseries(\"".str_replace('graph.php?','graph.php?uomB&',$uEscape)."\")'><img src='img/graph.png' width=10 height=10></a> ".$a[1]["val"].' '.$a[1]["uom"];
            }
            array_push($o,sprintf("<tr><td><b>%s</b></td><td><a href='javascript:showObsTimeseries(\"$uEscape\")'><img src='img/graph.png' width=10 height=10></a> $v $u$extra</td></tr>",$n));
          }
        }
      }
    }
  }

  // get tide data
  $tNow = time();
  $u = sprintf("http://opendap.co-ops.nos.noaa.gov/axis/webservices/highlowtidepred/plain/response.jsp?stationId=%s&beginDate=%s&endDate=%s&datum=0&unit=1&timeZone=1"
    ,$_REQUEST['id']
    ,date('Ymd',$tNow - 60 * 60 * 24 * 1)
    ,date('Ymd',$tNow + 60 * 60 * 24 * 1)
  );
  $tideData = array();
  $marked = false;
  $tideUom = '';
  foreach(explode("\n",@file_get_contents($u)) as $l) {
    if (preg_match('/\d\d/',$l)) {
      $p = preg_split('/ +/',$l);
      for ($i = 1; $i < count($p) - 3; $i += 3) {
        $tideT = strtotime(sprintf("%sT%sZ",$p[0],$p[$i]));
        if ($tideT > $tNow - 60 * 60 * 6) {
          $nextTide = false;
          if ($tideT >= $tNow && $marked == false) {
            $nextTide = true;
            $marked = true;
          }
          $a = convertUnits($p[$i+1],'m',$_REQUEST['uom'] == 'english');
          $tideUom = $a[0]["uom"];
          $v   = $a[0]["val"];
          $tideData[$tideT] = array(
             'val'  => sprintf("%.02f",$v)
            ,'hiLo' => $p[$i+2]
            ,'next' => $nextTide
          );
        }
      }
    }
  }

  // format tide data
  $tidesTr = array();
  foreach (array_keys($tideData) as $tideT) {
    $cls = '';
    if ($tideData[$tideT]['next']) {
      $cls = "class='hilite'";
    }
    array_push($tidesTr,sprintf("<td $cls>%s</td><td $cls>%s</td><td $cls>%s</td><td $cls>%s %s</td><td $cls>%s</td>"
      ,date('m/d',$tideT - $_REQUEST['tz'] * 60)
      ,date('D',$tideT - $_REQUEST['tz'] * 60)
      ,date('g:i a',$tideT - $_REQUEST['tz'] * 60)
      ,$tideData[$tideT]['val']
      ,$tideUom
      ,$tideData[$tideT]['hiLo']
    ));
  }
  if (count($tideData) > 0) {
    array_push($o,"<td colspan=2 style='padding-left:25px'>".'<div id="tide"><table><tr><td colspan=5 style="text-align:center;border-top:none;border-left:none;border-right:none;"><b>Tides table (next tide highlighted)</b></td></tr><tr>'.implode('</tr><tr>',$tidesTr).'</tr></table></div>'."</td>");
  }

  if (count($o) == 0) {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><th style="text-align:center">No recent observations</th></tr></table>'));
  }
  else {
    array_unshift($o,sprintf("<tr><td colspan=2 style='text-align:center'><b>%s-%02d</b></td></tr>",date('M d G:i e',strtotime($t) - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://tidesandcurrents.noaa.gov/noaatidepredictions/NOAATidesFacade.jsp?Stationid=".$_REQUEST['id']."'>More observations and station information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }
?>
