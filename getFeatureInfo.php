<?php
  date_default_timezone_set('UTC');
  header('Content-type: application/json');

  $mapTime = $_REQUEST['mapTime'] != 'undefined' ? $_REQUEST['mapTime'] : '';

  $data = array();
  $data['t'] = array();
  $data['u'] = array();
  $data['d'] = array();

  if (preg_match('/text%2Fcsv/i',$_SERVER["REQUEST_URI"])) {
    $csv = csv_to_array(file_get_contents(substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'?')+1)));
    $rowhits = 0;
    for ($i = 0; $i < count($csv); $i++) {
      // round to nearest hour
      preg_match("/(\d\d\d\d)-(\d\d)-(\d\d)T(\d\d):(\d\d):(\d\d)Z/",$csv[$i]['time'],$a);
      $t = mktime($a[4],0,0,$a[2],$a[3],$a[1]) + ($a[4] >= 30 ? 3600 : 0) - $_REQUEST['tz'] * 60;
      $colhits = 0;
      foreach (array_keys($csv[$i]) as $vStr) {
        if (!preg_match('/time|longitude|latitude/i',$vStr)) {
          preg_match("/(.*)\[(.*)\]/",$vStr,$a);
          if ($csv[$i][$vStr] != '--') {
            $colhits++;
            $rowhits++;
            if (!array_key_exists($a[1],$data['d'])) {
              $data['d'][$a[1]] = array(sprintf("%f",$csv[$i][$vStr]));
              $data['u'][$a[1]] = sprintf("%s",$a[2]);
            }
            else {
              array_push($data['d'][$a[1]],sprintf("%f",$csv[$i][$vStr]));
            }
          }
        }
      }
      if ($colhits > 0) {
        if ($mapTime == $t) {
          $data['nowIdx'] = count($data['t']);
        }
        array_push($data['t'],$t * 1000);
      }
      if ($rowhits == 0) {
        $data['error'] = 'No data at location';
      }
    }
  }
  else {
    $xml = @simplexml_load_file(substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'?')+1));
    if ($xml->{'ServiceException'}) {
      $data['error'] = sprintf("%s",$xml->{'ServiceException'}->attributes()->{'code'});
    }
    else if ($xml->{'Point'}) {
      foreach ($xml->{'Point'} as $p) {
        $a = preg_split("/-| |:/",sprintf("%s",$p->{'Time'}[0]));
        $t = mktime($a[3],$a[4],$a[5],$a[0],$a[1],$a[2]) - $_REQUEST['tz'] * 60;
        if ($mapTime == $t) {
          $data['nowIdx'] = count($data['t']);
        }
        array_push($data['t'],$t * 1000);
        foreach ($p->{'Value'} as $v) {
          $vStr = sprintf("%s",$v->attributes()->{'Var'});
          if (!array_key_exists($vStr,$data['d'])) {
            $data['d'][$vStr] = array(sprintf("%f",$v));
            $data['u'][$vStr] = sprintf("%s",$v->attributes()->{'Unit'});
          }
          else {
            array_push($data['d'][$vStr],sprintf("%f",$v));
          }
        }
      }
    }
    else if ($xml->{'FeatureInfo'}) {
      foreach ($xml->{'FeatureInfo'} as $p) {
        if (sprintf("%s",$p->{'value'}[0]) != 'none') {
          $t = strtotime($p->{'time'}[0]) - $_REQUEST['tz'] * 60;
          if ($mapTime == $t) {
            $data['nowIdx'] = count($data['t']);
          }
          array_push($data['t'],$t * 1000);
          $vStr = 'Chlorophyll concentration';
          if (!array_key_exists($vStr,$data['d'])) {
            $data['d'][$vStr] = array(sprintf("%f",$p->{'value'}[0]));
            $data['u'][$vStr] = 'mg m-3';
          }
          else {
            array_push($data['d'][$vStr],array(sprintf("%f",$p->{'value'}[0])));
          }
        }
      }
    }
  }

  echo json_encode($data);

  // from http://www.php.net/manual/en/function.str-getcsv.php#104558
  function csv_to_array($input,$delimiter=',') {
    $header  = null;
    $data    = array();
    $csvData = str_getcsv($input,"\n");
    foreach ($csvData as $csvLine) {
      if (is_null($header)) {
        $header = explode($delimiter, $csvLine);
      }
      else {
        $items = explode($delimiter, $csvLine);
        for ($n = 0,$m = count($header); $n < $m; $n++) {
          $prepareData[$header[$n]] = $items[$n];
        }
        $data[] = $prepareData;
      }
    }
    return $data;
  }
?>
