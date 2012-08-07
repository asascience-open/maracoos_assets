<?php
  function contains($bbox,$lon,$lat) {
    if ($bbox[0] <= $lon && $bbox[1] <= $lat && $lon <= $bbox[2] && $lat <= $bbox[3]) {
      return true;
    }
    return false;
  }

  function addToStack(&$metadata,$bbox,$lon,$lat,$provider,$o) {
    if (contains($bbox,$lon,$lat)) {
      $k = "$lon,$lat";
      if (!array_key_exists($k,$metadata)) {
        $metadata[$k] = Array();
      }
      if (!array_key_exists($provider,$metadata[$k])) {
        $metadata[$k][$provider] = Array();
      }
      array_push($metadata[$k][$provider],$o);
    }
  }

  function convertUnits($val,$uom,$toEnglish) {
    $english_category = array(
       'C'      => 'temperature'
      ,'F'      => 'temperature'
      ,'kelvin' => 'temperature'
      ,'m/s'    => 'velocity'
      ,'m'      => 'elevation'
    );
    if ($val == '') {
      return Array(Array('val' => '','uom' => '','cat' => ''));
    }
    $a = Array();
    if ($toEnglish) {
      if ($uom == 'm/s') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 1.943844),'uom' => 'knots','cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
        array_push($a,Array('val' => sprintf("%.02f",$val * 2.23693629),'uom' => 'mph','cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
      }
      else if ($uom == 'm') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 3.2808399),'uom' => 'ft','cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
      }
      else if ($uom == 'm below land surface') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 3.2808399),'uom' => 'ft below land surface','cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
      }
      else if ($uom == 'C') {
        array_push($a,Array('val' => sprintf("%.02f",9/5*$val + 32),'uom' => 'F','cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
      }
      else if ($uom == 'mm') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 0.0393700787),'uom' => 'in','cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
      }
      else if ($uom == 'kelvin') {
        array_push($a,Array('val' => sprintf("%.02f",($val - 272.15) * 9 / 5 + 32),'uom' => 'F','cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
      }
      else if ($uom == 'bar') {
        array_push($a,Array('val' => sprintf("%.02f",$val / 1000),'uom' => 'hPa','cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
      }
      else {
        return Array(Array('val' => $val,'uom' => $uom,'cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
      }
    }
    else {
      return Array(Array('val' => $val,'uom' => $uom,'cat' => isset($english_category[$uom]) ? $english_category[$uom] : ''));
    }
    return $a;
  }

  function makeMks($val,$uom) {
    if ($val == '') {
      return Array('val' => '','uom' => '');
    }
    if ($uom == 'in') {
      return Array('val' => sprintf("%.02f",$val * 0.0254),'uom' => 'm');
    }
    else if ($uom == 'ft') {
      return Array('val' => sprintf("%.02f",$val * 0.3048),'uom' => 'm');
    }
    else if ($uom == 'ft below land surface') {
      return Array('val' => sprintf("%.02f",$val * 0.3048),'uom' => 'm below land surface');
    }
    else if ($uom == 'ft/s') {
      return Array('val' => sprintf("%.02f",$val * 0.3048),'uom' => 'm/s');
    }
    return Array('val' => $val,'uom' => $uom);
  }

  function underscoreCaps($s) {
    $a = Array();
    foreach (explode('_',$s) as $p) {
      array_push($a,ucfirst($p));
    }
    return implode('',$a);
  }

  function nerrsUnits($p) {
    if ($p == 'Temp') {
      return 'C';
    }
    else if ($p == 'SpCond') {
      return 'mS/cm';
    }
    else if ($p == 'Sal') {
      return 'ppt';
    }
    else if ($p == 'DO_pct') {
      return 'percent';
    }
    else if ($p == 'DO_mgl') {
      return 'mg/L';
    }
    else if ($p == 'Depth') {
      return 'm';
    }
    else if ($p == 'cDepth') {
      return 'm';
    }
    else if ($p == 'Level') {
      return 'm';
    }
    else if ($p == 'cLevel') {
      return 'm';
    }
    else if ($p == 'pH') {
      return '';
    }
    else if ($p == 'Turb') {
      return 'NTU';
    }
    else if ($p == 'ChlFluor') {
      return 'ug/L';
    }
    else if ($p == 'ATemp') {
      return 'C';
    }
    else if ($p == 'RH') {
      return 'percent';
    }
    else if ($p == 'BP') {
      return 'mb';
    }
    else if ($p == 'WSpd') {
      return 'm/s';
    }
    else if ($p == 'MaxWSpd') {
      return 'm/s';
    }
    else if ($p == 'MaxWSpdT') {
      return 'm/s';
    }
    else if ($p == 'Wdir') {
      return 'degrees';
    }
    else if ($p == 'SDWDir') {
      return 'sd';
    }
    else if ($p == 'TotPAR') {
      return 'mmol/m^2';
    }
    else if ($p == 'TotPrcp') {
      return 'mm';
    }
    else if ($p == 'CumPrcp') {
      return 'mm';
    }
    else if ($p == 'TotSoRad') {
      return 'watts/m^2';
    }
    else if ($p == 'PO4F') {
      return 'mg/L';
    }
    else if ($p == 'NH4F') {
      return 'mg/L';
    }
    else if ($p == 'NO2F') {
      return 'mg/L';
    }
    else if ($p == 'NO3F') {
      return 'mg/L';
    }
    else if ($p == 'NO23F') {
      return 'mg/L';
    }
    else if ($p == 'CHLA_N') {
      return 'ug/L';
    }
    return '';
  }

  function getMDDNR($url) {
`echo "$url" >> /tmp/maplog`;
    date_default_timezone_set('UTC');
    $f = fopen($url,'r');
    $data    = array();
    $col2idx = array();
    $col2uom = array();
    $maxT    = 0;
    while (($csvData = fgetcsv($f)) !== FALSE) {
      if (count($col2idx) == 0) {
        foreach ($csvData as $k => $v) {
          $uom = '';
          if (preg_match("/(.*) \((.*)\)$/",$v,$matches)) {
            $uom = str_replace(chr(181),'micro',str_replace(chr(176),'',$matches[2])); // get rid of degrees sign and swap out micro symbol
          }
          $col2idx[$v] = count($col2idx);
          $col2uom[$v] = $uom;
        }
      }
      else {
        foreach ($col2idx as $k => $v) {
          $idx = $k;
          if (preg_match("/(.*) \((.*)\)$/",$k,$matches)) {
            $idx = $matches[1];
          }
          if (
            $csvData[$col2idx[$k]] != '' 
            && count($matches) > 1 
            && !(array_key_exists('Temp ('.chr(176).'C)',$col2idx) && $matches[1] == 'Temp' && $matches[2] == chr(176).'F') // don't want temps coming in as both C and F
            && !(array_key_exists('DO (%)',$col2idx)               && $matches[1] == 'DO'   && $matches[2] == '%')          // don't want DO as both conc. and %
          ) {
            $data[$idx][strtotime($csvData[$col2idx['Date+Time (EST)']].' EST')] = array(
               'value' => $csvData[$col2idx[$k]]
              ,'units' => $col2uom[$k]
            );
            if (!preg_match("/Station|Date|Time/",$k)) {
              $maxT = strtotime($csvData[$col2idx['Date+Time (EST)']].' EST');
            }
          }
        }
      }
    }
    fclose($f);

    return array(
       'maxT' => $maxT
      ,'data' => $data
    );
  }
?>
