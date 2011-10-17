<?php 
  header('Content-Type:text/plain');
  require_once('/usr/local/nusoap/lib/nusoap.php');
  nusoap_base::setGlobalDebugLevel(0);
  $u = substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/'));
  $wsdl = new nusoap_client("http://localhost$u/xml/nerrs.xml",'wsdl');
  $result = $wsdl->call('exportStationCodesXML');

  $col2idx = array();
  $data    = array();
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
        $d[$col2idx[count($d)]] = rtrim($c['!v']); 
      }
    }
    if (count($d) > 0) {
      // lump things by lon,lat
      $k = $d['Longitude'].','.$d['Latitude'];
      if (!array_key_exists($k,$data)) {
        $data[$k] = array($d);
      }
      else {
        array_push($data[$k],$d);
      }
    }
  }

  var_dump($data);
?>
