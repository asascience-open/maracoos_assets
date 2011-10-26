<?php
  include_once('util.php');

  $bbox = explode(',',$_REQUEST['bbox']);

  $metadata = array();

  $sosProviders = array(
     'NDBC'        => 'xml/ndbc.xml'
    ,'CO-OPS'      => 'xml/co-ops.xml'
    ,'Weatherflow' => 'xml/weatherflow.xml'
  );

  foreach ($sosProviders as $provider => $xmlLoc) {
    if ($_REQUEST['provider'] == $provider) {
      $xml = @simplexml_load_file($xmlLoc);
      $getObservation = '';
      foreach ($xml->children('http://www.opengis.net/ows/1.1')->{'OperationsMetadata'}[0]->children('http://www.opengis.net/ows/1.1')->{'Operation'} as $o) {
        if ($o->attributes()->{'name'} == 'GetObservation') {
          $getObservation = sprintf("%s",$o
            ->children('http://www.opengis.net/ows/1.1')->{'DCP'}[0]
            ->children('http://www.opengis.net/ows/1.1')->{'HTTP'}[0]
            ->children('http://www.opengis.net/ows/1.1')->{'Get'}[0]
            ->attributes('http://www.w3.org/1999/xlink')->{'href'}
          );
        }
      }
      foreach ($xml->{'Contents'}[0]->{'ObservationOfferingList'}[0]->{'ObservationOffering'} as $o) {
        $chld = $o->children('http://www.opengis.net/gml');
        $id = str_replace('station-','',sprintf("%s",$o->attributes('http://www.opengis.net/gml')->{'id'}));
        $loc = explode(' ',sprintf("%s",$chld->{'boundedBy'}[0]->{'Envelope'}[0]->{'lowerCorner'}));
        $props = array();
        foreach ($o->{'observedProperty'} as $prop) {
          $p = explode('/',$prop->attributes('http://www.w3.org/1999/xlink')->{'href'});
          if (sprintf("%s",$p[count($p)-1]) != 'currents') {
            array_push($props,sprintf("%s",$p[count($p)-1]));
          }
        }
        $tEndPosition = strtotime(sprintf("%s",$o->{'time'}[0]->children('http://www.opengis.net/gml')->{'TimePeriod'}->children('http://www.opengis.net/gml')->{'endPosition'}[0]));
        if (!$tEndPosition || $tEndPosition > time() - 60 * 60 * 24 * 30) {
          addToStack($metadata,$bbox,$loc[1],$loc[0],$provider,array(
             'id'       => $id
            ,'descr'    => sprintf("$provider Station %s%s",$id,$chld->{'description'} != 'GetCapabilities' ? ' - '.$chld->{'description'} : '')
            ,'url'      => "popup$provider.php"
              ."?getObservation=".$getObservation
              ."&procedure="     .sprintf("%s",$o->{'procedure'}[0]->attributes('http://www.w3.org/1999/xlink')->{'href'})
              ."&properties="    .str_replace('phenomenaDictionary.xml#','',implode(',',$props)) // hack for Weatherflow
              ."&id="            .$id
          ));
        }
      }
    }
  }

  if ($_REQUEST['provider'] == 'USGS') {
    $provider = 'USGS';
    $f = fopen("xml/usgs.csv",'r');
    $col2idx = array();
    $data = array();
    while (($d = fgetcsv($f)) !== FALSE) {
      if (count($col2idx) == 0) {
        foreach ($d as $k => $v) {
          $col2idx[$v] = count($col2idx);
        }
      }
      else {
        addToStack($metadata,$bbox,$d[$col2idx['lon']],$d[$col2idx['lat']],$provider,array(
           'id'       => $d[$col2idx['id']]
          ,'descr'    => sprintf("$provider Station %s - %s",$d[$col2idx['id']],$d[$col2idx['descr']])
          ,'url'      => "popup$provider.php"
            ."?id=".$d[$col2idx['id']]
        ));
      }
    }
    fclose($f);
  }

  if ($_REQUEST['provider'] == 'HF Radar') {
    $provider = 'HF Radar';
    $f = fopen("xml/hf_radar.csv",'r');
    $col2idx = array();
    $data = array();
    while (($d = fgetcsv($f)) !== FALSE) {
      if (count($col2idx) == 0) {
        foreach ($d as $k => $v) {
          $col2idx[$v] = count($col2idx);
        }
      }
      else {
        addToStack($metadata,$bbox,$d[$col2idx['lon']],$d[$col2idx['lat']],$provider,array(
           'id'    => $d[$col2idx['id']]
          ,'descr' => "HF Radar ground station"
          ,'url'   => "popup$provider.php?"
        ));
      }
    }
    fclose($f);
  }

  if ($_REQUEST['provider'] == 'Satellites') {
    $provider = 'Satellites';
    $f = fopen("xml/satellites.csv",'r');
    $col2idx = array();
    $data = array();
    while (($d = fgetcsv($f)) !== FALSE) {
      if (count($col2idx) == 0) {
        foreach ($d as $k => $v) {
          $col2idx[$v] = count($col2idx);
        }
      }
      else {
        addToStack($metadata,$bbox,$d[$col2idx['lon']],$d[$col2idx['lat']],$provider,array(
           'id'    => $d[$col2idx['id']]
          ,'descr' => "Satellite ground station"
          ,'url'   => "popup$provider.php?"
        ));
      }
    }
    fclose($f);
  }

  if ($_REQUEST['provider'] == 'Gliders') {
    $json = json_decode(file_get_contents('http://marine.rutgers.edu/cool/auvs/deployments.php?t0='.date("Y-m-d H:i",time() - 365 / 2 * 24 * 3600)));
    foreach ($json as $k => $v) {
      $d = array(
         'id'     => ''
        ,'track'  => array()
        ,'t'      => array()
        ,'active' => false
        ,'url'    => ''
        ,'descr'  => ''
      );
      foreach ($v as $depKey => $depVal) {
        if ($depKey == 'deployment') {
          $d['id']    = $depVal;
          $d['descr'] = "Glider $depVal";
        }
        else if ($depKey == 'track') {
          for ($i = 0; $i < count($depVal); $i++) {
            array_push($d['track'],array($depVal[$i]->lon,$depVal[$i]->lat));
            array_push($d['t'],$depVal[$i]->timestamp);
          }
        }
        else if ($depKey == 'active' && $depVal == 1) {
          $d['active'] = true;
        }
        else if ($depKey == 'url') {
          $d['url'] = $depVal;
        }
      }
      if (!$d['active']) {
        $d['descr'] .= ' (inactive)';
      }
      $d['url'] = "popupGliders.php"
        .'?id='.$d['id']
        .'&t='.$d['t'][0].' to '.$d['t'][count($d['t']) - 1]
        .'&u='.$d['url'];

      // push to $metadata straight up
      $metadata['Gliders.'.$d['id']]['Gliders.'.$d['id']] = array($d);
    }
  }

  if (preg_match('/gliders$/',$_REQUEST['provider'])) {
    $type = '';
    if ($_REQUEST['provider'] == 'Sea gliders') {
      $type = 'seaglider';
    }
    else if ($_REQUEST['provider'] == 'Spray gliders') {
      $type = 'spray';
    }
    else if ($_REQUEST['provider'] == 'Slocum gliders') {
      $type = 'slocum';
    }
    else if ($_REQUEST['provider'] == 'Unknown gliders') {
      $type = 'unknown';
    }

    $json = json_decode(file_get_contents("http://marine.rutgers.edu/cool/auvs/track.php?service=track&type[]=$type&t0=".$_REQUEST['t0']."&t1=".$_REQUEST['t1']));
    foreach ($json as $k => $v) {
      $d = array(
         'id'     => ''
        ,'track'  => array()
        ,'t'      => array()
        ,'active' => false
        ,'url'    => ''
        ,'descr'  => ''
      );
      foreach ($v as $depKey => $depVal) {
        if ($depKey == 'deployment') {
          $d['id']    = $depVal;
          $d['descr'] = $_REQUEST['provider']." $depVal";
        }
        else if ($depKey == 'track') {
          for ($i = 0; $i < count($depVal); $i++) {
            if (is_array($depVal)) {
              array_push($d['track'],array($depVal[$i]->lon,$depVal[$i]->lat));
              array_push($d['t'],$depVal[$i]->timestamp);
            }
          }
        }
        else if ($depKey == 'active' && $depVal == 1) {
          $d['active'] = true;
        }
        else if ($depKey == 'url') {
          $d['url'] = $depVal;
        }
      }
      if (!$d['active']) {
        $d['descr'] .= ' (inactive)';
      }
      $d['url'] = "popupGliders.php"
        .'?id='.$d['id']
        .'&t='.$d['t'][0].' to '.$d['t'][count($d['t']) - 1]
        .'&u='.$d['url'];

      // push to $metadata straight up
      $metadata[$_REQUEST['provider'].'.'.$d['id']][$_REQUEST['provider'].'.'.$d['id']] = array($d);
    }
  }

  if ($_REQUEST['provider'] == 'NERRS') {
    require_once('/usr/local/nusoap/lib/nusoap.php');
    nusoap_base::setGlobalDebugLevel(0);
    $u      = substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/'));
    $wsdl   = new nusoap_client('http://129.252.139.102/webservices/xmldatarequest.cfc?wsdl','wsdl');
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
        if ($d['Real_Time'] == 'R') {
          if (!array_key_exists($k,$data)) {
            $data[$k] = array($d);
          }
          else {
            array_push($data[$k],$d);
          }
        }
      }
    }

    $provider = 'NERRS';
    foreach ($data as $site) {
      $d = array();
      $d['stations'] = array();
      $d['params']   = array();
      foreach ($site as $station) {
        $d['id']    = $station['NERR_Site_ID'];
        $d['descr'] = $station['Reserve_Name'];
        $d['lon']   = -$station['Longitude'];
        $d['lat']   = $station['Latitude'];
        array_push($d['stations'],$station['Station_Code']);
        array_push($d['params'],$station['Params_Reported']);
      }
      addToStack($metadata,$bbox,$d['lon'],$d['lat'],$provider,array(
         'id'       => $d['id']
        ,'descr'    => sprintf("$provider Station %s - %s",$d['id'],$d['descr'])
        ,'url'      => "popup$provider.php"
          ."?id=".$d['id']
          .'&stations='.implode('|',$d['stations'])
          .'&params='.implode('|',$d['params'])
      ));
    }
  }

  $outMetadata = array();
  $i = 0;
  foreach (array_keys($metadata) as $m) {
    if ($i % $_REQUEST['everyNth'] == 0) {
      $outMetadata[$m] = $metadata[$m];
    }
    $i++; 
  } 

  echo json_encode(array(
     'bbox'     => $bbox
    ,'realBbox' => explode(',',$_REQUEST['realBbox'])
    ,'data'     => $outMetadata
    ,'zoom'     => $_REQUEST['zoom']
  ));
?>
