<?php
  $layers  = json_decode($_REQUEST['lyr']);
  $legends = json_decode($_REQUEST['leg']);
  $icons   = json_decode($_REQUEST['ico']);

  $tmp_dir = '/tmp/';
  $tmp_url = '/tmp/';
  $id      = time().'.'.rand();

  $canvas = new Imagick();
  for ($i = 0; $i < count($layers); $i++) {
    $handle = fopen($tmp_dir.$id.'.png','w');
    fwrite($handle,@file_get_contents($layers[$i]));
    fclose($handle);
    $img = new Imagick($tmp_dir.$id.'.png');
    if ($i == 0) {
      $canvas = $img->clone();
    }
    else {
      $canvas->compositeImage($img,imagick::COMPOSITE_OVER,0,0);
    }
  }
  $canvas->writeImage($tmp_dir.$id.'.print.png');

  $legTr = array();
  for ($i = 0; $i < count($legends); $i++) {
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
  <body>
    <table>
      <tr><th colspan=2 align=center>MARACOOS Assets Explorer</th></tr>
      <tr>
        <td><img class='mapImg' src='$url_dir$id.print.png'></td>
        <td>$legTable</td>
      </tr>
    </table>
  </body>
</html>
";

$handle = fopen($tmp_url.$id.'.html','w');
fwrite($handle,$html);
fclose($handle);

echo $tmp_url.$id.'.html';
