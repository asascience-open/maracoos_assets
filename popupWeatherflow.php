<?php
  include_once('util.php');

  // Weatherflow's getObs link is wrong.
  $getObs = explode('/',$_REQUEST['getObservation']);

  // the offering / procedure needs some help
  $proc = str_replace(':sensor',':station',$_REQUEST['procedure']);
  $proc = substr($proc,0,strrpos($proc,':'));

  $base = "$getObs[0]//$getObs[1]$getObs[2]/sos/$getObs[3]"
    .'?request=GetObservation&service=SOS&version=1.0.0'
    .'&offering='.$proc
    .'&procedure='.$proc;

  date_default_timezone_set('UTC');
  $t = ''; // assume same time for all obs
  $o = Array();

  // ignore the properties param and override it w/ 'Winds'
  foreach (explode(',',$_REQUEST['properties']) as $p) {
    // sheesh!  Weatherflow likes observedproperty instead of observedProperty
    $xml = @simplexml_load_file("$base&observedproperty=$p".'&responseFormat=text/xml;schema="ioos/0.6.1"');
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
        $extra = '';
        if (count($a) == 2) {
          $extra = '<br/>'.$a[1]["val"].' '.$a[1]["uom"];
        }
        $dEnd   = date('Y-m-d\TH:i\Z');
        $dBegin = date('Y-m-d\TH:i\Z',time() - 60 * 60 * (24 * 1 + 1));
        if ($v != '') {
          array_push($o,sprintf("<tr><td><b>%s</b></td><td>$v $u$extra</td></tr>",$n));
        }
      }
    }
  }

  if (count($o) == 0) {
    echo json_encode(Array('html' => '<table class="obsDetails"><tr><th style="text-align:center">No recent observations</th></tr></table>'));
  }
  else {
    array_unshift($o,sprintf("<tr><td colspan=2 style='text-align:center'><b>%s-%02d</b></td></tr>",date('M d G:i e',strtotime($t) - $_REQUEST['tz'] * 60),$_REQUEST['tz']/60));
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='http://www.weatherflow.com'>Provider information</a></td></tr>");
    echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
  }
?>
