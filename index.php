<html>
  <head>
<?php
  $title = 'MARACOOS Assets';
  if (isset($_REQUEST['config'])) {
    if ($_REQUEST['config'] == 'gliders') {
      $title = 'IOOS Glider';
    }
  }
?>
    <title><?php echo $title?> Explorer</title>
    <link rel="stylesheet" type="text/css" href="./js/ext-3.3.0/resources/css/ext-all.css"/>
    <link rel="stylesheet" type="text/css" href="style.css?v=4"/>
    <!--[if IE]>
      <link rel="stylesheet" type="text/css" href="style.ie.css?v=4" />
    <![endif]-->

    <script>
      var globalTitle     = '<?php echo $title?>';
      var globalTitleOverride;
      var config          = 'assets';
      var helpUnavailable = false;
      var fdbkUnavailable = false;
<?php
  if (isset($_REQUEST['config'])) {
    echo 'config = "'.$_REQUEST['config'].'"'.";\n";
  }
?>

      var restrictLayers;
      var defaultLayers = {
         'NDBC'             : true
        ,'NERRS'            : true
        ,'NCOM currents'    : true
        ,'WWIII waves'      : true
      };
      var defaultStyles = {
         'Satellite water temperature' : 'boxfill/rainbow'
        ,'ROMS'                        : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'HOPS'                        : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'ROMS ESPRESSO'               : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'STPS'                        : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'UMass'                       : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'Stevens NYHOPS'              : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'NCOM currents'               : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'HYCOM currents'              : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'NAM winds'                   : 'WINDS_VERY_SPARSE_GRADIENT-False-1-0-45'
        ,'HF radar currents'           : 'CURRENTS_RAMP-Jet-False-1-True-0-2'
        ,'WWIII waves'                 : ''
        ,'NCOM SST'                    : ''
      };
      var hideLegendsGridPanel      = false;
      var hideTimeseriesPanel       = false;
      var hideAssetsGridPanel       = false;
      var hideModelsGridPanel       = false;
      var hideObservationsGridPanel = false;
      var hideMarineGridPanel       = true;
      var hideGlidersGridPanel      = true;
      var hideGlatosGridPanel       = true;
      var hideTimeSlider            = false;
      var hideMapToolbar            = false;
      var hideMapTitle              = false;
      var hideIntroPanel            = false;
      var managerPanelCollapsible   = true;
      var hideTimestampLabel        = false;
      var hideMouseControl          = false;
      var hideGlidersYearsFormPanel = true;
      var hideRealtimeAlert         = true;
      var introPanelHtmlOverride;
      var introPanelHeightOverride;
      var maxZoomLevel;

      var mapBanner = {hidden : true};

      var defaultBasemap = 'ESRI Ocean';

      var defaultCenter  = [-7792364.3544444,4865942.2788258];
      var defaultZoom    = 6;

      var showHelpOnStartup = true;

      if (config == 'gliders') {
        introPanelHtmlOverride  = '<table class="smallFont" width="100%"><tr><td align=center><a target=_blank href="http://www.ioos.gov/"><img title="Go to the IOOS home page" src="img/ioos.gif"></a></td></tr></table>';
        helpUnavailable = true;
        fdbkUnavailable = true

        defaultLayers = {
           'Sea gliders'     : true
          ,'Slocum gliders'  : true
          ,'Spray gliders'   : true
          ,'Unknown gliders' : true
        };
        restrictLayers = {
           'Bathymetry contours' : true
          ,'Sea gliders'         : true
          ,'Slocum gliders'      : true
          ,'Spray gliders'       : true
          ,'Unknown gliders'     : true
          ,'HF radar currents'           : true
          ,'Satellite water temperature' : true
        };
        defaultStyles['HF radar currents'] = 'CURRENTS_RAMP-Jet-False-4-True-0-2';
        hideGlidersYearsFormPanel = false;
        hideTimeseriesPanel       = true;
        hideAssetsGridPanel       = true;
        hideModelsGridPanel       = true;
        hideTimeSlider            = true;
        hideGlidersGridPanel      = false;
        showHelpOnStartup         = false;
        managerPanelCollapsible   = false;
        hideTimestampLabel        = true;
        hideRealtimeAlert         = false;

        defaultBasemap            = 'ESRI Ocean';

        defaultCenter  = [-10429775.084004,3815773.3314648];
        defaultZoom    = 3;
      }

      var defaultLayerLayers = {
        'Satellite water temperature' : 'sst-seven/mcsst'
      };
      var guaranteeDefaultStyles = defaultStyles;
      var defaultOpacities = {
         'ROMS'                        : 100
        ,'HOPS'                        : 100
        ,'STPS'                        : 100
        ,'Stevens NYHOPS'              : 100
        ,'UMass'                       : 100
        ,'ROMS ESPRESSO'               : 100
        ,'NCOM currents'               : 100
        ,'HYCOM currents'              : 100
        ,'NAM winds'                   : 100
        ,'WWIII waves'                 : 100
        ,'NCOM SST'                    : 100
        ,'HF radar currents'           : 100
        ,'Satellite water temperature' : 100
        ,'GOES visible imagery'        : 100
        ,'NHC storm tracks'            :  65
        ,'Zones'                       : 100
        ,'WWA'                         :  75
        ,'Open StreetMap'              : 100
        ,'Google Satellite'            : 100
        ,'Google Terrain'              : 100
        ,'Google Hybrid'               : 100
        ,'ESRI Ocean'                  : 100
        ,'Sea gliders'                 : 100
        ,'Slocum gliders'              : 100
        ,'Spray gliders'               : 100
        ,'Unknown gliders'             : 100
      };
      var guaranteeDefaultOpacities = defaultOpacities;
      var defaultImageTypes = {
         'NCOM SST'             : 'png'
        ,'WWIII waves'          : 'png'
        ,'ROMS'                 : 'png'
        ,'HOPS'                 : 'png'
        ,'ROMS ESPRESSO'        : 'png'
        ,'STPS'                 : 'png'
        ,'UMass'                : 'png'
        ,'Stevens NYHOPS'       : 'png'
        ,'NCOM currents'        : 'png'
        ,'HYCOM currents'       : 'png'
        ,'NAM winds'            : 'png'
        ,'HF radar currents'    : 'png'
        ,'GOES visible imagery' : 'png'
        ,'Navigational Charts'  : 'png'
        ,'Satellite water temperature' : 'png'
      }
<?php
  $layers = array();
  if (isset($_REQUEST['lyrs'])) {
    foreach (explode(',',$_REQUEST['lyrs']) as $l) {
      array_push($layers,"'$l' : true");
    }
    echo "defaultLayers = {".implode(',',$layers)."};\n";
  }

  $layers = explode(',',$_REQUEST['lyrs']);
  $styles = array();
  if (isset($_REQUEST['styls'])) {
    foreach (explode(',',$_REQUEST['styls']) as $s) {
      array_push($styles,"'".$layers[count($styles)]."' : '$s'");
    }
    echo "defaultStyles = {".implode(',',$styles)."};\n";
  }

  $opacities = array();
  if (isset($_REQUEST['opcty'])) {
    foreach (explode(',',$_REQUEST['opcty']) as $o) {
      array_push($opacities,"'".$layers[count($opacities)]."' : '$o'");
    }
    echo "defaultOpacities = {".implode(',',$opacities)."};\n";
  }

  $layerLayers = array();
  if (isset($_REQUEST['lyrLyrs'])) {
    foreach (explode(',',$_REQUEST['lyrLyrs']) as $o) {
      array_push($layerLayers,"'".$layers[count($layerLayers)]."' : '$o'");
    }
    echo "defaultLayerLayers = {".implode(',',$layerLayers)."};\n";
  }

  $i = 0;
  if (isset($_REQUEST['imgTyps'])) {
    foreach (explode(',',$_REQUEST['imgTyps']) as $t) {
      echo "defaultImageTypes['".$layers[$i]."'] = '$t';\n";
      $i++;
    }
  }

  if (isset($_REQUEST['center'])) {
    echo "defaultCenter = [".$_REQUEST['center']."];\n";
  }

  if (isset($_REQUEST['zoom'])) {
    echo "defaultZoom = ".$_REQUEST['zoom'].";\n";
  }

  if (isset($_REQUEST['base'])) {
    echo "defaultBasemap = '".$_REQUEST['base']."';\n";
  }

  if (isset($_REQUEST['esriO']) && $_REQUEST['esriO'] != '') {
    echo "defaultBasemap = 'ESRI Ocean';\n"; 
    echo "defaultOpacities['ESRI Ocean'] = '".$_REQUEST['esriO']."';\n";
    echo "defaultOpacities['Open StreetMap'] = '".$_REQUEST['esriO']."';\n";
  }

  if (isset($_REQUEST['navC']) && $_REQUEST['navC'] != '') {
    echo "defaultBasemap = 'Navigational Charts';\n";
    echo "defaultOpacities['Navigational Charts'] = '".$_REQUEST['navC']."';\n";
    echo "defaultOpacities['Open StreetMap'] = '".$_REQUEST['navC']."';\n";
  }
?>
      for (var s in guaranteeDefaultStyles) {
        if (!defaultStyles[s]) {
          defaultStyles[s] = guaranteeDefaultStyles[s];
        }
      }
    </script>

    <script type="text/javascript">
      var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
      document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
    </script>
    <script type="text/javascript">
      try{
        var pageTracker = _gat._getTracker("UA-25332621-1");
        pageTracker._trackPageview();
      } catch(err) {}
    </script>

  </head>
  <body onload="Ext.onReady(function(){init()})">
    <div id="loading-mask"></div>
    <div id="loading">
      <span id="loading-message">Loading core API. Please wait...</span>
    </div>
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="./js/ext-3.3.0/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="./js/ext-3.3.0/ext-all.js"></script>
    <script type="text/javascript" src="./js/ext-3.3.0/Spotlight.js"></script>
    <script type="text/javascript" src="./js/OpenLayers-2.11-rc2/OpenLayers.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.crosshair.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.navigate.js"></script>
    <script type="text/javascript" src="./js/jquery/excanvas.js"></script>
    <script type="text/javascript" src="./js/overlib.js"></script>
    <script type="text/javascript" src="misc.js?v=4"></script>
    <script type="text/javascript" src="map.js.php?v=4"></script>
    <div id="overDiv" class="overStyle" style="position:absolute;visibility:hidden;z-index:1000000;"></div>
  </body>
</html>
