<?php
  date_default_timezone_set('UTC');

  $gliders = array();

  $xml = simplexml_load_file("xml/gliders_glos/unit_236.kml");
  foreach ($xml->{'Document'}->{'Placemark'} as $placemark) {
    $start = '';
    $end   = '';
    $descr = '';
    foreach ($placemark->{'ExtendedData'}->{'Data'} as $data) {
      if ($data['name'] == 'Begin') {
        $start = sprintf("%s",$data->{'value'});
      }
      else if ($data['name'] == 'End') {
        $end = sprintf("%s",$data->{'value'});
      }
      else if ($data['name'] == 'iw') {
        $descr = sprintf("%s",$data->{'value'});
      }
    }
    $d = array(
       'id'          => sprintf("%s",$placemark->{'name'})
      ,'description' => $descr
      ,'start'       => $start
      ,'end'         => $end
      ,'track'       => mkTrack(explode(' ',sprintf("%s",$placemark->{'LineString'}[0]->{'coordinates'})),$d['start'],$d['end'])
    );
    if (dateIntersects(strtotime($_REQUEST['t0']),strtotime($_REQUEST['t1']),strtotime($d['start']),strtotime($d['end']))) {
      $gliders[$d['id']] = array(
         'deployment' => $d['id']
        ,'active'     => $d['end'] != '' ? (time() - strtotime($d['end']) < 5 * 24 * 3600 ? 1 : 0) : 1
        ,'provider'   => 'glos'
        ,'type'       => 'slocum'
        ,'track'      => mkTrack(explode(' ',sprintf("%s",$placemark->{'LineString'}[0]->{'coordinates'})),$d['start'],$d['end'])
        ,'url'        => getUrl($d['description'])
      );
    }
  }

  echo json_encode($gliders);

  function dateIntersects($r1start,$r1end,$r2start,$r2end) {
    return $r2end == '' || (($r1start == $r2start) || ($r1start > $r2start ? $r1start <= $r2end : $r2start <= $r1end));
  }

  function mkTrack($coords,$startTime,$endTime) {
    $trk = array();
    for ($i = 0; $i < count($coords); $i++) {
      if ($coords[$i] != '') {
        $p = explode(',',$coords[$i]);
        array_push($trk,array(
           'timestamp' => $startTime
          ,'lat'       => $p[1]
          ,'lon'       => $p[0]
        ));
      }
    }
    if (count($trk) > 0) {
      $trk[count($trk) - 1]['timestamp'] = $endTime;
    }
    return $trk;
  }

  function getUrl($description) {
    preg_match("/href=\"([^\"]*)/",$description,$matches);
    return $matches[1];
  }
?>
