<?php
  date_default_timezone_set('UTC');

  $gliders = array();

  $xml = simplexml_load_file("xml/gliders_gerg/real.kml");
  foreach ($xml->{'Document'}->{'Placemark'} as $placemark) {
    $d = array(
       'id'          => sprintf("%s",$placemark->{'name'})
      ,'description' => sprintf("%s",$placemark->{'description'})
      ,'start'       => sprintf("%s",$placemark->{'TimeSpan'}[0]->{'begin'})
      ,'end'         => sprintf("%s",$placemark->{'TimeSpan'}[0]->{'end'})
      ,'track'       => mkTrack(explode(' ',sprintf("%s",$placemark->{'LineString'}[0]->{'coordinates'})),$d['start'],$d['end'])
    );
    if (dateIntersects(strtotime($_REQUEST['t0']),strtotime($_REQUEST['t1']),strtotime($d['start']),strtotime($d['end']))) {
      $gliders[$d['id']] = array(
         'deployment' => $d['id']
        ,'active'     => time() - strtotime($d['end']) < 5 * 24 * 3600 ? 1 : 0
        ,'provider'   => 'gerg'
        ,'type'       => 'slocum'
        ,'track'      => mkTrack(explode(' ',sprintf("%s",$placemark->{'LineString'}[0]->{'coordinates'})),$d['start'],$d['end'])
        ,'url'        => getUrl($d['description'])
      );
    }
  }

  echo json_encode($gliders);

  function dateIntersects($r1start,$r1end,$r2start,$r2end) {
    return ($r1start == $r2start) || ($r1start > $r2start ? $r1start <= $r2end : $r2start <= $r1end);
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
    preg_match("/href='([^']*)'/",$description,$matches);
    return $matches[1];
  }
?>
