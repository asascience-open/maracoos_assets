<?php
  include_once('util.php');

  date_default_timezone_set('UTC');

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
      foreach ($xml->children('http://www.opengis.net/sos/1.0')->{'Contents'}[0]->children('http://www.opengis.net/sos/1.0')->{'ObservationOfferingList'}[0]->children('http://www.opengis.net/sos/1.0')->{'ObservationOffering'} as $o) {
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
              ."&lon="           .$loc[1]
              ."&lat="           .$loc[0]
          ));
        }
      }
    }
  }

  if ($_REQUEST['provider'] == 'Ship') {
    // get list of NDBC stations since we don't want to include these as ships
    $stations = array();
    preg_match_all('/<tr class="stndata"><td>(\w)*<\/td>/',file_get_contents('xml/ndbc_stations.html'),$m0);
    foreach ($m0[0] as $m) {
      preg_match('/<td>(.*)<\/td>/',$m,$m1);
      $stations[$m1[1]] = true;
    }
    $provider = 'Ship';
    $xml = simplexml_load_file('xml/shipobs.xml');
    $data = array();
    foreach ($xml->{'record'} as $r) {
      $a = $r->attributes();
      // don't pass along NDBC stations
      if (!array_key_exists(sprintf("%s",$a->{'shef_id'}),$stations)) {
        if (!array_key_exists(sprintf("%s",$a->{'shef_id'}),$data)) {
          $data[sprintf("%s",$a->{'shef_id'})] = array(
             'lon'  => sprintf("%f",$a->{'lon'})
            ,'lat'  => sprintf("%f",$a->{'lat'})
          );
        }
      }
    }

    foreach ($data as $key => $value) {
      if ($key != 'SHIP') {
        addToStack($metadata,$bbox,$value['lon'],$value['lat'],$provider,array(
           'id'       => $key
          ,'descr'    => sprintf("$provider %s",$key)
          ,'url'      => "popup$provider.php"
            ."?id=".$key
        ));
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
            ."&lon=".$d[$col2idx['lon']]
            ."&lat=".$d[$col2idx['lat']]
        ));
      }
    }
    fclose($f);
  }

  if ($_REQUEST['provider'] == 'MDDNR') {
    $provider = 'MDDNR';
    $f = fopen("http://mddnr.chesapeakebay.net/newmontech/contmon/MACOORA_Station.cfm",'r');
    $col2idx = array();
    $data = array();
    while (($d = fgetcsv($f)) !== FALSE) {
      if (count($col2idx) == 0) {
        foreach ($d as $k => $v) {
          $col2idx[$v] = count($col2idx);
        }
      }
      else {
        addToStack($metadata,$bbox,$d[$col2idx['Long']],$d[$col2idx['Lat']],$provider,array(
           'id'       => $d[$col2idx['Shortname']]
          ,'descr'    => sprintf("$provider Station %s",$d[$col2idx['Station_name']])
          ,'url'      => "popup$provider.php"
            ."?id=".$d[$col2idx['Shortname']]
            ."&lon=".$d[$col2idx['Long']]
            ."&lat=".$d[$col2idx['Lat']]
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
        addToStack($metadata,$bbox,$d[$col2idx['Longitude']],$d[$col2idx['Latitude']],$provider,array(
           'id'    => array_key_exists('id',$col2idx) ? $d[$col2idx['id']] : ''
          ,'descr' => "HF Radar ground station"
          ,'url'   => "popup$provider.php"
            ."?name=".urlencode($d[$col2idx['Name']])
            ."&owner=".urlencode($d[$col2idx['Radar Owner']])
            ."&model=".urlencode($d[$col2idx['Model']])
        ));
      }
    }
    fclose($f);
  }

  if ($_REQUEST['provider'] == 'HRECOS') {
    $provider = 'HRECOS';
    $f = fopen("xml/hrecos.csv",'r');
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
          ,'descr' => 'HRECOS Station - '.$d[$col2idx['id']]
          ,'url'   => "popup$provider.php"
            ."?id=".$d[$col2idx['id']]
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
           'id'    => array_key_exists('id',$col2idx) ? $d[$col2idx['id']] : ''
          ,'descr' => "Satellite ground station"
          ,'url'   => "popup$provider.php"
            ."?descrip=".urlencode($d[$col2idx['descrip']])
        ));
      }
    }
    fclose($f);
  }

  if ($_REQUEST['provider'] == 'Gliders') {
    $json = json_decode(file_get_contents('http://marine.rutgers.edu/cool/auvs/track.php?service=track&&region=mab&t0='.date("Y-m-d H:i",time() - 365 / 2 * 24 * 3600)));
    foreach ($json as $k => $v) {
      $active = false; // honor what is marked as active
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
          $active = true;
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
        .'&t=from '.$d['t'][0]
        .($d['t'][count($d['t']) - 1] != '' ? (' to '.$d['t'][count($d['t']) - 1]) : ' (active)')
        .'&u='.$d['url'];

      // push to $metadata straight up (only active ones)
      if ($active) {
        $metadata['Gliders.'.$d['id']]['Gliders.'.$d['id']] = array($d);
      }
    }
  }

  if ($_REQUEST['provider'] == 'ESPRESSOSimFloats') {
    $u = 'http://64.72.74.107/geojson/http://tds.marine.rutgers.edu/thredds/dodsC/floats/espresso_flt_'.date("Ymd",time() - 3 * 24 * 3600).'.nc';
    $json = json_decode(file_get_contents($u),true);

    foreach ($json['features'] as $f) {
      $d = array(
         'id'     => $f['id']
        ,'track'  => $f['geometry']['coordinates']
        ,'active' => true
        ,'url'    => sprintf('popupESPRESSOSimFloats.php'
          ."?id=%s"
          ."&t=from %s to %s"
          ."&u="
          ,$f['properties']['title'].' '.$f['id']
          ,$f['properties']['time_coverage_start']
          ,$f['properties']['time_coverage_end']
          ,'http://tds.marine.rutgers.edu/thredds/catalog/floats/catalog.html'
        )
        ,'descr'  => $f['properties']['title'].' '.$f['id']
      );

      // push to $metadata straight up
      $metadata['Gliders.'.$d['id']]['Gliders.'.$d['id']] = array($d);
    }
  }

  if ($_REQUEST['provider'] == 'Drifters') {
    // we hope that line:marker is 1:1 -- otherwise we're screwed
    $xml = @simplexml_load_file('xml/drifters.xml');

    $drifters = array();
    // pull out markers 1st we can get the ID's
    foreach ($xml->{'marker'} as $c) {
      $html = sprintf("%s",$c->attributes()->{'html'});
      preg_match('/StartTime=(.*)<br> EndTime=(.*)/',$html,$matches);
      array_push($drifters,array(
         'id'     => sprintf("%s",$c->attributes()->{'label'})
        ,'track'  => array()
        ,'active' => sprintf("%s",$c->attributes()->{'active'}) == '1'
        ,'url'    => 'popupDrifters.php'
          .'?id='.sprintf("%s",$c->attributes()->{'label'})
          .'&t='.implode(' to ',array(trim($matches[1]),trim($matches[2])))
          .'&u=http://www.nefsc.noaa.gov/drifter/drift_X.html?'
        ,'descr'  => 'Drifter '.sprintf("%s",$c->attributes()->{'label'})
            .(sprintf("%s",$c->attributes()->{'active'}) !== '1' ? ' (inactive)' : '')
      ));
    }

    $i = 0;
    foreach ($xml->{'line'} as $c) {
      foreach ($c->{'point'} as $p) {
        array_push($drifters[$i]['track'],array(sprintf("%f",$p->attributes()->{'lng'}),sprintf("%f",$p->attributes()->{'lat'})));
      }
      // push to $metadata straight up
      $metadata['Drifters.'.$drifters[$i]['id']]['Drifters.'.$drifters[$i]['id']] = array($drifters[$i]);
      $i++;
    }
  }

  if (preg_match('/gliders$/',$_REQUEST['provider']) && isset($_REQUEST['t0']) && isset($_REQUEST['t1'])) {
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

    $json = array();
    if ($type == 'spray') {
      if (preg_match('/&provider\[\]=scripps/',$_REQUEST['filterProvider'])) {
        $json = json_decode(file_get_contents(str_replace(' ','%20','http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/')).'/gliders_scripps.php?t0='.$_REQUEST['t0'].'&t1='.$_REQUEST['t1'])));
      }
    }
    else if ($type == 'seaglider') {
      $json = array_merge(
        preg_match('/&provider\[\]=uw/',$_REQUEST['filterProvider']) ? 
          (array)json_decode(file_get_contents(str_replace(' ','%20','http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/')).'/gliders_uw.php?t0='.$_REQUEST['t0'].'&t1='.$_REQUEST['t1'])))
          : array()
        ,preg_match('/&provider\[\]=osu/',$_REQUEST['filterProvider']) ?
          (array)json_decode(file_get_contents(str_replace(' ','%20','http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/')).'/gliders_osu.php?t0='.$_REQUEST['t0'].'&t1='.$_REQUEST['t1'].'&type=seaglider')))
          : array()
      );
    }
    else if ($type == 'slocum') {
      $json = array_merge(
         (array)json_decode(file_get_contents(str_replace(' ','%20',"http://marine.rutgers.edu/cool/auvs/track.php?service=track&type[]=$type&t0=".$_REQUEST['t0']."&t1=".$_REQUEST['t1'].$_REQUEST['filterProvider'])))
        ,preg_match('/&provider\[\]=osu/',$_REQUEST['filterProvider']) ?
          (array)json_decode(file_get_contents(str_replace(' ','%20','http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/')).'/gliders_osu.php?t0='.$_REQUEST['t0'].'&t1='.$_REQUEST['t1'].'&type=slocum')))
          : array()
        ,preg_match('/&provider\[\]=gerg/',$_REQUEST['filterProvider']) ?
          (array)json_decode(file_get_contents(str_replace(' ','%20','http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/')).'/gliders_gerg.php?t0='.$_REQUEST['t0'].'&t1='.$_REQUEST['t1'].'&type=slocum')))
          : array()
      );
    }
    else {
      $json = json_decode(file_get_contents(str_replace(' ','%20',"http://marine.rutgers.edu/cool/auvs/track.php?service=track&type[]=$type&t0=".$_REQUEST['t0']."&t1=".$_REQUEST['t1'].$_REQUEST['filterProvider'])));
    }

    foreach ($json as $k => $v) {
      $d = array(
         'id'       => ''
        ,'track'    => array()
        ,'t'        => array()
        ,'active'   => false
        ,'url'      => ''
        ,'descr'    => ''
        ,'provider' => ''
      );
      foreach ($v as $depKey => $depVal) {
        if ($depKey == 'deployment') {
          $d['id']    = $depVal;
          $d['descr'] = $_REQUEST['provider']." $depVal";
        }
        else if ($depKey == 'provider') {
          $d['provider'] = $depVal;
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
      $d['descr'] = $d['provider'].' '.$d['descr'];
      $d['url'] = "popupGliders.php"
        .'?id='.$d['id']
        .'&t=from '.$d['t'][0]
        .($d['t'][count($d['t']) - 1] != '' ? (' to '.$d['t'][count($d['t']) - 1]) : ' (active)')
        .(array_key_exists('url',$d) ? '&u='.urlencode($d['url']) : '');

      // push to $metadata straight up
      $metadata[$_REQUEST['provider'].'.'.$d['id']][$_REQUEST['provider'].'.'.$d['id']] = array($d);
    }
  }

  if (preg_match('/Receiver/',$_REQUEST['provider'])) {
    $t0             = $_REQUEST['t0'];
    $t1             = $_REQUEST['t1'];
    $studiesFilter  = explode(',',$_REQUEST['glatosStudiesFilter']);
    $modelsFilter   = explode(',',$_REQUEST['glatosModelsFilter']);
    $seasonalFilter = $_REQUEST['glatosSeasonalFilter'];
    $json = json_decode(file_get_contents('http://glatos.asascience.com/deployments.geo'));
    for ($i = 0; $i < count($json); $i++) {
      preg_match('/\((.*) (.*)\)/',sprintf("%s",$json[$i]->geojson->geometry),$lonLat);
      $d = array(
         'lon'      => $lonLat[1]
        ,'lat'      => $lonLat[2]
        ,'id'       => sprintf("%s",$json[$i]->geojson->id)
        ,'studyId'  => sprintf("%s",$json[$i]->geojson->properties->study_id)
        ,'start'    => strtotime(sprintf("%s",$json[$i]->geojson->properties->start))
        ,'end'      => strtotime(sprintf("%s",$json[$i]->geojson->properties->ending))
        ,'seasonal' => $json[$i]->geojson->properties->seasonal
        ,'code'     => sprintf("%s",$json[$i]->geojson->properties->code)
        ,'model'    => sprintf("%s",$json[$i]->geojson->properties->model)
      );
      if (!$d['end']) {
        $d['end'] = time();
      }
      $timeOK = ($t0 <= $d['start'] && $d['start'] <= $t1)
        || ($t0 <= $d['end'] && $d['end'] <= $t1)
        || ($d['start'] <= $t0 && $t1 <= $d['end'])
        || (!isset($_REQUEST['t0']) && !isset($_REQUEST['t1']));
      $studiesOK = in_array($d['studyId'],$studiesFilter)
        || (!isset($_REQUEST['glatosStudiesFilter']));
      $modelsOK = in_array($d['model'],$modelsFilter)
        || (!isset($_REQUEST['glatosModelsFilter']));
      $seasonalOK = ($seasonalFilter == 'Seasonal only') ? $d['seasonal'] : true;
      if ($timeOK && $studiesOK && $modelsOK && $seasonalOK) {
        addToStack($metadata,array(-180,-90,180,90),$d['lon'],$d['lat'],$_REQUEST['provider'],array(
           'id'       => $d['id']
          ,'studyId'  => $d['studyId']
          ,'descr'    => ''
          ,'start'    => $d['start']
          ,'end'      => $d['end']
          ,'url'      => 'popupGlatos.php'
            .'?start='.sprintf("%s",$json[$i]->geojson->properties->start)
            .'&end='.sprintf("%s",$json[$i]->geojson->properties->end)
          ,'seasonal' => $d['seasonal']
          ,'code'     => $d['code']
          ,'model'    => $d['model']
        ));
      }
    }
  }

  if ($_REQUEST['provider'] == 'NERRS') {
    require_once('/usr/local/nusoap/lib/nusoap.php');
    nusoap_base::setGlobalDebugLevel(0);
    $u      = substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/'));
    $wsdl   = new nusoap_client('http://cdmo.baruch.sc.edu/webservices2/requests.cfc?wsdl','wsdl');
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
        $k = $d['longitude'].','.$d['latitude'];
        if ($d['Real_time'] == 'R') {
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
        $d['id']    = $station['NERR_SITE_ID'];
        $d['descr'] = $station['reserve_name'];
        $d['lon']   = -$station['longitude'];
        $d['lat']   = $station['latitude'];
        array_push($d['stations'],$station['Station_Code']);
        array_push($d['params'],$station['params_reported']);
      }
      addToStack($metadata,$bbox,$d['lon'],$d['lat'],$provider,array(
         'id'       => $d['id']
        ,'descr'    => sprintf("$provider Station %s - %s",$d['id'],$d['descr'])
        ,'url'      => "popup$provider.php"
          ."?id=".$d['id']
          .'&stations='.implode('|',$d['stations'])
          .'&params='.implode('|',$d['params'])
          .'&lon='.$d['lon']
          .'&lat='.$d['lat']
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
    ,'provider' => $_REQUEST['provider']
    ,'obsId'    => $_REQUEST['obsId']
  ));
?>
