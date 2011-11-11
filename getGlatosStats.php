<?php
  $stats = array(
     'start'  => ''
    ,'end'    => ''
    ,'models' => array()
  );

  $json = json_decode(file_get_contents('http://glatos.asascience.com/deployments.geo'));
  for ($i = 0; $i < count($json); $i++) {
    $d = array(
       'start'    => strtotime(sprintf("%s",$json[$i]->geojson->properties->start))
      ,'end'      => strtotime(sprintf("%s",$json[$i]->geojson->properties->ending))
      ,'model'    => sprintf("%s",$json[$i]->geojson->properties->model)
    );
    if (!$d['end']) {
      $d['end'] = time();
    }
    if (!defined($stats['start']) || $d['start'] < $stats['start']) {
      $stats['start'] = $d['start'];
    }
    if (!defined($stats['end']) || $d['end'] < $stats['end']) {
      $stats['end'] = $d['end'];
    }
    if (!in_array($d['model'],$stats['models'])) {
      array_push($stats['models'],$d['model']);
    }
  }

  sort($stats['models']);
  echo json_encode($stats);
?>
