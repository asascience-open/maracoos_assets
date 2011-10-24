<?php
  $pt = ms_newPointObj();
  $pt->setXY($_REQUEST['lon'],$_REQUEST['lat']);

  $m = ms_newMapObj('/home/cpurvis/Temp/nws/zones.map');

  $mz = '';
  $l  = $m->getLayer(2);
  $q  = @$l->queryByPoint($pt,MS_SINGLE,0);
  if ($q == MS_SUCCESS) {
    $s  = $l->getShape($l->getResult(0));
    $mz = $s->getValue($l,"ID");
  }

  $oz = '';
  $l  = $m->getLayer(1);
  $q  = @$l->queryByPoint($pt,MS_SINGLE,0);
  if ($q == MS_SUCCESS) {
    $s  = $l->getShape($l->getResult(0));
    $oz = $s->getValue($l,"ID");
  }

  $json = json_decode(@file_get_contents('http://www.srh.noaa.gov/ridge2/ajax/getProductsForPoint.php?lon='.$_REQUEST['lon'].'&lat='.$_REQUEST['lat']));
  foreach ($json->{'features'} as $k => $v) {
    // strip out the geom, we don't need it
    $json->{'features'}[$k]->{'geometry'} = array(
       'type'        => 'Point'
      ,'coordinates' => array(0,0)
    );
  }

  if (count($json->{'features'}) <= 0) {
    $json->{'features'} = array(array(
       'type'     => 'Feature'
      ,'geometry' => array(
         'type'        => 'Point'
        ,'coordinates' => array(0,0)
      )
      ,'properties' => array(
          'dummy'      => true
         ,'marineFC'   => @file_get_contents('http://weather.noaa.gov/pub/data/forecasts/marine/coastal/'.strtolower(substr($mz,0,2).'/'.$mz.'.txt'))
         ,'offshoreFC' => @file_get_contents('http://weather.noaa.gov/pub/data/forecasts/marine/offshore/'.strtolower(substr($oz,0,2).'/'.$oz.'.txt'))
      )
    ));
  }
  else {
    $json->{'features'}[0]->{'properties'}->{'marineFC'} = @file_get_contents('http://weather.noaa.gov/pub/data/forecasts/marine/coastal/'.strtolower(substr($mz,0,2).'/'.$mz.'.txt'));
    $json->{'features'}[0]->{'properties'}->{'offshoreFC'} = @file_get_contents('http://weather.noaa.gov/pub/data/forecasts/marine/offshore/'.strtolower(substr($oz,0,2).'/'.$oz.'.txt'));
    $json->{'features'}[0]->{'properties'}->{'pointFC'} = array();
    $xml = simplexml_load_file('http://forecast.weather.gov/MapClick.php?FcstType=xml&lon='.$_REQUEST['lon'].'&lat='.$_REQUEST['lat']);
    foreach ($xml->{'period'} as $p) {
      array_push($json->{'features'}[0]->{'properties'}->{'pointFC'},array(
         'valid'   => sprintf("%s",$p->{'valid'})
        ,'text'    => sprintf("%s",$p->{'text'})
        ,'image'   => sprintf("%s/%s",$xml->{'icon-location'},$p->{'image'})
        ,'weather' => sprintf("%s",$p->{'weather'})
        ,'temp'    => sprintf("%s of %s %s",($p->{'temp'}->attributes()->{'hilo'} == 'H' ? 'high' : 'low'),$p->{'temp'},$p->{'temp'}->attributes()->{'format'})
      ));
    }
  }

  echo json_encode($json);
?>
