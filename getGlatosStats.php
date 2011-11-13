<?php
  $stats = array(
     'start'  => time()
    ,'end'    => time()
    ,'models' => array()
  );

  $json = json_decode(file_get_contents('http://glatos.asascience.com/deployments.geo'));
  for ($i = 0; $i < count($json); $i++) {
    $d = array(
       'start'    => strtotime(sprintf("%s",$json[$i]->geojson->properties->start))
      ,'end'      => strtotime(sprintf("%s",$json[$i]->geojson->properties->ending))
      ,'model'    => sprintf("%s",$json[$i]->geojson->properties->model)
    );
    if ($d['start'] < $stats['start']) {
      $stats['start'] = $d['start'];
    }
    if ($d['end'] > $stats['end']) {
      $stats['end'] = $d['end'];
    }
    if (!in_array($d['model'],$stats['models'])) {
      array_push($stats['models'],$d['model']);
    }
  }

  sort($stats['models']);
  echo json_encode($stats);
?>
