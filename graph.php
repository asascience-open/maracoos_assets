<?php
  include_once('util.php');

  date_default_timezone_set('UTC');

  $data = Array();
  $uomOrig = '';
  $uom = '';
  
  // usgs isn't sos, so treat it differently
  if (isset($_REQUEST['USGS'])) {
    $xml = @simplexml_load_file(substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'&USGS=')+6));
    foreach ($xml->children('http://www.cuahsi.org/waterML/1.1/')->{'timeSeries'} as $ts) {
      $var     = $ts->children('http://www.cuahsi.org/waterML/1.1/')->{'variable'}[0];
      $p       = sprintf("%s",$var->children('http://www.cuahsi.org/waterML/1.1/')->{'variableCode'}[0]);
      preg_match('/(.*), ([^,]*)/',sprintf("%s",$var->children('http://www.cuahsi.org/waterML/1.1/')->{'variableName'}[0]),$matches);
      $n       = $matches[1];
      $noData  = sprintf("%s",$var->children('http://www.cuahsi.org/waterML/1.1/')->{'noDataValue'}[0]);
      foreach ($ts->children('http://www.cuahsi.org/waterML/1.1/')->{'values'}[0]->children('http://www.cuahsi.org/waterML/1.1/')->{'value'} as $val) {
        $t       = sprintf("%s",$val->attributes()->{'dateTime'});
        $v       = sprintf("%s",$val);
        if ($v != $noData) {
          $a = makeMks($v,preg_replace('/&\#...;/','',$matches[2]));
          $uomOrig = $a['uom']; // pretend units came in as mks
          $newVal = convertUnits($a['val'],$a['uom'],$_REQUEST['uom'] == 'english');
          $v   = $newVal[0]['val'];
          $uom = $newVal[0]['uom'];
          if (count($newVal) == 2 && isset($_REQUEST['uomB'])) {
            $v   = $newVal[1]['val'];
            $uom = $newVal[1]['uom'];
          }
          $data[$t] = $v;
        }
      }
    }
  }
  // nerrs isn't sos either
  else if (isset($_REQUEST['NERRS'])) {
    require_once('/usr/local/nusoap/lib/nusoap.php');
    nusoap_base::setGlobalDebugLevel(0);
    $wsdl = new nusoap_client('http://cdmo.baruch.sc.edu/webservices2/requests.cfc?wsdl');
    $result = $wsdl->call('exportAllParamsDateRangeXML',array('tbl'=>$_REQUEST['NERRS'],'mindate'=>$_REQUEST['startDt'],'maxdate'=>$_REQUEST['endDt'],'fieldlist'=>$_REQUEST['name']));
    $d2      = array();
    $col2idx = array();
    $uomOrig = nerrsUnits($_REQUEST['name']);
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
      array_push($d2,$d);
    }
    foreach ($d2 as $d) {
      $t = $d['DateTimeStamp'];
      $v = $d[$_REQUEST['name']];
      if ($v != '') {
        $newVal = convertUnits($v,$uomOrig,$_REQUEST['uom'] == 'english');
        $v   = $newVal[0]['val'];
        $uom = $newVal[0]['uom'];
        if (count($newVal) == 2 && isset($_REQUEST['uomB'])) {
          $v   = $newVal[1]['val'];
          $uom = $newVal[1]['uom'];
        }
        $data[$t] = $v;
      }
    }
  }
  else if (isset($_REQUEST['BOB'])) {
    $json = json_decode(file_get_contents('http://pro-bob.com/data/api/records?min_time='.$_REQUEST['startDt'].'&format=arrays&buoy='.$_REQUEST['station']),true);
file_put_contents('/tmp/maplog','http://pro-bob.com/data/api/records?min_time='.$_REQUEST['startDt'].'&format=arrays&buoy='.$_REQUEST['station']."\n",FILE_APPEND);
    foreach ($json[$_REQUEST['station']] as $var) {
      if ($var['variable']['name'] == $_REQUEST['name']) {
        for ($i = 0; $i < count($var['values']); $i++) {
          $t = $var['times'][$i];
          $n = $var['variable']['name'];
          $a = convertUnits($var['values'][$i],$var['variable']['unit'],$_REQUEST['uom'] == 'english');
          $u = $a[0]["uom"];
          $v = $a[0]["val"];
          if (count($a) == 2 && isset($_REQUEST['uomB'])) {
            $v = $a[1]['val'];
            $u = $a[1]['uom'];
          }
          $uom = $u;
          $data[$t] = $v;
        }
      }
    }
  }
  // mddnr isn't either
  else if (isset($_REQUEST['MDDNR'])) {
    $a = getMDDNR(substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'&MDDNR=')+7));
    foreach ($a['data'][$_REQUEST['name']] as $k => $v) {
      $newVal = convertUnits($v['value'],$v['units'],$_REQUEST['uom'] == 'english');
      $val    = $newVal[0]['val'];
      $uom    = $newVal[0]['uom'];
      if (count($newVal) == 2 && isset($_REQUEST['uomB'])) {
        $val = $newVal[1]['val'];
        $uom = $newVal[1]['uom'];
      }
      $data[date('c',$k)] = $val;
    }
  }
  else {
    if ($_REQUEST['responseFormat'] != 'text/csv') {
      // This is INSANE but simplexml_load_* seems to bomb if there isn't a COMMENT STRING before the 1st entry.  WTF?!
      // So always stick one in.
      $xmlStr = preg_replace('/(<om:CompositeObservation)([^>]*>)/','${1}${2}'."\n  <!--===============================================-->",@file_get_contents(str_replace('uomB&','',substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'?')+1))));
      $xml = @simplexml_load_string($xmlStr);
      if ($xml && $xml->children('http://www.opengis.net/om/1.0')->{'result'}) {
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
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}
          as $pointComposite) {
          $t = sprintf("%s",$pointComposite
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'CompositeContext'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'TimeInstant'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'timePosition'}[0]
          );
          $v = '';
          foreach ($pointComposite
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'CompositeValue'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'Quantity'}
          as $pointObservations) {
            if ($pointObservations->attributes()->{'name'} == $_REQUEST['name']) {
              $v = sprintf("%s",$pointObservations);
              $uomOrig = $pointObservations->attributes()->{'uom'};
            }
          }
          $newVal = convertUnits($v,$uomOrig,$_REQUEST['uom'] == 'english');
          $v   = $newVal[0]['val'];
          $uom = $newVal[0]['uom'];
          if (count($newVal) == 2 && isset($_REQUEST['uomB'])) {
            $v   = $newVal[1]['val'];
            $uom = $newVal[1]['uom'];
          }
          $data[$t] = $v;
        }
      }
    }
    else {
      $p = $_REQUEST['observedProperty'];
      $f = fopen(str_replace('uomB&','',substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'?')+1)),'r');
      $col2idx = Array();
      while (($csvData = fgetcsv($f)) !== FALSE) {
        if (count($col2idx) == 0) {
          foreach ($csvData as $k => $v) {
            $col2idx[$v] = count($col2idx);
            if (preg_match("/^$p \((.*)\)$/",$v,$matches)) {
              $uomOrig = $matches[1];
            }
          }
        }
        else {
          $v = $csvData[$col2idx["$p ($uomOrig)"]];
          $newVal = convertUnits($v,$uomOrig,$_REQUEST['uom'] == 'english');
          $v   = $newVal[0]['val'];
          $uom = $newVal[0]['uom'];
          if (count($newVal) == 2 && isset($_REQUEST['uomB'])) {
            $v   = $newVal[1]['val'];
            $uom = $newVal[1]['uom'];
          }
          $data[$csvData[$col2idx['date_time']]] = $v;
        }
      }
      fclose($f);
    }
  }

  $dataChart = array();
  $dataChart['t'] = array();
  $dataChart['u'] = array();
  $dataChart['d'] = array();

  foreach ($data as $k => $v) {
    $t = strtotime($k) - (!(isset($_REQUEST['NERRS'])) ? $_REQUEST['tz'] * 60 : 0);
    array_push($dataChart['t'],$t * 1000);
    if (!array_key_exists($_REQUEST['name'],$dataChart['d'])) {
      $dataChart['d'][$_REQUEST['name']] = array($v);
      $dataChart['u'][$_REQUEST['name']] = sprintf("%s",$uom);
    }
    else {
      array_push($dataChart['d'][$_REQUEST['name']],$v);
    }
  }

  echo json_encode($dataChart);
?>
