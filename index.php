<!DOCTYPE html>
<?php
  ob_start();
  session_start();
  require_once('auth.php');

  $title  = 'MARACOOS Assets';
  $version = '0.006';

  $config = isset($_REQUEST['config']) ? $_REQUEST['config'] : (getenv('config') ? getenv('config') : null);
  $_SESSION['config'] = $config;

  switch ($config) {
    case 'gliders' : $title = 'IOOS Glider'; break;
    case 'ecop'    : $title = 'CoastMap'; break;
    case 'cari'    : $title = 'CariCOOS'; break;
    case 'pr'      : $title = 'CariCOOS'; $config = 'cari'; break;
  }

  if ($config == 'ecop' && !checkLoggedin()) {
    header('Location: login.php');
    return;
  }
?>

<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=8; IE=7;">
    <title><?php echo $title?> Explorer</title>
    <link rel="stylesheet" type="text/css" href="./js/ext-3.3.0/resources/css/ext-all.css"/>
    <link rel="stylesheet" type="text/css" href="style.css?v=<?php echo $version?>"/>
    <!--[if IE]>
      <link rel="stylesheet" type="text/css" href="style.ie.css?v=<?php echo $version?>" />
    <![endif]-->

    <script>
      var globalTitle     = '<?php echo $title?>';
      var globalTitleOverride;
      var config          = 'assets';
      var helpUnavailable = false;
      var fdbkUnavailable = false;
<?php
  if (isset($config)) {
    echo "config = '$config';\n";
  }
?>
      var restrictLayers;
      var defaultLayers = {
         'NDBC'           : true
        ,'HYCOM currents'    : true
        ,'NHC storm tracks'  : true
      };
      var filterOutLayers = {
         'HYCOM currents (global)'     : true
        ,'NCOM currents (S Atlantic)'           : true
        ,'ROMS currents (Caribbean)' : true
        ,'HF radar currents (PR-VI)'  : true
        ,'SWAN wave height (Caribbean)'              : true
        ,'SWAN wave direction (Caribbean)' : true
        ,'SWAN wave period (Caribbean)' : true
        ,'WWIII wave direction'    : true
        ,'WWIII wave period'       : true
        ,'HYCOM elevation (global)'    : true
        ,'NCOM SST'                : true
        ,'ROMS SST'                : true
        ,'HYCOM salinity (global)' : true
        ,'NCOM salinity (S Atlantic)'           : true
        ,'ROMS salinity (Caribbean)' : true
        ,'Chlorophyll concentration (VIIRS)'      : true
        ,'GFS winds'               : true
        ,'WRF winds (Caribbean)'   : true
      };
      var defaultStyles = {
         'Chlorophyll concentration (VIIRS)'  : 'SST_USCG-0-2'
        ,'Chlorophyll concentration'          : 'pcolor_average_jet_0.01_15_50_Log'
        ,'ESPRESSO bottom temperature'        : 'pcolor_average_jet_0_30_60_False'
        ,'GFS winds'                          : 'WINDS_VERY_SPARSE_GRADIENT-False-2-0-45-High'
        ,'HF radar currents (PR-VI)'          : 'CURRENTS_RAMP-Jet-False-2-True-0-2-High'
        ,'HF radar currents'                  : 'CURRENTS_RAMP-Jet-False-3-True-0-2-High'
        ,'HOPS'                               : 'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
        ,'HYCOM currents (global)'            : 'CURRENTS_RAMP-Jet-False-2-True-0-2-High'
        ,'HYCOM currents'                     : 'CURRENTS_RAMP-Jet-False-2-True-0-2-High'
        ,'HYCOM elevation (global)'           : 'WATER_ELEVATION_STYLE-0-2'
        ,'HYCOM salinity (global)'            : 'SST_USCG-0-50'
        ,'HYCOM SST'                          : 'WATER_TEMP-0-35'
        ,'NAM winds'                          : 'WINDS_VERY_SPARSE_GRADIENT-False-1-0-45-High'
        ,'NCOM currents (S Atlantic)'         : 'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
        ,'NCOM salinity (S Atlantic)'         : 'SST_USCG-0-50'
        ,'ROMS salinity (Caribbean)'          : 'SST_USCG-0-50'
        ,'NCOM SST'                           : 'SST_USCG-0-50'
        ,'ROMS SST'                           : 'SST_USCG-0-50'
        ,'ROMS currents (Caribbean)'          : 'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
        ,'ROMS ESPRESSO'                      : 'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
        ,'ROMS'                               : 'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
        ,'SABGOM currents'                    : 'CURRENTS_RAMP-Jet-False-2-True-0-2-High'
        ,'NW_ATL currents'                    : 'CURRENTS_RAMP-Jet-False-2-True-0-2-High'
        ,'Satellite water temperature'        : 'pcolor_average_jet_5_32_54_False'
        ,'Stevens NYHOPS'                     : 'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
        ,'STPS'                               : 'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
        ,'SWAN wave direction (Caribbean)'    : ''
        ,'SWAN wave height (Caribbean)'       : 'WAVE_HEIGHT_STYLE-0-7'
        ,'SWAN wave period (Caribbean)'       : 'WAVE_PERIOD_STYLE-0-17'
        ,'UMass'                              : 'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
        ,'WRF winds (Caribbean)'              : 'WINDS_VERY_SPARSE_GRADIENT-False-2-0-45-High'
        ,'WWIII wave direction'               : ''
        ,'WWIII wave height'                  : 'WAVE_HEIGHT_STYLE-0-7'
        ,'WWIII wave period'                  : 'WAVE_PERIOD_STYLE-0-17'
      };
      var hideLegendsGridPanel      = false;
      var hideTimeseriesPanel       = false;
      var hideAssetsGridPanel       = false;
      var hideCurrentsGridPanel     = true;
      var hideWindsGridPanel        = true;
      var hideWavesGridPanel        = true;
      var hideTemperaturesGridPanel = true;
      var hideOtherGridPanel        = true;
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
      var ignoreMapClick            = false;
      var introPanelHtmlOverride;
      var introPanelHeightOverride;

      var defaultBasemap = 'ESRI Ocean';

      var defaultCenter  = [-7792364.3544444,4865942.2788258];
      var defaultZoom    = 6;

      var showHelpOnStartup = true;

      if (config == 'cari') {
        introPanelHtmlOverride  = '<table class="smallFont" width="100%"><tr><td align=center><a target=_blank href="http://www.caricoos.org/"><img title="Go to the CariCOOS home page" src="img/caricoos.png"></a></td></tr></table>';
        showHelpOnStartup = false;
        helpUnavailable = true;
        fdbkUnavailable = true;

        defaultLayers = {
           'NDBC'               : true
          ,'HYCOM currents (global)': true
        };
        restrictLayers = {
           'NDBC'                    : true
          ,'Weatherflow'             : true
          ,'HYCOM currents (global)'     : true
          ,'ROMS currents (Caribbean)' : true
          ,'HF radar currents (PR-VI)'  : true
          ,'SWAN wave height (Caribbean)'              : true
          ,'SWAN wave direction (Caribbean)' : true
          ,'SWAN wave period (Caribbean)' : true
          ,'WWIII wave height'             : true
          ,'WWIII wave direction'    : true
          ,'WWIII wave period'       : true
          ,'HYCOM elevation (global)'    : true
          ,'HYCOM SST'               : true
          ,'ROMS SST'                : true
          ,'HYCOM salinity (global)' : true
          ,'ROMS salinity (Caribbean)' : true
          ,'Chlorophyll concentration (VIIRS)'      : true
          ,'GFS winds'               : true
          ,'WRF winds (Caribbean)'   : true
          ,'HF radar currents (2km)' : true
          ,'HF radar currents (6km)' : true
        };
        filterOutLayers = {};

        defaultCenter = [-8035739.8524704,2352692.7891591];
        defaultZoom   = 5;

<?php
  if (isset($_REQUEST['config']) && $_REQUEST['config'] == 'pr') {
?>
          defaultCenter = [-7388330.11319,2068143.2633338];
          defaultZoom   = 8;
<?php
  }
?>
      }

      if (config == 'gliders') {
        introPanelHtmlOverride  = '<table class="smallFont" width="100%"><tr><td align=center><a target=_blank href="http://www.ioos.noaa.gov/"><img title="Go to the IOOS home page" src="img/ioos.gif"></a></td></tr></table>';
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
          ,'WWIII wave height'         : true
          ,'NCOM SST'            : true
          ,'NCOM currents (S Atlantic)'       : true
          ,'GFS winds'           : true
        };
        filterOutLayers = {};

        hideGlidersYearsFormPanel = false;
        hideTimeseriesPanel       = true;
        hideAssetsGridPanel       = true;
        hideObservationsGridPanel = true;
        hideTimeSlider            = true;
        hideGlidersGridPanel      = false;
        showHelpOnStartup         = false;
        managerPanelCollapsible   = false;
        hideTimestampLabel        = true;
        hideRealtimeAlert         = false;
        ignoreMapClick            = true;

        defaultBasemap            = 'ESRI Ocean';

        defaultCenter  = [-4285461.0031831,802319.92876964];
        defaultZoom    = 2;
      }
      else if (config == 'ecop') {
        introPanelHtmlOverride  = '<table class="smallFont" width="100%"><tr><td align=center><a target=_blank href="http://www.asascience.com/"><img title="Go to ASA\'s home page" src="img/asa.jpg"></a></td></tr></table>';
        introPanelHeightOverride  = 52;
        hideModelsGridPanel       = true;
        hideAssetsGridPanel       = true;
        hideObservationsGridPanel = true;
        hideCurrentsGridPanel     = false;
        hideWindsGridPanel        = false;
        hideWavesGridPanel        = false;
        hideTemperaturesGridPanel = false;
        hideOtherGridPanel        = false;
        showHelpOnStartup         = false;
        defaultLayers             = {
           'WW3 Wave Hgt||waves'  : true
          ,'NCOM Model||currents' : true
        };
        defaultStyles             = {};
        defaultCenter  = [-2974317.6442192,821850.92800765];
        defaultZoom    = 2;
      }

      var defaultLayerLayers = {
         'Chlorophyll concentration'   : 'http://wms.maracoos.org/wms/MODIS_Eight_Agg?GFI_TIME=min/max&ELEVATION=0&'
        ,'Satellite water temperature' : 'http://wms.maracoos.org/wms/SST_Seven_Agg?GFI_TIME=min/max&ELEVATION=0&'
      };
      var guaranteeDefaultStyles = defaultStyles;
      var defaultOpacities = {
         'ROMS'                        : 100
        ,'HOPS'                        : 100
        ,'STPS'                        : 100
        ,'Stevens NYHOPS'              : 100
        ,'UMass'                       : 100
        ,'ROMS ESPRESSO'               : 100
        ,'NCOM currents (S Atlantic)'  : 100
        ,'HYCOM currents'              : 100
        ,'SABGOM currents'             : 100
        ,'NW_ATL currents'             : 100
        ,'NAM winds'                   : 100
        ,'WWIII wave height'           : 100
        ,'NCOM SST'                    : 100
        ,'HF radar currents'           : 100
        ,'Chlorophyll concentration'   : 100
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
        ,'WWIII wave height'          : 'png'
        ,'ROMS'                 : 'png'
        ,'HOPS'                 : 'png'
        ,'ROMS ESPRESSO'        : 'png'
        ,'STPS'                 : 'png'
        ,'UMass'                : 'png'
        ,'Stevens NYHOPS'       : 'png'
        ,'NCOM currents (S Atlantic)'        : 'png'
        ,'HYCOM currents'       : 'png'
        ,'SABGOM currents'      : 'png'
        ,'NW_ATL currents'      : 'png'
        ,'NAM winds'            : 'png'
        ,'HF radar currents'    : 'png'
        ,'GOES visible imagery' : 'png'
        ,'Navigational Charts'  : 'png'
        ,'ESPRESSO bottom temperature' : 'png'
      }
<?php
  $layers = array();
  if (isset($_REQUEST['lyrs'])) {
    foreach (explode(',',$_REQUEST['lyrs']) as $l) {
      array_push($layers,"'$l' : true");
    }
    echo "defaultLayers = {".implode(',',$layers)."};\n";
  }

  $layers = array();
  if (isset($_REQUEST['lyrs'])) {
    $layers = explode(',',$_REQUEST['lyrs']);
  }

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

  $i = 0;
  if (isset($_REQUEST['lyrLyrs'])) {
    foreach (explode(',',$_REQUEST['lyrLyrs']) as $o) {
?>
       if (defaultLayerLayers[<?php echo json_encode($layers[$i])?>]) {
         defaultLayerLayers[<?php echo json_encode($layers[$i])?>] = <?php echo json_encode($o)?>; 
       }
<?php
      $i++;
    }
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
    <!-- <script type="text/javascript" src="http://www.nhc.noaa.gov/storm_graphics/AT01/inundation/AL0114_inundation.js"></script> -->
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="./js/ext-3.3.0/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="./js/ext-3.3.0/ext-all.js"></script>
    <script type="text/javascript" src="./js/ext-3.3.0/Spotlight.js"></script>
    <script type="text/javascript" src="./js/OpenLayers-2.11-rc2/OpenLayers.js"></script>
	 <script type="text/javascript" src="./js/jquery/jquery.js"></script>
	<script type="text/javascript" src="./js/jquery/jquery.flot.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.time.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.crosshair.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.navigate.js"></script>
    <script type="text/javascript" src="./js/jquery/excanvas.js"></script>
    <script type="text/javascript" src="./js/overlib.js"></script>
    <script type="text/javascript" src="./js/dateFormat.js"></script>
    <script type="text/javascript" src="misc.js?v=<?php echo $version?>"></script>
    <script type="text/javascript" src="map.js.php?v=<?php echo $version?>"></script>
    <div id="overDiv" class="overStyle" style="position:absolute;visibility:hidden;z-index:1000000;"></div>
  </body>
</html>
