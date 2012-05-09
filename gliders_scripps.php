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
          $g['end'] = date("Y-m-d",time());
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
            ,'active'     => time() - strtotime($g['end']) < 5 * 24 * 3600 ? 1 : 0
            ,'provider'   => 'scripps'
            ,'type'       => 'spray'
            ,'track'      => $g['track']
            ,'url'        => $g['info'].(time() - strtotime($g['end']) < 5 * 24 * 3600 ? 'act' : '')
          );
        }
      }
    }
  }

  // hit the db for archived positions
  $dbh = sqlite_open('db/gliders.db',0666,$error);
  sqlite_create_function($dbh,'dateIntersects','dateIntersects',4);

  $depSql = <<<EOSQL
select
  *
from
   deployment
  ,type
  ,provider
where
  dateIntersects(t_start,t_end,'%s','%s')
  and deployment.provider = provider.seq
  and deployment.type = type.seq
  and provider.id = 'scripps'
  and type.id = 'spray'
EOSQL;

  $trkSql = <<<EOSQL
select
  *
from
  track
where
  deployment = %d
EOSQL;

  $depRes = sqlite_query($dbh,sprintf($depSql,$_REQUEST['t0'],$_REQUEST['t1']));
  while ($depRow = sqlite_fetch_array($depRes,SQLITE_ASSOC)) {
    $d = array(
       'deployment' => $depRow['deployment.id']
      ,'active'     => time() - strtotime($depRow['deployment.t_end']) < 5 * 24 * 3600 ? 1 : 0
      ,'provider'   => $depRow['provider.id']
      ,'type'       => $depRow['type.id']
      ,'track'      => array()
      ,'url'        => $depRow['deployment.url'].(time() - strtotime($g['end']) < 5 * 24 * 3600 ? 'act' : '')
    );
    $trkRes = sqlite_query($dbh,sprintf($trkSql,$depRow['deployment.seq']));
    while ($trkRow = sqlite_fetch_array($trkRes,SQLITE_ASSOC)) {
      array_push($d['track'],array(
         'timestamp' => $trkRow['t']
        ,'lon'       => $trkRow['lon']
        ,'lat'       => $trkRow['lat']
      ));
    }
    $gliders[$d['deployment']] = $d;
  }

  sqlite_close($dbh);

  echo json_encode($gliders);

  function dateIntersects($r1start,$r1end,$r2start,$r2end) {
    return ($r1start == $r2start) || ($r1start > $r2start ? $r1start <= $r2end : $r2start <= $r1end);
  }
?>
