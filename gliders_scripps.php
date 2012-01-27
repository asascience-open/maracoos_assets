<?php
  date_default_timezone_set('UTC');

  $gliders = array();

  if ($handle = opendir('xml/gliders_scripps')) {
    while (false !== ($f = readdir($handle))) {
      if (preg_match('/\.kml$/',$f)) {
        $g = array();
        $xml = simplexml_load_file("xml/gliders_scripps/$f");
        foreach ($xml->{'Document'}[0]->{'Style'} as $s) {
          if (sprintf("%s",$s->attributes()->id) == 'sprayIcon') {
            $h = simplexml_load_string(sprintf("%s",$s->{'BalloonStyle'}[0]->{'text'}[0]));
            foreach ($h->{'table'}[0]->{'tr'} as $tr) {
              $val = str_replace("\n",' ',sprintf("%s",$tr->{'td'}[0]));
              switch (sprintf("%s",$tr->{'th'}[0])) {
                case 'Spray'     : $g['id']    = $val; break;
                case 'Start:'    : $g['start'] = date("Y-m-d",strtotime($val.'Z')); break;
                case 'End:'      : $g['end']   = date("Y-m-d",strtotime($val.'Z')); break;
                case 'More Info' : $g['info']  = sprintf("%s",$tr->{'td'}[0]->{'a'}[0]->attributes()->{'href'}); break;
              }
            }
          }
        }
        foreach ($xml->{'Document'}[0]->{'Placemark'} as $p) {
          if (array_key_exists('LineString',$p)) {
            $g['coords'] = explode("\n",str_replace(' ','',sprintf("%s",$p->{'LineString'}[0]->{'coordinates'})));
          }
        }
        if (!array_key_exists('end',$g)) {
          $g['end']    = date("Y-m-d",time());
          $g['active'] = 1;
        }
        else {
          $g['active'] = 0;
        }

        $g['track'] = array();
        for ($i = 0; $i < count($g['coords']); $i++) {
          if ($g['coords'][$i] != '') {
            $p = explode(',',$g['coords'][$i]);
            array_push($g['track'],array(
               'timestamp' => $g['start']
              ,'lat'       => $p[1]
              ,'lon'       => $p[0]
            ));
          }
        }
        if (count($g['track']) > 0) {
          $g['track'][count($g['track']) - 1]['timestamp'] = $g['end'];
        }
        if (dateIntersects(strtotime($_REQUEST['t0']),strtotime($_REQUEST['t1']),strtotime($g['start']),strtotime($g['end']))) {
          $gliders[$g['id']] = array(
             'deployment' => $g['id']
            ,'active'     => $g['active']
            ,'provider'   => 'scripps'
            ,'type'       => 'spray'
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
