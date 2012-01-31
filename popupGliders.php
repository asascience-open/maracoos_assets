<?php
  $o = array(
    sprintf("<tr><td colspan=2 style='text-align:center'><b>%s</b></td></tr>",$_REQUEST['t'])
  );
  if (isset($_REQUEST['u']) && $_REQUEST['u'] != '') {
    if (preg_match('/sccoos/',$_REQUEST['u'])) {
      $html = @file_get_contents($_REQUEST['u']);
      preg_match('/var lastleg = (.*);/',$html,$matches);
      $lastleg = $matches[1];
      preg_match('/var mission = "(.*)"/',$html,$matches);
      $mission = $matches[1];
      preg_match('/varlist = \[(.*)\]/',$html,$matches);
      $varlist = explode(',',preg_replace('/"| /','',$matches[1]));
      preg_match('/var plotbase = "(.*)"/',$html,$matches);
      $plotbase = $matches[1];
      preg_match('/var largeplotbase = "(.*)"/',$html,$matches);
      $largeplotbase = $matches[1];

      $img = array();
      for ($i = 1; $i <= $lastleg; $i++) {
        $img[$i-1] = array();
        foreach ($varlist as $var) {
          array_push($img[$i-1]
            ,'<a onmouseover="var caption = \'Hi\';overlib(\'<table><tr><td><img src='
              .sprintf($largeplotbase,$mission,$var,$i)
              .'></td></tr></table>\',VAUTO,HAUTO,FGCOLOR,\'#8EAACE\')"><img width=25 src="'
              .sprintf($plotbase,$mission,$var,$i)
            .'" onmouseout=\'nd()\' ></a>');
        }
      }
      $rows = '';
      for ($i = 0; $i < count($img); $i++) {
        $rows .= '<tr><td>'.implode('</td><td>',$img[$i]).'</td></tr>'."\n";
      }
      array_push($o,"<tr><td colspan=2><table>".$rows."</table></td></tr>");
    }
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='".$_REQUEST['u']."'>More observations and glider information</a></td></tr>");
  }

  echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
?>
