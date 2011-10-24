<?php
  $pt = ms_newPointObj();
  $pt->setXY($_REQUEST['lon'],$_REQUEST['lat']);

  $m = ms_newMapObj('/home/cpurvis/Temp/nws/zones.map');

  $mz = '';
  $l  = $m->getLayer(2);
  $q  = $l->queryByPoint($pt,MS_SINGLE,0);
  if ($q == MS_SUCCESS) {
    $s  = $l->getShape($l->getResult(0));
    $mz = $s->getValue($l,"ID");
  }

  $oz = '';
  $l  = $m->getLayer(1);
  $q  = $l->queryByPoint($pt,MS_SINGLE,0);
  if ($q == MS_SUCCESS) {
    $s  = $l->getShape($l->getResult(0));
    $oz = $s->getValue($l,"ID");
  }

  echo json_encode(array('marine' => $mz,'offshore' => $oz));
?>
