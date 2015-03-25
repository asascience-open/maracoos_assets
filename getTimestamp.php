<?php
  date_default_timezone_set('UTC');

  if (preg_match("/HF radar currents (.*)/",$_REQUEST['lyrName']) === 1) {
    $radar = array(
       'HF radar currents (1km)' => array('prefix' => 'a','resolution' => '1000')
      ,'HF radar currents (2km)' => array('prefix' => 'a','resolution' => '2000')
      ,'HF radar currents (6km)' => array('prefix' => 'a','resolution' => '6000')
    ); 
    $json = json_decode(file_get_contents('http://hfrnet.ucsd.edu/rtv/ts.php'),true);
    for ($i = 0; $i < count($json); $i++) {
      if ($json[$i]['prefix'] == $radar[$_REQUEST['lyrName']]['prefix'] && $json[$i]['resolution'] == $radar[$_REQUEST['lyrName']]['resolution']) {
        if (strtotime($_REQUEST['mapTime'].'Z') > $json[$i]['latest']) {
          echo 'latest='.$json[$i]['latest'];
        }
        else {
          echo 'dateNotAvailable';
        }
        return;
      }
    }
    echo 'dateNotAvailable';
    return;
  }

  $u = substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'?')+1);
  $c = get_headers(urldecode($u));
  for ($i = 0; $i < count($c); $i++) {
    if (preg_match('/Warning: (99 Nearest value used: time=)*([^ ]*)/i',$c[$i],$match)) {
      $d = $match[2];
      if ($d == 'invalidBbox') {
        echo 'invalidBbox';
      }
      else if ($d == 'DateNotAvailable') {
        echo 'dateNotAvailable';
      }
      else {
        if (substr($d,strlen($d)-1,1) != 'Z') {
          $d .= 'Z';
        }
        echo strtotime($d);
      }
    }
  }
?>
