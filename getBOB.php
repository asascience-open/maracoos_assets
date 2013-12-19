<?php
  header('Content-type: text/plain');

  $dbh = new PDO('sqlite:bob.db');
  $xml = @simplexml_load_file('/home/cgalvarino/Temp/130906__V1_20131209094600.OML');

  foreach ($xml->{'StationData'} as $stationData) {
    $a = $stationData->attributes();
    $s = array(
       'id'   => sprintf("%s",$a->{'stationId'})
      ,'name' => sprintf("%s",$a->{'name'})
      ,'tz'   => str_replace(':00',' hours',sprintf("%s",$a->{'timezone'}))
      ,'lon'  => -76.06573
      ,'lat'  => 39.2037
    );
    $c = 0;
    foreach ($dbh->query("select count(*) as c from station where id = '$s[id]'") as $row) {
      $c = $row['c'];
    }
    if ($c == 0) {
      $dbh->exec("insert into station (id,name,lon,lat) values ('$s[id]','$s[name]',$s[lon],$s[lat])");
    }
    foreach ($stationData->{'ChannelData'} as $channelData) {
      $a = $channelData->attributes();
      $d = array(
         'var' => preg_replace('/[^A-Za-z0-9\(\)\. -]/', '',sprintf("%s",$a->{'name'}))
        ,'uom' => preg_replace('/[^A-Za-z0-9\(\)\. -]/', '',sprintf("%s",$a->{'unit'}))
      );
      foreach ($channelData->{'Values'}->{'VT'} as $vt) {
        $d['t'] = sprintf("%sZ",$vt->attributes()->{'t'});
        $d['v'] = sprintf("%s",$vt);
        $sql = sprintf(
          "insert into obs (station,var,uom,t,val) select seq,'%s','%s',%s,'%s' from station where id = '%s'"
          ,$d['var'] 
          ,$d['uom'] 
          ,"strftime('%s','".$d['t']."','".$s['tz']."')"
          ,$d['v']
          ,$s['id']
        );
        print $sql."\n";
        $dbh->exec($sql);
      }
    }
  }
?>
