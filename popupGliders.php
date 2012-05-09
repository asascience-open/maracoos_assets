<?php
  $o = array(
    sprintf("<tr><td colspan=2 style='text-align:center'><b>%s</b></td></tr>",$_REQUEST['t'])
  );
  if (isset($_REQUEST['u']) && $_REQUEST['u'] != '') {
    if (preg_match('/sccoos/',$_REQUEST['u'])) {
      $html = @file_get_contents($_REQUEST['u']);
      preg_match('/lastleg: (.*)/',$html,$matches);
      $lastleg = $matches[1];
      preg_match('/mission: "(.*)"/',$html,$matches);
      $mission = $matches[1];
      preg_match('/varlist: \[(.*)\]/',$html,$matches);
      $varlist = explode(',',preg_replace('/"| /','',$matches[1]));
      // not sure why this is showing up 2x
      array_pop($varlist);
      preg_match('/plotRoot: "(.*)"/',$html,$matches);
      $plotRoot = $matches[1];

      $img = array();
      for ($i = 1; $i <= $lastleg; $i++) {
        $img[$i-1] = array();
        foreach ($varlist as $var) {
          array_push($img[$i-1]
            ,'<a onmouseover="overlib(\'<table><tr><td><img src='
              .sprintf("%s%s_%s_%s_large.png",$plotRoot,$mission,$var,$i)
              .'></td></tr></table>\',VAUTO,HAUTO,FGCOLOR,\'#8EAACE\')"><img height=19 src="'
              .sprintf("%s%s_%s_%s.png",$plotRoot,$mission,$var,$i)
            .'" onmouseout=\'nd()\' ></a>');
        }
      }
      $rows = '';
      for ($i = 0; $i < count($img); $i++) {
        $rows .= '<tr><td>'.implode('</td><td>',$img[$i]).'</td></tr>'."\n";
      }
      if ($rows != '') {
        array_push($o,"<tr><td colspan=2><table>".$rows."</table></td></tr>");
        array_push($o,"<tr><td colspan=2 style='text-align:center'><font color='gray'>Mouseover a thumbnail to view a larger image.</font></td></tr>");
      }
    }
    else if (preg_match('/washington/',$_REQUEST['u'])) {
      $u = substr($_REQUEST['u'],0,strrpos($_REQUEST['u'],'/') + 1);
      $html = file_get_contents($_REQUEST['u']);
      preg_match_all('/<img src="(pngplot[^"]*)"/',$html,$matches);
      $td = array();
      $tr = array();
      for ($i = 0; $i < count($matches[1]); $i++) {
        $img = $u.$matches[1][$i];
        $big_img = preg_replace('/&scale=([^&]*)/','&scale=0.5',$img);
        array_push($td
          ,'<a onmouseover="overlib(\'<table><tr><td><img src='
            .$big_img
            .'></td></tr></table>\',VAUTO,HAUTO,FGCOLOR,\'#8EAACE\')"><img height=19 src="'
            .$img
          .'" onmouseout=\'nd()\' ></a>'
        );
        if (($i + 1) % 8 == 0 || $i == count($matches[1]) - 1) {
          array_push($tr,'<td>'.implode('</td><td>',$td).'</td>');
          $td = array();
        }
      }
      if (implode('</tr><tr>',$tr) != '') {
        array_push($o,"<tr><td colspan=2><table>"."<tr>".implode('</tr><tr>',$tr)."</tr>"."</table></td></tr>");
        array_push($o,"<tr><td colspan=2 style='text-align:center'><font color='gray'>Mouseover a thumbnail to view a larger image.</font></td></tr>");
      }
    }
    else if (preg_match('/rutgers/',$_REQUEST['u'])) {
      $html = file_get_contents($_REQUEST['u']);
      preg_match_all('/<img src="([^"]*cross_sections[^"]*)"/',$html,$matches);
      $td = array();
      $tr = array();
      for ($i = 0; $i < count($matches[1]); $i++) {
        $img = $matches[1][$i];
        $big_img = preg_replace('/_tn/','_lores',$img);
        array_push($td
          ,'<a onmouseover="overlib(\'<table><tr><td><img src='
            .$big_img
            .'></td></tr></table>\',VAUTO,HAUTO,FGCOLOR,\'#8EAACE\')"><img height=19 src="'
            .$img
          .'" onmouseout=\'nd()\' ></a>'
        );
        if (($i + 1) % 8 == 0 || $i == count($matches[1]) - 1) {
          array_push($tr,'<td>'.implode('</td><td>',$td).'</td>');
          $td = array();
        }
      }
      if (implode('</tr><tr>',$tr) != '') {
        array_push($o,"<tr><td colspan=2><table>"."<tr>".implode('</tr><tr>',$tr)."</tr>"."</table></td></tr>");
        array_push($o,"<tr><td colspan=2 style='text-align:center'><font color='gray'>Mouseover a thumbnail to view a larger image.</font></td></tr>");
      }
    }
    array_push($o,"<tr><td colspan=2 style='text-align:center'><a target=_blank href='".$_REQUEST['u']."'>More observations and glider information</a></td></tr>");
  }

  echo json_encode(Array('html' => '<table class="obsDetails">'.implode('',$o).'</table>'));
?>
