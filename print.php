<?php
  $iconSysPath = substr($_SERVER['SCRIPT_FILENAME'],0,strrpos($_SERVER['SCRIPT_FILENAME'],'/')+1).'img/';

  $layers   = json_decode($_REQUEST['lyr'],true);
  $legends  = json_decode($_REQUEST['leg']);
  $icons    = json_decode($_REQUEST['ico']);
  $features = json_decode($_REQUEST['ftr'],true);
  $tracks   = json_decode($_REQUEST['trk'],true);
  $basemap  = json_decode($_REQUEST['bm'],true);

  $tmp_dir = '/var/www/html/tmp/';
  $tmp_url = '/tmp/';
  $id      = time().'.'.rand();

  $olay = new Imagick();
  $olay->newImage($_REQUEST['w'],$_REQUEST['h'],new ImagickPixel('transparent'));
  $olay->setImageFormat('png');
  foreach ($layers as $k => $v) {
    $handle = fopen($tmp_dir.$id.'.png','w');
    fwrite($handle,@file_get_contents($layers[$k]['url']));
    fclose($handle);
    $img = new Imagick($tmp_dir.$id.'.png');
    $olay->compositeImage($img,imagick::COMPOSITE_OVER,$layers[$k]['x'],$layers[$k]['y']);
  }
  
  $canvas = new Imagick();
  $canvas->newImage($_REQUEST['w'],$_REQUEST['h'],new ImagickPixel('transparent'));
  $canvas->setImageFormat('png');
  foreach ($basemap as $k => $v) {
    $handle = fopen($tmp_dir.$id.'.bm.png','w');
    fwrite($handle,@file_get_contents($basemap[$k]['url']));
    fclose($handle);
    $img = new Imagick($tmp_dir.$id.'.bm.png');
    $canvas->compositeImage($img,imagick::COMPOSITE_OVER,$basemap[$k]['x'],$basemap[$k]['y']);
  }
  $canvas->compositeImage($olay,imagick::COMPOSITE_OVER,0,0);

  foreach ($tracks as $k => $v) {
    for ($i = 0; $i < count($tracks[$k]); $i++) {
      $draw = new ImagickDraw();
      $draw->setStrokeColor(new ImagickPixel($tracks[$k][$i]['color']));
      if ($tracks[$k][$i]['stroke'] == 'dot') {
        $draw->setStrokeDashArray(array(2,3));
      }
      else {
        $draw->setStrokeWidth(2);
      }
      for ($j = 1; $j < count($tracks[$k][$i]['data']); $j++) {
        $draw->line($tracks[$k][$i]['data'][$j-1][0],$tracks[$k][$i]['data'][$j-1][1],$tracks[$k][$i]['data'][$j][0],$tracks[$k][$i]['data'][$j][1]);
      }
      $canvas->drawImage($draw);
    }
  }

  foreach ($features as $k => $v) {
    $img = new Imagick($iconSysPath.$k.'.png');
    for ($i = 0; $i < count($features[$k]); $i++) {
      $cloneImg = $img->clone();
      // don't rotate for now
      // $cloneImg->rotateImage(new ImagickPixel('none'),$features[$k][$i][2]);
      $dim = $cloneImg->getImageGeometry();
      $canvas->compositeImage($cloneImg,imagick::COMPOSITE_OVER,$features[$k][$i][0] - $dim['width'] / 2,$features[$k][$i][1] - $dim['height'] / 2);
    }
  }

  $canvas->writeImage($tmp_dir.$id.'.print.png');

  $legTr  = array();
  $images = array();
  for ($i = 0; $i < count($legends); $i++) {
    // create local copies of all images
    preg_match('/src="(.*)"/',$legends[$i],$matches);
    if (count($matches) > 0) {
      $handle = fopen($tmp_dir.$id.'.legend.'.$i.'.png','w');
      fwrite($handle,@file_get_contents('http://localhost'.str_replace(' ','%20',$matches[1])));
      fclose($handle);
      array_push($images,$id.'.legend.'.$i.'.png');
      $legends[$i] = str_replace($matches[1],$id.'.legend.'.$i.'.png',$legends[$i]);
    }
    preg_match('/src="(.*)"/',$icons[$i],$matches);
    if (count($matches) > 0) {
      $handle = fopen($tmp_dir.$id.'.icon.'.$i.'.png','w');
      fwrite($handle,@file_get_contents('http://localhost'.str_replace(' ','%20',$matches[1])));
      fclose($handle);
      array_push($images,$id.'.icon.'.$i.'.png');
      $icons[$i] = str_replace($matches[1],$id.'.icon.'.$i.'.png',$icons[$i]);
    }
    array_push($legTr,"<tr><td>$icons[$i]</td><td>$legends[$i]</td></tr>");
  }
  $legTable = '<table>'.implode('',$legTr).'</table>';

$html = "
<html>
  <head>
    <title>MARACOOS Assets Explorer</title>
    <style>
      th {
        font-family : 'Lucida Grande', Arial, Helvetica, sans-serif;
        font-size   : 15px;
      }
      td {
        font-family    : 'Lucida Grande', Arial, Helvetica, sans-serif;
        font-size      : 13px;
        vertical-align : top;
      }
      .mapImg {
        border   : 1px solid #6F94D2;
      }
      .layerIcon {
        margin-top : -1px;
      }
    </style>
  </head>
  <body onload=\"window.print()\">
    <table>
      <tr><th align=center>MARACOOS Assets Explorer</th></tr>
      <tr>
        <td><img class='mapImg' src='$id.print.png'></td>
        <td>$legTable</td>
      </tr>
    </table>
  </body>
</html>
";

$handle = fopen($tmp_dir.$id.'.html','w');
fwrite($handle,$html);
fclose($handle);

if ($_REQUEST['out'] == 'print') {
  echo $tmp_url.$id.'.html';
}
else {
  $cmd = "cd $tmp_dir ; zip"
    .' '.$id.'.zip'
    .' '.$id.'.html'
    .' '.$id.'.print.png'
    .' '.implode(' ',$images);
  `$cmd`;
  echo $tmp_url.$id.'.zip';
}
