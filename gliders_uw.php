<?php
  date_default_timezone_set('UTC');

  $gliders = array();

  if ($handle = opendir('xml/gliders_uw')) {
    while (false !== ($f = readdir($handle))) {
      if (preg_match('/\.kml$/',$f)) {
        $g = array();
        $xml = simplexml_load_file("xml/gliders_uw/$f");
        $g['id']    = sprintf("%s",$xml->{'Document'}[0]->{'name'});
        $g['track'] = array();
        foreach ($xml->{'Document'}[0]->{'Folder'} as $f0) {
          if (preg_match('/Dives$/',sprintf("%s",$f0->attributes()->{'id'}))) {
            foreach ($f0->{'Folder'} as $f1) {
              foreach ($f1->{'Placemark'} as $p) {
                if (array_key_exists('Point',$p)) {
                  $c = explode(',',sprintf("%s",$p->{'Point'}[0]->{'coordinates'}));
                  array_push($g['track'],array(
                     'timestamp' => date("Y-m-d",strtotime(preg_replace('/GPS fix at |Dive finished /','',sprintf("%s",$p->{'description'}))))
                    ,'lon'       => $c[0]
                    ,'lat'       => $c[1]
                  ));
                  if (!array_key_exists('start',$g)) {
                    $g['start'] = date("Y-m-d",strtotime(preg_replace('/GPS fix at |Dive finished /','',sprintf("%s",$p->{'description'}))));
                  }
                  $g['end'] = date("Y-m-d",strtotime(preg_replace('/GPS fix at |Dive finished /','',sprintf("%s",$p->{'description'}))));
                }
              }
            }
          }
        }
        if (dateIntersects(strtotime($_REQUEST['t0']),strtotime($_REQUEST['t1']),strtotime($g['start']),strtotime($g['end']))) {
          $gliders[$g['id']] = array(
             'deployment' => $g['id']
            ,'active'     => time() - strtotime($g['end']) < 5 * 24 * 3600 ? 1 : 0
            ,'provider'   => 'uw'
            ,'type'       => 'seaglider'
            ,'track'      => $g['track']
          );
        }
      }
    }
  }

  echo json_encode($gliders);

  function dateIntersects($r1start,$r1end,$r2start,$r2end) {
    return ($r1start == $r2start) || ($r1start > $r2start ? $r1start <= $r2end : $r2start <= $r1end);
  }
?>
