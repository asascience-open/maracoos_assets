<?php
  $dest_dir = 'xml';  // everything in here (including the dir)should be writable by the user running this script

  $u = 'http://sdf.ndbc.noaa.gov/sos/server.php?VERSION=1.0.0&SERVICE=SOS&REQUEST=GetCapabilities';
  echo "$u\n";
  file_put_contents("$dest_dir/ndbc.xml",file_get_contents($u));

  $u = 'http://opendap.co-ops.nos.noaa.gov/ioos-dif-sos/SOS?service=SOS&request=GetCapabilities';
  echo "$u\n";
  file_put_contents("$dest_dir/co-ops.xml",file_get_contents($u));

  $u = 'http://www.weatherflow.com/sos/sos.pl?request=GetCapabilities&service=SOS';
  echo "$u\n";
  file_put_contents("$dest_dir/weatherflow.xml",file_get_contents($u));

  $u = 'http://sdf.ndbc.noaa.gov/stations.shtml';
  echo "$u\n";
  file_put_contents("$dest_dir/ndbc_stations.html",file_get_contents($u));

  # wget 'http://www.nefsc.noaa.gov/drifter/drift_X.xml' -O xml/drifters.xml

  # wget --user=2creek_madis_public --password=aA0Z1089nI4p 'https://madis-data.noaa.gov/madisPublic/cgi-bin/madisXmlPublicDir?rdr=&time=0&minbck=-180&minfwd=0&recwin=3&dfltrsel=0&state=AK&latll=0.0&lonll=0.0&latur=90.0&lonur=0.0&stanam=&stasel=0&pvdrsel=1&varsel=1&qcsel=1&xml=1&csvmiss=0&pvd=MARITIME&nvars=SLP&nvars=T&nvars=DD&nvars=FF&nvars=FFGUST&nvars=PSWDIR&nvars=PSWHT&nvars=PSWPER&nvars=SST&nvars=WAVEHT&nvars=WAVEPER' -O xml/shipobs.xml

  $f = fopen('coastal_counties.csv','r');
  $col2idx = Array();
  $data = Array();
  while (($d = fgetcsv($f)) !== FALSE) {
    if (count($col2idx) == 0) {
      foreach ($d as $k => $v) {
        $col2idx[$v] = count($col2idx);
      }
    }
    else {
      $state = $d[$col2idx['state']];
      $county = $d[$col2idx['county']];
      if (count($data[$state]) > 0) {
        array_push($data[$state],$state.$county);
      }
      else {
        $data[$state] = Array($state.$county);
      }
    }
  }
  fclose($f);

  $locs = Array(Array('lon','lat','id','descr'));
  foreach (array_keys($data) as $state) {
    for ($i = 0; $i < count($data[$state]) / 20; $i++) {  // max # of counties / request = 20
      $counties = Array();
      for ($j = $i * 20; $j < $i * 20 + 20; $j++) {
        if ($j < count($data[$state])) {
          array_push($counties,$data[$state][$j]);
        }
      }
      $u = sprintf("http://waterservices.usgs.gov/nwis/iv?format=waterml&countyCd=%s",implode(',',$counties));
      echo "$u\n";
      $xml = @simplexml_load_file($u);
      foreach ($xml->children('http://www.cuahsi.org/waterML/1.1/')->{'timeSeries'} as $ts) {
        $id = explode(':',sprintf("%s",$ts->attributes()->{'name'}));
        if (!$locations[$id[1]]) {
          $locations[$id[1]] = true;
          $sourceInfo = $ts->children('http://www.cuahsi.org/waterML/1.1/')->{'sourceInfo'}[0];
          $descr = sprintf("%s",$sourceInfo->children('http://www.cuahsi.org/waterML/1.1/')->{'siteName'}[0]);
          $lon = sprintf("%s",$sourceInfo->children('http://www.cuahsi.org/waterML/1.1/')->{'geoLocation'}[0]->children('http://www.cuahsi.org/waterML/1.1/')->{'geogLocation'}[0]->children('http://www.cuahsi.org/waterML/1.1/')->{'longitude'}[0]);
          $lat = sprintf("%s",$sourceInfo->children('http://www.cuahsi.org/waterML/1.1/')->{'geoLocation'}[0]->children('http://www.cuahsi.org/waterML/1.1/')->{'geogLocation'}[0]->children('http://www.cuahsi.org/waterML/1.1/')->{'latitude'}[0]);
          array_push($locs,Array($lon,$lat,$id[1],$descr));
        }
      }
    }
  }

  $f = fopen("$dest_dir/usgs.csv",'w');
  foreach($locs as $l) {
    fputcsv($f,$l);
  }
  fclose($f);
?>
