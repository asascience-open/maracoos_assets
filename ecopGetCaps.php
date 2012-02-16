<?php
  header('Content-type:application/json');
  $layers = array(
     'currents'    => array()
    ,'winds'       => array()
    ,'waves'       => array()
    ,'temperature' => array()
    ,'other'       => array()
  );
  $layerStack = array();
  $xml = @simplexml_load_file('http://coastmap.com/ecop/wms.aspx?service=WMS-999&version=1.1.1&request=getcapabilities');
  foreach ($xml->{'Capability'}[0]->{'Layer'}[0]->{'Layer'} as $l) {
    $a = array(
       'title'    => sprintf("%s",$l->{'Title'})
      ,'name'     => sprintf("%s",$l->{'Name'})
      ,'abstract' => sprintf("%s",$l->{'Abstract'})
      ,'bbox'     => array(
         sprintf("%f",$l->{'LatLonBoundingBox'}->attributes()->{'minx'})
        ,sprintf("%f",$l->{'LatLonBoundingBox'}->attributes()->{'miny'})
        ,sprintf("%f",$l->{'LatLonBoundingBox'}->attributes()->{'maxx'})
        ,sprintf("%f",$l->{'LatLonBoundingBox'}->attributes()->{'maxy'})
      )
    );
    if (preg_match('/_CURRENTS$/',$a['name'])) {
      $a['type']  = 'currents';
      $a['title'] .= '||'.$a['type'];
      array_push($layerStack,$a);
      array_push($layers['currents'],$a);
    }
    else if (preg_match('/_WINDS$/',$a['name'])) {
      $a['type']  = 'winds';
      $a['title'] .= '||'.$a['type'];
      array_push($layerStack,$a);
      array_push($layers['winds'],$a);
    }
    else if (preg_match('/_WAVE_/',$a['name'])) {
      $a['type']  = 'waves';
      $a['title'] .= '||'.$a['type'];
      if (preg_match('/DIRECTION$/',$a['name'])) {
        array_push($layerStack,$a);
      }
      else {
        array_unshift($layerStack,$a);
      }
      array_push($layers['waves'],$a);
    }
    else if (preg_match('/_(TEMP|SST)$/',$a['name'])) {
      $a['type']  = 'temperature';
      $a['title'] .= '||'.$a['type'];
      array_unshift($layerStack,$a);
      array_push($layers['temperature'],$a);
    }
    else {
      $a['type']  = 'other';
      $a['title'] .= '||'.$a['type'];
      array_unshift($layerStack,$a);
      array_push($layers['other'],$a);
    }
  }

  foreach (array_keys($layers) as $l) {
    usort($layers[$l],'customSort');
  }

  function customSort($a,$b) {
    return $a['title'] > $b['title'];
  }
?>
