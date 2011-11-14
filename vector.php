<?php
  $draw = new ImagickDraw();

  $draw->setFillColor('#'.$_REQUEST['color']);

  $icon = new Imagick();
  $icon->newImage($_REQUEST['w'],$_REQUEST['h'],new ImagickPixel('transparent'));
  $icon->setImageFormat('png');
  if ($_REQUEST['type'] == 'arrow') {
    $draw->setFontSize($_REQUEST['w'] * 0.40);
    $draw->setFont('fonts/ESRICartography.ttf');
    $icon->annotateImage(
       $draw
      ,$_REQUEST['w'] / 2
      ,$_REQUEST['w'] / 2
      ,$_REQUEST['dir']
      ,utf8_encode(chr(176))
    );
  }
  if ($_REQUEST['type'] == 'barb') {
    $draw->setFontSize($_REQUEST['w'] * 0.50);
    $draw->setFont('fonts/ESRIWeather.ttf');
    $icon->annotateImage(
       $draw
      ,$_REQUEST['w'] / 2
      ,$_REQUEST['w'] / 2
      ,-$_REQUEST['dir'] - 90
      ,utf8_encode(chr(33 + ($_REQUEST['spd'] - 5) / 5))
    );
  }
  $icon->trimImage(0);
  $dim = $icon->getImageGeometry();

  $canvas = new Imagick();
  $canvas->newImage($_REQUEST['w'],$_REQUEST['h'],new ImagickPixel('transparent'));
  $canvas->setImageFormat('png');
  $canvas->compositeImage($icon,imagick::COMPOSITE_OVER,$_REQUEST['w'] / 2 - $dim['width'] / 2,$_REQUEST['h'] / 2 - $dim['height'] / 2);

  header('Content-type: image/png');
  echo $canvas;
?>
