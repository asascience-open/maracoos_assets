<?php
  // chmod 777 db

  date_default_timezone_set('UTC');

  $t0 = '2005-01-01';
  $t1 = '2011-12-31';

  $json = json_decode(file_get_contents("http://assets.maracoos.org/gliders_scripps.php?t0=$t0&t1=$t1"),true);

  $dbh = sqlite_open('db/gliders.db',0666,$error);
  if ($error) {
    var_dump($error);
    exit;
  }

  foreach ($json as $dep) {
    $t_start = null;
    $t_end   = null;
    $sql = "insert into deployment(id,provider,type,url) select '".$dep['deployment']."',provider.seq,type.seq,'".$dep['url']."' from provider,type where provider.id = 'scripps' and type.id = 'spray'";
    echo "$sql\n";
    sqlite_exec($dbh,$sql);
    foreach ($dep['track'] as $trk) {
      if (!isset($t_start) || strtotime($trk['timestamp']) < strtotime($t_start)) {
        $t_start = $trk['timestamp'];
      }
      if (!isset($t_end) || strtotime($trk['timestamp']) > strtotime($t_end)) {
        $t_end = $trk['timestamp'];
      }
      $sql = "insert into track (deployment,t,lon,lat) select deployment.seq,'".$trk['timestamp']."',".$trk['lon'].",".$trk['lat']." from deployment,type,provider where deployment.id = '".$dep['deployment']."' and provider in (select seq from provider where id = 'scripps') and type in (select seq from type where id = 'spray')";
      sqlite_exec($dbh,$sql);
    }
    $sql = "update deployment set t_start = '$t_start',t_end = '$t_end' where deployment.id = '".$dep['deployment']."' and provider in (select seq from provider where id = 'scripps') and type in (select seq from type where id = 'spray')";
    echo "$sql\n";
    sqlite_exec($dbh,$sql);
  }

  sqlite_close($dbh);
?>
