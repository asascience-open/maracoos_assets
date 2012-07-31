<?php
  date_default_timezone_set('UTC');

  $gliders = array();

  $xml = simplexml_load_file("xml/gliders_osu/OSU_Glider_Tracks.kml");
  foreach ($xml->{'Document'}->{'Folder'} as $activeArchive) {
    foreach ($activeArchive->{'Placemark'} as $placemark) {
      if (preg_match("/^Mission (.*)$/",sprintf("%s",$placemark->{'name'}),$matches)) {
        $d = array('id' => $matches[1]);
        foreach ($placemark->{'ExtendedData'}[0]->{'Data'} as $data) {
          $attr = sprintf("%s",$data->attributes()->{'name'});
          if ($attr == 'iw') {
            $d['description'] = sprintf("%s",$data->{'value'}[0]->{'iw'}[0]->{'description'}[0]);
          }
          else if ($attr == 'First Date:') {
            $d['start'] = sprintf("%s",$data->{'value'}[0]);
          }
          else if ($attr == 'Last Date:') {
            $d['end'] = sprintf("%s",$data->{'value'}[0]);
          }
          else if ($attr == 'Type:') {
            $d['type'] = sprintf("%s",$data->{'value'}[0]);
          }
        }
        $d['track'] = mkTrack(explode(' ',sprintf("%s",$placemark->{'LineString'}[0]->{'coordinates'})),$d['start'],$d['end']);
        if (dateIntersects(strtotime($_REQUEST['t0']),strtotime($_REQUEST['t1']),strtotime($d['start']),strtotime($d['end'])) && $_REQUEST['type'] == strtolower($d['type'])) {
          $gliders[$d['id']] = array(
             'deployment' => $d['id']
            ,'active'     => time() - strtotime($d['end']) < 5 * 24 * 3600 ? 1 : 0
            ,'provider'   => 'osu'
            ,'type'       => $d['type']
            ,'track'      => mkTrack(explode(' ',sprintf("%s",$placemark->{'LineString'}[0]->{'coordinates'})),$d['start'],$d['end'])
            ,'url'        => '' // getUrl($d['description'])
          );
        }
      }
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
    $x = simplexml_load_string(str_replace('&','',$description));
    return sprintf("%s",$x->{'a'}[0]->attributes()->{'href'});
  }
?>
