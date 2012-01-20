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
    );
    if ($val == '') {
      return Array(Array('val' => '','uom' => '','cat' => ''));
    }
    $a = Array();
    if ($toEnglish) {
      if ($uom == 'm/s') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 1.943844),'uom' => 'knots','cat' => $english_category[$uom]));
        array_push($a,Array('val' => sprintf("%.02f",$val * 2.23693629),'uom' => 'mph','cat' => $english_category[$uom]));
      }
      else if ($uom == 'm') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 3.2808399),'uom' => 'ft','cat' => $english_category[$uom]));
      }
      else if ($uom == 'm below land surface') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 3.2808399),'uom' => 'ft below land surface','cat' => $english_category[$uom]));
      }
      else if ($uom == 'C') {
        array_push($a,Array('val' => sprintf("%.02f",9/5*$val + 32),'uom' => 'F','cat' => $english_category[$uom]));
      }
      else if ($uom == 'mm') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 0.0393700787),'uom' => 'in','cat' => $english_category[$uom]));
      }
      else if ($uom == 'kelvin') {
        array_push($a,Array('val' => sprintf("%.02f",($val - 272.15) * 9 / 5 + 32),'uom' => 'F','cat' => $english_category[$uom]));
      }
      else if ($uom == 'bar') {
        array_push($a,Array('val' => sprintf("%.02f",$val / 1000),'uom' => 'hPa','cat' => $english_category[$uom]));
      }
      else {
        return Array(Array('val' => $val,'uom' => $uom,'cat' => $english_category[$uom]));
      }
    }
    else {
      return Array(Array('val' => $val,'uom' => $uom,'cat' => $english_category[$uom]));
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
?>
