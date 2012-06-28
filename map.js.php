<?php
  session_start();
  if ($_SESSION['config'] == 'ecop') {
    include_once('ecopGetCaps.php');
    echo 'var ecop = '.json_encode(array(
       'availableLayers' => $layers
      ,'layerStack'      => $layerStack
    )).";\n";
  }
?>

var cp;
var map;
var legendImages = {};
var proj3857   = new OpenLayers.Projection("EPSG:3857");
var proj900913 = new OpenLayers.Projection("EPSG:900913");
var proj4326   = new OpenLayers.Projection("EPSG:4326");
var helpWin;
var palettesStore = {};
var layersStore   = {};
var baseStylesStore;
var colorMapStore;
var stridingStore;
var barbLabelStore;
var tailMagStore;
var imageQualityStore;
var mainStore;
var assetsStore          = new Ext.data.ArrayStore({fields : []}); 
var currentsStore        = new Ext.data.ArrayStore({fields : []});
var windsStore           = new Ext.data.ArrayStore({fields : []});
var wavesStore           = new Ext.data.ArrayStore({fields : []});
var temperaturesStore    = new Ext.data.ArrayStore({fields : []});
var otherStore           = new Ext.data.ArrayStore({fields : []});
var modelsStore          = new Ext.data.ArrayStore({fields : []});
var observationsStore    = new Ext.data.ArrayStore({fields : []});
var glidersStore         = new Ext.data.ArrayStore({fields : []});
var glidersMetadataStore = new Ext.data.ArrayStore({fields : ['name','description']});
var legendsStore;
var spot;
var spotTooltip;
var activeSettingsWindows = {};
var activeInfoWindows = {};
var obsMinZoom = {
   'NDBC'        : 1 + 5
  ,'CO-OPS'      : 2 + 5
  ,'USGS'        : 5 + 5
  ,'Ship'        : 0 + 5
  ,'NERRS'       : 0 + 5
  ,'MDDNR'       : 0 + 5
//  ,'Weatherflow' : 3 + 5
  ,'HRECOS'      : 0
  ,'HF Radar'    : 0
  ,'Satellites'  : 0
  ,'Gliders'     : 0
  ,'Drifters'    : 0
};
var obsBbox = {};
var obsZoom = {};
var obsBigExtentScale  = 2;  // bigger this # is, the more obs it will cache
var popupObs;
var mouseoverObs;
var popupCtl;
var hiliteCtl;
var lyrQueryPts;
var chartData;
var chartUrls = {};
var chartLayerStore;
var esriOcean;     // special case for this layer
var dNow = new Date();
dNow.setUTCMinutes(0);
dNow.setUTCSeconds(0);
dNow.setUTCMilliseconds(0);
var dNow12Hours = new Date(dNow.getTime());
dNow12Hours.setUTCHours(12);
if (dNow.getHours() >= 12) {
  dNow.setUTCHours(12);
}
else {
  dNow.setUTCHours(0);
}
var lastMapClick = {
   layer : ''
  ,xy    : ''
};
var timeControlsHeight = 42;
var checkPrintTimer;
var lineColors = [
   ['#66C2A5','#1B9E77']
  ,['#FC8D62','#D95F02']
  ,['#8DA0CB','#7570B3']
  ,['#E78AC3','#E7298A']
  ,['#A6D854','#66A61E']
  ,['#FFD92F','#E6AB02']
  ,['#E5C494','#A6761D']
  ,['#B3B3B3','#666666']
];
var gliderTracks = {
   'Slocum gliders' : '#ff00ff'
  ,'Spray gliders'  : '#EB342F'
  ,'Sea gliders'    : '#ff0000'
};
var basemapResolutions = [
   156543.03390625
  ,78271.516953125
  ,39135.7584765625
  ,19567.87923828125
  ,9783.939619140625
  ,4891.9698095703125
  ,2445.9849047851562
  ,1222.9924523925781
  ,611.4962261962891
  ,305.74811309814453
];
var layersToSyncBbox = {};
var needToInitGridPanel = {};

function init() {
  var loadingMask = Ext.get('loading-mask');
  var loading = Ext.get('loading');

  //Hide loading message
  loading.fadeOut({duration : 0.2,remove : true});

  //Hide loading mask
  loadingMask.setOpacity(0.9);
  loadingMask.shift({
     xy       : loading.getXY()
    ,width    : loading.getWidth()
    ,height   : loading.getHeight()
    ,remove   : true
    ,duration : 1
    ,opacity  : 0.1
    ,easing   : 'bounceOut'
  });

  cp = new Ext.state.CookieProvider({
    expires : new Date(new Date().getTime()+(1000*60*60*24*30)) //30 days
  });
  Ext.state.Manager.setProvider(cp);

  Ext.QuickTips.init();

  // don't remember window settings
  Ext.override(Ext.Component,{
    stateful : false
  });

  chartLayerStore =  new Ext.data.ArrayStore({
     id        : 0
    ,fields    : ['rank','name','displayName']
    ,listeners : {
      add     : function(store,recs,idx) {
        Ext.getCmp('chartLayerCombo').setValue(recs[0].get('name'));
      }
      ,remove : function(store) {
        if (store.getCount() > 0) {
          Ext.getCmp('chartLayerCombo').setValue(store.getAt(0).get('name'));
        }
      }
    }
  });

  var introPanel = new Ext.Panel({
     height : introPanelHeightOverride ? introPanelHeightOverride : 48
    ,border : false
    ,html   : introPanelHtmlOverride ? introPanelHtmlOverride : '<table class="smallFont" width="100%"><tr><td align=center><a target=_blank href="http://maracoos.org/"><img title="Go to the MARACOOS home page" src="img/maracoos.jpg"></a></td><td align=center><a target=_blank href="http://www.ioos.gov/"><img title="Go to the IOOS home page" src="img/ioos.gif"></a></td></tr></table>'
  });

  mainStore = new Ext.data.ArrayStore({
    fields : [
       'type'
      ,'name'
      ,'displayName'
      ,'info'
      ,'status'
      ,'settings'
      ,'infoBlurb'
      ,'settingsParam'
      ,'settingsOpacity'
      ,'settingsImageQuality'
      ,'settingsImageType'
      ,'settingsPalette'
      ,'settingsBaseStyle'
      ,'settingsColorMap'
      ,'settingsStriding'
      ,'settingsBarbLabel'
      ,'settingsTailMag'
      ,'settingsMin'
      ,'settingsMax'
      ,'settingsMinMaxBounds'
      ,'rank'
      ,'legend'
      ,'timestamp'
      ,'bbox'
      ,'queryable'
      ,'settingsLayers'
      ,'category'
    ]
<?php
  if ($_SESSION['config'] != 'ecop') {
?>
    ,data  : [
      [
         'gliders'
        ,'Sea gliders'
        ,'Sea gliders'
        ,'off'
        ,defaultLayers['Sea gliders'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Sea gliders.html')))?>'
        ,''
        ,typeof defaultOpacities['Sea gliders'] != 'undefined' && defaultOpacities['Sea gliders'] != '' ? defaultOpacities['Sea gliders'] : 100
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'false'
        ,'-135,0,-50,50'
        ,'false'
        ,''
        ,''
      ]
      ,[
         'gliders'
        ,'Slocum gliders'
        ,'Slocum gliders'
        ,'off'
        ,defaultLayers['Slocum gliders'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Slocum gliders.html')))?>'
        ,''
        ,typeof defaultOpacities['Slocum gliders'] != 'undefined' && defaultOpacities['Slocum gliders'] != '' ? defaultOpacities['Slocum gliders'] : 100
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'false'
        ,'-135,0,-50,50'
        ,'false'
        ,''
        ,''
      ]
      ,[
         'gliders'
        ,'Spray gliders'
        ,'Spray gliders'
        ,'off'
        ,defaultLayers['Spray gliders'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Spray gliders.html')))?>'
        ,''
        ,typeof defaultOpacities['Spray gliders'] != 'undefined' && defaultOpacities['Spray gliders'] != '' ? defaultOpacities['Spray gliders'] : 100
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'false'
        ,'-135,0,-50,50'
        ,'false'
        ,''
        ,''
      ]
      ,[
         'gliders'
        ,'Unknown gliders'
        ,'Unknown gliders'
        ,'off'
        ,defaultLayers['Unknown gliders'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Unknown gliders.html')))?>'
        ,''
        ,typeof defaultOpacities['Unknown gliders'] != 'undefined' && defaultOpacities['Unknown gliders'] != '' ? defaultOpacities['Unknown gliders'] : 100
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'false'
        ,'-135,0,-50,50'
        ,'false'
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'NDBC'    
        ,'NDBC buoys'
        ,'off'
        ,defaultLayers['NDBC'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/NDBC.html')))?>' 
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-78,35.5,-62,44'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'CO-OPS'
        ,'CO-OPS stations'
        ,'off'
        ,defaultLayers['CO-OPS'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/CO-OPS.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-78,35.5,-62,44'
        ,''
        ,''
        ,''
      ]
/*
      ,[
         'asset'
        ,'Weatherflow'
        ,'Weatherflow stations'
        ,'off'
        ,defaultLayers['Weatherflow'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Weatherflow.html')))?>' 
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-78,35.5,-62,44'
        ,''
        ,''
        ,''
      ]
*/
      ,[
         'asset'
        ,'HRECOS'
        ,'HRECOS stations'
        ,'off'
        ,defaultLayers['HRECOS'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/HRECOS.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-75,40.5,-73,43'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'MDDNR'
        ,'Maryland DNR stations'
        ,'off'
        ,defaultLayers['MDDNR'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/MDDNR.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-77.88,37.91,-74.50,40.39'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'NERRS'   
        ,'NERRS stations'
        ,'off'
        ,defaultLayers['NERRS'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/NERRS.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-78,35.5,-62,44'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'USGS'
        ,'USGS stations'
        ,'off'
        ,defaultLayers['USGS'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/USGS.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-78,35.5,-62,44'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'Gliders'
        ,'Gliders'
        ,'off'
        ,defaultLayers['Gliders'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Gliders.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'legends/Gliders.png'
        ,''
        ,'-78,35.5,-62,44'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'Drifters'
        ,'Drifters'
        ,'off'
        ,defaultLayers['Drifters'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Drifters.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'legends/Drifters.png'
        ,''
        ,'-78,35.5,-62,44'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'Ship'
        ,'Ships & drifting buoys'
        ,'off'
        ,defaultLayers['Ship'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Ship.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-78,35.5,-62,44'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'Satellites'
        ,'Satellite ground stations'
        ,'off'
        ,defaultLayers['Satellites'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Satellites.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-76.5,39,-73,41'
        ,''
        ,''
        ,''
      ]
      ,[
         'asset'
        ,'HF Radar'
        ,'HF radar ground stations'
        ,'off'
        ,defaultLayers['HF Radar'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/HF Radar.html')))?>'
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'-160,-60,160,60'
        ,''
        ,''
        ,''
      ]
      ,[
         'model'
        ,'ROMS'    
        ,'Chesapeake currents'
        ,'off'
        ,defaultLayers['ROMS'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/ROMS.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['ROMS'] != 'undefined' && defaultOpacities['ROMS'] != '' ? defaultOpacities['ROMS'] : 100
        ,defaultStyles['ROMS'].split('-')[7]
        ,defaultImageTypes['ROMS']
        ,''
        ,defaultStyles['ROMS'].split('-')[0]
        ,defaultStyles['ROMS'].split('-')[1]
        ,defaultStyles['ROMS'].split('-')[3]
        ,defaultStyles['ROMS'].split('-')[2]
        ,defaultStyles['ROMS'].split('-')[4]
        ,defaultStyles['ROMS'].split('-')[5]
        ,defaultStyles['ROMS'].split('-')[6]
        ,'0-6'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=NOSCBOFSCUR_CURRENTS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['ROMS'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=NOSCBOFSCUR_CURRENTS'
        ,''
        ,'-79,35.5,-74,40'
        ,'true'
        ,''
        ,'currentsVelocity'
      ]
      ,[
         'model'
        ,'STPS'    
        ,'STPS currents'
        ,'off'
        ,defaultLayers['STPS'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/STPS.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['STPS'] != 'undefined' && defaultOpacities['STPS'] != '' ? defaultOpacities['STPS'] : 100
        ,defaultStyles['STPS'].split('-')[7]
        ,defaultImageTypes['STPS']
        ,''
        ,defaultStyles['STPS'].split('-')[0]
        ,defaultStyles['STPS'].split('-')[1]
        ,defaultStyles['STPS'].split('-')[3]
        ,defaultStyles['STPS'].split('-')[2]
        ,defaultStyles['STPS'].split('-')[4]
        ,defaultStyles['STPS'].split('-')[5]
        ,defaultStyles['STPS'].split('-')[6]
        ,'0-6'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=CODARSTPS_CURRENTS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['STPS'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=CODARSTPS_CURRENTS'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'currentsVelocity'
      ]
      ,[
         'model'
        ,'Stevens NYHOPS'
        ,'Stevens NYHOPS currents'
        ,'off'
        ,defaultLayers['Stevens NYHOPS'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Stevens NYHOPS.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['Stevens NYHOPS'] != 'undefined' && defaultOpacities['Stevens NYHOPS'] != '' ? defaultOpacities['Stevens NYHOPS'] : 100
        ,defaultStyles['Stevens NYHOPS'].split('-')[7]
        ,defaultImageTypes['Stevens NYHOPS']
        ,''
        ,defaultStyles['Stevens NYHOPS'].split('-')[0]
        ,defaultStyles['Stevens NYHOPS'].split('-')[1]
        ,defaultStyles['Stevens NYHOPS'].split('-')[3]
        ,defaultStyles['Stevens NYHOPS'].split('-')[2]
        ,defaultStyles['Stevens NYHOPS'].split('-')[4]
        ,defaultStyles['Stevens NYHOPS'].split('-')[5]
        ,defaultStyles['Stevens NYHOPS'].split('-')[6]
        ,'0-6'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=NYHOPSCUR_currents&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['Stevens NYHOPS'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=NYHOPSCUR_currents'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'currentsVelocity'
      ]
//      ,[
//         'model'
//        ,'UMass'
//        ,'UMass currents'
//        ,'off'
//        ,defaultLayers['UMass'] ? 'on' : 'off'
//        ,'off'
//        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/UMass.html')))?>'
//        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
//        ,typeof defaultOpacities['UMass'] != 'undefined' && defaultOpacities['UMass'] != '' ? defaultOpacities['UMass'] : 100
//        ,defaultStyles['UMass'].split('-')[7]
//        ,defaultImageTypes['UMass']
//        ,''
//        ,defaultStyles['UMass'].split('-')[0]
//        ,defaultStyles['UMass'].split('-')[1]
//        ,defaultStyles['UMass'].split('-')[3]
//        ,defaultStyles['UMass'].split('-')[2]
//        ,defaultStyles['UMass'].split('-')[4]
//        ,defaultStyles['UMass'].split('-')[5]
//        ,defaultStyles['UMass'].split('-')[6]
//        ,'0-6'
//        ,''
//        ,'legends/UMass.png'
//        ,''
//        ,'-72,40.5,-69,43.5'
//        ,'true'
//        ,''
//        ,'currentsVelocity'
//      ]
      ,[
         'model'
        ,'ROMS ESPRESSO'
        ,'ROMS ESPRESSO currents'
        ,'off'
        ,defaultLayers['ROMS ESPRESSO'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/ROMS ESPRESSO.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['ROMS ESPRESSO'] != 'undefined' && defaultOpacities['ROMS ESPRESSO'] != '' ? defaultOpacities['ROMS ESPRESSO'] : 100
        ,defaultStyles['ROMS ESPRESSO'].split('-')[7]
        ,defaultImageTypes['ROMS ESPRESSO']
        ,''
        ,defaultStyles['ROMS ESPRESSO'].split('-')[0]
        ,defaultStyles['ROMS ESPRESSO'].split('-')[1]
        ,defaultStyles['ROMS ESPRESSO'].split('-')[3]
        ,defaultStyles['ROMS ESPRESSO'].split('-')[2]
        ,defaultStyles['ROMS ESPRESSO'].split('-')[4]
        ,defaultStyles['ROMS ESPRESSO'].split('-')[5]
        ,defaultStyles['ROMS ESPRESSO'].split('-')[6]
        ,'0-6'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=ESPRESSO_CURRENTS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['ROMS ESPRESSO'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=ESPRESSO_CURRENTS'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'currentsVelocity'
      ]
      ,[
         'model'
        ,'HOPS'
        ,'MARACOOS HOPS currents'
        ,'off'
        ,defaultLayers['HOPS'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/HOPS.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['HOPS'] != 'undefined' && defaultOpacities['HOPS'] != '' ? defaultOpacities['HOPS'] : 100
        ,defaultStyles['HOPS'].split('-')[7]
        ,defaultImageTypes['HOPS']
        ,''
        ,defaultStyles['HOPS'].split('-')[0]
        ,defaultStyles['HOPS'].split('-')[1]
        ,defaultStyles['HOPS'].split('-')[3]
        ,defaultStyles['HOPS'].split('-')[2]
        ,defaultStyles['HOPS'].split('-')[4]
        ,defaultStyles['HOPS'].split('-')[5]
        ,defaultStyles['HOPS'].split('-')[6]
        ,'0-6'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=PESHELF_CURRENTS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['HOPS'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=PESHELF_CURRENTS'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'currentsVelocity'
      ]
      ,[
         'model'
        ,'NCOM currents'
        ,'NCOM currents'
        ,'off'
        ,defaultLayers['NCOM currents'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/NCOM currents.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['NCOM currents'] != 'undefined' && defaultOpacities['NCOM currents'] != '' ? defaultOpacities['NCOM currents'] : 100
        ,defaultStyles['NCOM currents'].split('-')[7]
        ,defaultImageTypes['NCOM currents']
        ,''
        ,defaultStyles['NCOM currents'].split('-')[0]
        ,defaultStyles['NCOM currents'].split('-')[1]
        ,defaultStyles['NCOM currents'].split('-')[3]
        ,defaultStyles['NCOM currents'].split('-')[2]
        ,defaultStyles['NCOM currents'].split('-')[4]
        ,defaultStyles['NCOM currents'].split('-')[5]
        ,defaultStyles['NCOM currents'].split('-')[6]
        ,'0-6'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=NCOM_CURRENTS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['NCOM currents'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=NCOM_CURRENTS'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'currentsVelocity'
      ]
      ,[
         'model'
        ,'HYCOM currents'
        ,'HYCOM currents'
        ,'off'
        ,defaultLayers['HYCOM currents'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/HYCOM currents.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['HYCOM currents'] != 'undefined' && defaultOpacities['HYCOM currents'] != '' ? defaultOpacities['HYCOM currents'] : 100
        ,defaultStyles['HYCOM currents'].split('-')[7]
        ,defaultImageTypes['HYCOM currents']
        ,''
        ,defaultStyles['HYCOM currents'].split('-')[0]
        ,defaultStyles['HYCOM currents'].split('-')[1]
        ,defaultStyles['HYCOM currents'].split('-')[3]
        ,defaultStyles['HYCOM currents'].split('-')[2]
        ,defaultStyles['HYCOM currents'].split('-')[4]
        ,defaultStyles['HYCOM currents'].split('-')[5]
        ,defaultStyles['HYCOM currents'].split('-')[6]
        ,'0-6'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=HYCOM_CURRENTS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['HYCOM currents'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=HYCOM_CURRENTS'
        ,''
        ,'-180,-90,180,90' // '-78,35.5,-62,44'
        ,'true'
        ,''
        ,'currentsVelocity'
      ]
      ,[
         'model'
        ,'NAM winds'
        ,'NAM winds'
        ,'off'
        ,defaultLayers['NAM winds'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/NAM winds.html')))?>'
        ,'baseStyle,barbLabel,striding,min,max'
        ,typeof defaultOpacities['NAM winds'] != 'undefined' && defaultOpacities['NAM winds'] != '' ? defaultOpacities['NAM winds'] : 100
        ,defaultStyles['NAM winds'].split('-')[5]
        ,defaultImageTypes['NAM winds']
        ,''
        ,defaultStyles['NAM winds'].split('-')[0]
        ,''
        ,defaultStyles['NAM winds'].split('-')[2]
        ,defaultStyles['NAM winds'].split('-')[1]
        ,''
        ,defaultStyles['NAM winds'].split('-')[3]
        ,defaultStyles['NAM winds'].split('-')[4]
        ,'0-70'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=NAM_WINDS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['NAM winds'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=NAM_WINDS'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'windsVelocity'
      ]
      ,[
         'model'
        ,'WWIII waves'
        ,'WWIII waves'
        ,'off'
        ,defaultLayers['WWIII waves'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/WWIII waves.html')))?>'
        ,''
        ,typeof defaultOpacities['WWIII waves'] != 'undefined' && defaultOpacities['WWIII waves'] != '' ? defaultOpacities['WWIII waves'] : 100
        ,''
        ,defaultImageTypes['WWIII waves']
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=WW3_WAVE_HEIGHT&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['WWIII waves'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=WW3_WAVE_HEIGHT'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'wavesElevation'
      ]
      ,[
         'model'
        ,'NCOM SST'
        ,'NCOM water temperature'
        ,'off'
        ,defaultLayers['NCOM SST'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/NCOM SST.html')))?>'
        ,''
        ,typeof defaultOpacities['NCOM SST'] != 'undefined' && defaultOpacities['NCOM SST'] != '' ? defaultOpacities['NCOM SST'] : 100
        ,''
        ,defaultImageTypes['NCOM SST']
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=NCOM_SST&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['NCOM SST'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=NCOM_SST'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'temperature'
      ]
      ,[
         'observation'
        ,'HF radar currents'
        ,'HF radar currents'
        ,'off'
        ,defaultLayers['HF radar currents'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/HF radar currents.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['HF radar currents'] != 'undefined' && defaultOpacities['HF radar currents'] != '' ? defaultOpacities['HF radar currents'] : 100
        ,defaultStyles['HF radar currents'].split('-')[7]
        ,defaultImageTypes['HF radar currents']
        ,''
        ,defaultStyles['HF radar currents'].split('-')[0]
        ,defaultStyles['HF radar currents'].split('-')[1]
        ,defaultStyles['HF radar currents'].split('-')[3]
        ,defaultStyles['HF radar currents'].split('-')[2]
        ,defaultStyles['HF radar currents'].split('-')[4]
        ,defaultStyles['HF radar currents'].split('-')[5]
        ,defaultStyles['HF radar currents'].split('-')[6]
        ,'0-6'
        ,''
        ,'http://coastmap.com/ecop/wms.aspx?LAYERS=MARCOOSHFRADAR_CURRENTS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['HF radar currents'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=MARCOOSHFRADAR_CURRENTS'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
        ,'currentsVelocity'
      ]
      ,[
         'observation'
        ,'Chlorophyll concentration'
        ,'Chlorophyll concentration'
        ,'off'
        ,defaultLayers['Chlorophyll concentration'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Chlorophyll concentration.html')))?>'
        ,'palette'
        ,typeof defaultOpacities['Chlorophyll concentration'] != 'undefined' && defaultOpacities['Chlorophyll concentration'] != '' ? defaultOpacities['Chlorophyll concentration'] : 100
        ,''
        ,''
        ,defaultStyles['Chlorophyll concentration']
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'http://tds.maracoos.org/ncWMS/wms?REQUEST=GetLegendGraphic&LAYER=' + (typeof defaultLayerLayers['Chlorophyll concentration'] != 'undefined' && defaultLayerLayers['Chlorophyll concentration'] != '' ? defaultLayerLayers['Chlorophyll concentration'] : 'modis-seven/chl_oc3') + '&PALETTE=' + defaultStyles['Chlorophyll concentration'].split('/')[1] + '&TIME=' + dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + 'T' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,typeof defaultLayerLayers['Chlorophyll concentration'] != 'undefined' && defaultLayerLayers['Chlorophyll concentration'] != '' ? defaultLayerLayers['Chlorophyll concentration'] : 'modis-seven/chl_oc3'
        ,''
      ]
      ,[
         'observation'
        ,'Satellite water temperature'
        ,'Satellite water temperature'
        ,'off'
        ,defaultLayers['Satellite water temperature'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Satellite water temperature.html')))?>'
        ,'palette'
        ,typeof defaultOpacities['Satellite water temperature'] != 'undefined' && defaultOpacities['Satellite water temperature'] != '' ? defaultOpacities['Satellite water temperature'] : 100
        ,''
        ,''
        ,defaultStyles['Satellite water temperature']
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'http://tds.maracoos.org/ncWMS/wms?REQUEST=GetLegendGraphic&LAYER=' + (typeof defaultLayerLayers['Satellite water temperature'] != 'undefined' && defaultLayerLayers['Satellite water temperature'] != '' ? defaultLayerLayers['Satellite water temperature'] : 'sst-seven/mcsst') + '&PALETTE=' + defaultStyles['Satellite water temperature'].split('/')[1] + '&TIME=' + dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + 'T' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00' + '&GetMetadata&COLORSCALERANGE=' + getColorScaleRange()
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,typeof defaultLayerLayers['Satellite water temperature'] != 'undefined' && defaultLayerLayers['Satellite water temperature'] != '' ? defaultLayerLayers['Satellite water temperature'] : 'sst-seven/mcsst'
        ,'temperature'
      ]
      ,[
         'observation'
        ,'GOES visible imagery'
        ,'GOES visible imagery'
        ,'off'
        ,defaultLayers['GOES visible imagery'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/GOES visible imagery.html')))?>'
        ,''
        ,typeof defaultOpacities['GOES visible imagery'] != 'undefined' && defaultOpacities['GOES visible imagery'] != '' ? defaultOpacities['GOES visible imagery'] : 100
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'false'
        ,'-78,35.5,-62,44'
        ,'false'
        ,''
        ,''
      ]
      ,[
         'observation'
        ,'NHC storm tracks'
        ,'NHC storm tracks'
        ,'off'
        ,defaultLayers['NHC storm tracks'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/NHC storm tracks.html')))?>'
        ,''
        ,typeof defaultOpacities['NHC storm tracks'] != 'undefined' && defaultOpacities['NHC storm tracks'] != '' ? defaultOpacities['NHC storm tracks'] : 100
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'false'
        ,'-78,35.5,-62,44'
        ,'false'
        ,''
        ,''
      ]
      ,[
         'n/a'
        ,'Bathymetry contours'
        ,'Bathymetry contours'
        ,'off'
        ,defaultLayers['Bathymetry contours'] ? 'on' : 'off'
        ,'off'
        ,''
        ,''
        ,typeof defaultOpacities['Bathymetry contours'] != 'undefined' && defaultOpacities['Bathymetry contours'] != '' ? defaultOpacities['Bathymetry contours'] : 100
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,''
        ,'false'
        ,'-135,0,-50,50'
        ,'false'
        ,''
        ,''
      ]
    ]
<?php
  }
?>
  });

  if (config == 'ecop') {
    mainStore.removeAll();
    for (var layerType in ecop.availableLayers) {
      for (var i = 0; i < ecop.availableLayers[layerType].length; i++) {
        if (layerType == 'currents') {
          if (typeof defaultStyles[ecop.availableLayers[layerType][i].title] != 'string') {
            defaultStyles[ecop.availableLayers[layerType][i].title]          = 'CURRENTS_RAMP-Jet-False-1-True-0-2-Low';
            guaranteeDefaultStyles[ecop.availableLayers[layerType][i].title] = 'CURRENTS_RAMP-Jet-False-1-True-0-2-Low';
          }
          mainStore.add(new mainStore.recordType({
             'type'                 : 'currents'
            ,'name'                 : ecop.availableLayers[layerType][i].title
            ,'displayName'          : ecop.availableLayers[layerType][i].title
            ,'info'                 : 'off'
            ,'status'               : defaultLayers[ecop.availableLayers[layerType][i].title] ? 'on' : 'off'
            ,'settings'             : 'off'
            ,'infoBlurb'            : ecop.availableLayers[layerType][i].abstract
            ,'settingsParam'        : 'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
            ,'settingsOpacity'      : 100
            ,'settingsImageQuality' : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[7]
            ,'settingsImageType'    : 'png'
            ,'settingsPalette'      : ''
            ,'settingsBaseStyle'    : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[0]
            ,'settingsColorMap'     : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[1]
            ,'settingsStriding'     : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[2]
            ,'settingsBarbLabel'    : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[3]
            ,'settingsTailMag'      : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[4]
            ,'settingsMin'          : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[5]
            ,'settingsMax'          : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[6]
            ,'settingsMinMaxBounds' : '0-6'
            ,'rank'                 : ''
            ,'legend'               : 'http://coastmap.com/ecop/wms.aspx?LAYER=' + ecop.availableLayers[layerType][i].name + '&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=' + defaultStyles[ecop.availableLayers[layerType][i].title] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG:3857&LAYERS=' + ecop.availableLayers[layerType][i].name
            ,'timestamp'            : ''
            ,'bbox'                 : ecop.availableLayers[layerType][i].bbox
            ,'queryable'            : 'true'
            ,'settingsLayers'       : ''
            ,'category'             : 'currentsVelocity'
          }));
        }
        else if (layerType == 'winds') {
          if (typeof defaultStyles[ecop.availableLayers[layerType][i].title] != 'string') {
            defaultStyles[ecop.availableLayers[layerType][i].title]          = 'WINDS_VERY_SPARSE_GRADIENT-False-1-0-45-Low';
            guaranteeDefaultStyles[ecop.availableLayers[layerType][i].title] = 'WINDS_VERY_SPARSE_GRADIENT-False-1-0-45-Low';
          }
          mainStore.add(new mainStore.recordType({
             'type'                 : 'winds'
            ,'name'                 : ecop.availableLayers[layerType][i].title
            ,'displayName'          : ecop.availableLayers[layerType][i].title
            ,'info'                 : 'off'
            ,'status'               : defaultLayers[ecop.availableLayers[layerType][i].title] ? 'on' : 'off'
            ,'settings'             : 'off'
            ,'infoBlurb'            : ecop.availableLayers[layerType][i].abstract
            ,'settingsParam'        : 'baseStyle,barbLabel,striding,min,max'
            ,'settingsOpacity'      : 100
            ,'settingsImageQuality' : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[5]
            ,'settingsImageType'    : 'png'
            ,'settingsPalette'      : ''
            ,'settingsBaseStyle'    : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[0]
            ,'settingsColorMap'     : ''
            ,'settingsStriding'     : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[2]
            ,'settingsBarbLabel'    : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[1]
            ,'settingsTailMag'      : ''
            ,'settingsMin'          : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[3]
            ,'settingsMax'          : defaultStyles[ecop.availableLayers[layerType][i].title].split('-')[4]
            ,'settingsMinMaxBounds' : '0-70'
            ,'rank'                 : ''
            ,'legend'               : 'http://coastmap.com/ecop/wms.aspx?LAYER=' + ecop.availableLayers[layerType][i].name + '&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=' + defaultStyles[ecop.availableLayers[layerType][i].title] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG:3857&LAYERS=' + ecop.availableLayers[layerType][i].name
            ,'timestamp'            : ''
            ,'bbox'                 : ecop.availableLayers[layerType][i].bbox
            ,'queryable'            : 'true'
            ,'settingsLayers'       : ''
            ,'category'             : 'windsVelocity'
          }));
        }
        else if (layerType == 'waves') {
          if (typeof defaultStyles[ecop.availableLayers[layerType][i].title] != 'string') {
            defaultStyles[ecop.availableLayers[layerType][i].title]          = '';
            guaranteeDefaultStyles[ecop.availableLayers[layerType][i].title] = '';
          }
          mainStore.add(new mainStore.recordType({
             'type'                 : 'waves'
            ,'name'                 : ecop.availableLayers[layerType][i].title
            ,'displayName'          : ecop.availableLayers[layerType][i].title
            ,'info'                 : 'off'
            ,'status'               : defaultLayers[ecop.availableLayers[layerType][i].title] ? 'on' : 'off'
            ,'settings'             : 'off'
            ,'infoBlurb'            : ecop.availableLayers[layerType][i].abstract
            ,'settingsParam'        : ''
            ,'settingsOpacity'      : 100
            ,'settingsImageQuality' : ''
            ,'settingsImageType'    : 'png'
            ,'settingsPalette'      : ''
            ,'settingsBaseStyle'    : ''
            ,'settingsColorMap'     : ''
            ,'settingsStriding'     : ''
            ,'settingsBarbLabel'    : ''
            ,'settingsTailMag'      : ''
            ,'settingsMin'          : ''
            ,'settingsMax'          : ''
            ,'settingsMinMaxBounds' : ''
            ,'rank'                 : ''
            ,'legend'               : 'http://coastmap.com/ecop/wms.aspx?LAYER=' + ecop.availableLayers[layerType][i].name + '&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=' + defaultStyles[ecop.availableLayers[layerType][i].title] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG:3857&LAYERS=' + ecop.availableLayers[layerType][i].name
            ,'timestamp'            : ''
            ,'bbox'                 : ecop.availableLayers[layerType][i].bbox
            ,'queryable'            : 'true'
            ,'settingsLayers'       : ''
            ,'category'             : 'wavesElevation'
          }));
        }
        else if (layerType == 'temperature') {
          if (typeof defaultStyles[ecop.availableLayers[layerType][i].title] != 'string') {
            defaultStyles[ecop.availableLayers[layerType][i].title]          = '';
            guaranteeDefaultStyles[ecop.availableLayers[layerType][i].title] = '';
          }
          mainStore.add(new mainStore.recordType({
             'type'                 : 'temperatures'
            ,'name'                 : ecop.availableLayers[layerType][i].title
            ,'displayName'          : ecop.availableLayers[layerType][i].title
            ,'info'                 : 'off'
            ,'status'               : defaultLayers[ecop.availableLayers[layerType][i].title] ? 'on' : 'off'
            ,'settings'             : 'off'
            ,'infoBlurb'            : ecop.availableLayers[layerType][i].abstract
            ,'settingsParam'        : ''
            ,'settingsOpacity'      : 100
            ,'settingsImageQuality' : ''
            ,'settingsImageType'    : 'png'
            ,'settingsPalette'      : ''
            ,'settingsBaseStyle'    : ''
            ,'settingsColorMap'     : ''
            ,'settingsStriding'     : ''
            ,'settingsBarbLabel'    : ''
            ,'settingsTailMag'      : ''
            ,'settingsMin'          : ''
            ,'settingsMax'          : ''
            ,'settingsMinMaxBounds' : ''
            ,'rank'                 : ''
            ,'legend'               : 'http://coastmap.com/ecop/wms.aspx?LAYER=' + ecop.availableLayers[layerType][i].name + '&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=' + defaultStyles[ecop.availableLayers[layerType][i].title] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG:3857&LAYERS=' + ecop.availableLayers[layerType][i].name
            ,'timestamp'            : ''
            ,'bbox'                 : ecop.availableLayers[layerType][i].bbox
            ,'queryable'            : 'true'
            ,'settingsLayers'       : ''
            ,'category'             : 'temperature'
          }));
        }
        else {
          if (typeof defaultStyles[ecop.availableLayers[layerType][i].title] != 'string') {
            defaultStyles[ecop.availableLayers[layerType][i].title]          = '';
            guaranteeDefaultStyles[ecop.availableLayers[layerType][i].title] = '';
          }
          mainStore.add(new mainStore.recordType({
             'type'                 : 'other'
            ,'name'                 : ecop.availableLayers[layerType][i].title
            ,'displayName'          : ecop.availableLayers[layerType][i].title
            ,'info'                 : 'off'
            ,'status'               : 'off'
            ,'settings'             : 'off'
            ,'infoBlurb'            : ecop.availableLayers[layerType][i].abstract
            ,'settingsParam'        : ''
            ,'settingsOpacity'      : 100
            ,'settingsImageType'    : 'png'
            ,'settingsPalette'      : ''
            ,'settingsBaseStyle'    : ''
            ,'settingsColorMap'     : ''
            ,'settingsStriding'     : ''
            ,'settingsBarbLabel'    : ''
            ,'settingsTailMag'      : ''
            ,'settingsMin'          : ''
            ,'settingsMax'          : ''
            ,'settingsMinMaxBounds' : ''
            ,'rank'                 : ''
            ,'legend'               : 'http://coastmap.com/ecop/wms.aspx?LAYER=' + ecop.availableLayers[layerType][i].name + '&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=' + defaultStyles[ecop.availableLayers[layerType][i].title] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG:3857&LAYERS=' + ecop.availableLayers[layerType][i].name
            ,'timestamp'            : ''
            ,'bbox'                 : ecop.availableLayers[layerType][i].bbox
            ,'queryable'            : 'false'
            ,'settingsLayers'       : ''
            ,'category'             : ''
          }));
        }
      }
    }
  }
  else if (config == 'gliders') {
    mainStore.add(new mainStore.recordType({
       'type'                 : 'model'
      ,'name'                 : 'GFS winds'
      ,'displayName'          : 'GFS winds'
      ,'info'                 : 'off'
      ,'status'               : defaultLayers['GFS winds'] ? 'on' : 'off'
      ,'settings'             : 'off'
      ,'infoBlurb'            : 'No information currently available.'
      ,'settingsParam'        : 'baseStyle,barbLabel,striding,min,max'
      ,'settingsOpacity'      : typeof defaultOpacities['GFS winds'] != 'undefined' && defaultOpacities['GFS winds'] != '' ? defaultOpacities['GFS winds'] : 100
      ,'settingsImageQuality' : defaultStyles['GFS winds'].split('-')[5]
      ,'settingsImageType'    : defaultImageTypes['GFS winds']
      ,'settingsPalette'      : ''
      ,'settingsBaseStyle'    : defaultStyles['GFS winds'].split('-')[0]
      ,'settingsColorMap'     : ''
      ,'settingsStriding'     : defaultStyles['GFS winds'].split('-')[2]
      ,'settingsBarbLabel'    : defaultStyles['GFS winds'].split('-')[1]
      ,'settingsTailMag'      : ''
      ,'settingsMin'          : defaultStyles['GFS winds'].split('-')[3]
      ,'settingsMax'          : defaultStyles['GFS winds'].split('-')[4]
      ,'settingsMinMaxBounds' : '0-70'
      ,'rank'                 : ''
      ,'legend'               : 'http://coastmap.com/ecop/wms.aspx?LAYERS=NAM_WINDS&FORMAT=image%2Fpng&TRANSPARENT=TRUE&STYLES=' + defaultStyles['GFS winds'] + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&TIME=&SRS=EPSG%3A3857&LAYER=NAM_WINDS'
      ,'timestamp'            : ''
      ,'bbox'                 : '-78,35.5,-62,44'
      ,'queryable'            : 'true'
      ,'settingsLayers'       : ''
      ,'category'             : 'windsVelocity'
    }));
  }

  mainStore.each(function(rec) {
    if (restrictLayers && !restrictLayers[rec.get('name')]) {
      mainStore.remove(rec);
    }
  });

  var i = 0;
  mainStore.each(function(rec) {
    rec.set('rank',i++);
    rec.commit();
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'asset') {
      assetsStore.add(rec);
    }
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'currents') {
      currentsStore.add(rec);
    }
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'winds') {
      windsStore.add(rec);
    }
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'waves') {
      wavesStore.add(rec);
    }
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'temperatures') {
      temperaturesStore.add(rec);
    }
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'other') {
      otherStore.add(rec);
    }
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'model') {
      modelsStore.add(rec);
    }
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'observation') {
      observationsStore.add(rec);
    }
  });

  mainStore.each(function(rec) {
    if (rec.get('type') == 'gliders') {
      glidersStore.add(rec);
    }
  });

  legendsStore = new Ext.data.ArrayStore({
    fields : [
       'name'
      ,'displayName'
      ,'status'
      ,'rank'
      ,'fetchTime'
      ,'type'
    ]
    ,listeners : {update : function() {
      this.sort('rank','ASC');
    }}
  });

  palettesStore['Satellite water temperature'] = new Ext.data.ArrayStore({
    fields : [
      'name'
     ,'icon'
    ]
    ,data : [
       ['boxfill/redblue','redblue']
      ,['boxfill/alg','alg']
      ,['boxfill/ncview','ncview']
      ,['boxfill/alg2','alg2']
      ,['boxfill/greyscale','greyscale']
      ,['boxfill/occam','occam']
      ,['boxfill/rainbow','rainbow']
      ,['boxfill/sst_36','sst_36']
      ,['boxfill/occam_pastel-30','occam_pastel-30']
      ,['boxfill/ferret','ferret']
    ]
  });
  layersStore['Satellite water temperature'] = new Ext.data.ArrayStore({
    fields : [
      'name'
     ,'wmsName'
     ,'queryName'
    ]
    ,data : [
       ['7-day composite','sst-seven/mcsst','sst-masked/mcsst']
      ,['3-day composite','sst-three/mcsst','sst-masked/mcsst']
      ,['1-day composite','sst-one/mcsst','sst-masked/mcsst']
      ,['Single pass declouded','sst-masked/mcsst','sst-masked/mcsst']
      ,['Single pass','sst/mcsst','sst/mcsst']
    ]
  });

  palettesStore['Chlorophyll concentration'] = new Ext.data.ArrayStore({
    fields : [
      'name'
     ,'icon'
    ]
    ,data : [
       ['boxfill/redblue','redblue']
      ,['boxfill/alg','alg']
      ,['boxfill/ncview','ncview']
      ,['boxfill/alg2','alg2']
      ,['boxfill/greyscale','greyscale']
      ,['boxfill/occam','occam']
      ,['boxfill/rainbow','rainbow']
      ,['boxfill/sst_36','sst_36']
      ,['boxfill/occam_pastel-30','occam_pastel-30']
      ,['boxfill/ferret','ferret']
    ]
  });
  layersStore['Chlorophyll concentration'] = new Ext.data.ArrayStore({
    fields : [
      'name'
     ,'wmsName'
     ,'queryName'
    ]
    ,data : [
       ['7-day composite','modis-seven/chl_oc3','modis/chl_oc3']
      ,['3-day composite','modis-three/chl_oc3','modis/chl_oc3']
      ,['1-day composite','modis-one/chl_oc3','modis/chl_oc3']
      ,['Single pass','modis/chl_oc3','modis/chl_oc3']
    ]
  });

  baseStylesStore = new Ext.data.ArrayStore({
    fields : [
      'name'
     ,'value'
     ,'type'
    ]
    ,data : [
       ['Ramp','CURRENTS_RAMP','CURRENTS']
      ,['Black','CURRENTS_STATIC_BLACK','CURRENTS']
      ,['Green','WINDS_VERY_SPARSE_GREEN','WINDS']
      ,['Purple','WINDS_VERY_SPARSE_PURPLE','WINDS']
      ,['Yellow','WINDS_VERY_SPARSE_YELLOW','WINDS']
      ,['Orange','WINDS_VERY_SPARSE_ORANGE','WINDS']
      ,['Gradient','WINDS_VERY_SPARSE_GRADIENT','WINDS']
    ]
  });

  colorMapStore = new Ext.data.ArrayStore({
    fields : [
      'name'
    ]
    ,data : [
       ['Jet']
      ,['NoGradient']
      ,['Gray']
      ,['Blue']
      ,['Cool']
      ,['Hot']
      ,['Summer']
      ,['Winter']
      ,['Spring']
      ,['Autumn']
    ]
  });

  stridingStore = new Ext.data.ArrayStore({
    fields : [
      'index','param'
    ]
    ,data : [
       [0,0.25]
      ,[1,0.33]
      ,[2,0.50]
      ,[3,1.00]
      ,[4,2.00]
      ,[5,3.00]
      ,[6,4.00]
    ]
  });

  barbLabelStore = new Ext.data.ArrayStore({
    fields : [
      'name'
    ]
    ,data : [
       ['True']
      ,['False']
    ]
  });

  tailMagStore = new Ext.data.ArrayStore({
    fields : [
      'name'
    ]
    ,data : [
       ['True']
      ,['False']
    ]
  });

  imageQualityStore = new Ext.data.ArrayStore({
    fields : [
      'name'
     ,'value'
    ]
    ,data : [
       ['low','Low']
      ,['high','High']
    ]
  });

  var assetsSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var assetsGridPanel = new Ext.grid.GridPanel({
     id               : 'assetsGridPanel'
    ,hidden           : hideAssetsGridPanel
    ,height           : assetsStore.getCount() * 21.2 + 26 + 11 + 25
    ,title            : 'Point Observations'
    ,collapsible      : true
    ,store            : assetsStore
    ,border           : false
    ,selModel         : assetsSelModel
    ,columns          : [
       assetsSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 25}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderLayerCalloutButton    ,width : 20}
      // ,{id : 'settings'   ,dataIndex : 'settings'   ,renderer : renderSettingsButton,width : 25,align : 'right'}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      assetsSelModel.suspendEvents();
      var i = 0;
      assetsStore.each(function(rec) {
        if (rec.get('status') == 'on') {
          assetsSelModel.selectRow(i,true);
        }
        i++;
      });
      assetsSelModel.resumeEvents();
    }}
    ,tbar             : [
      {
         text    : 'Turn all assets off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          assetsSelModel.clearSelections(); 
        }
      }
      ,'->'
      ,{
         text    : 'Turn all assets on'
        ,icon    : 'img/add.png'
        ,handler : function() {
          assetsSelModel.selectAll();
        }
      }
    ]
  });

  var currentsSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var currentsGridPanel = new Ext.grid.GridPanel({
     id               : 'currentsGridPanel'
    ,hidden           : hideCurrentsGridPanel
    ,height           : Math.min(currentsStore.getCount(),5) * 21.1 + 26 + 11 + 25
    ,title            : 'Currents'
    ,collapsible      : true
    ,store            : currentsStore
    ,border           : false
    ,selModel         : currentsSelModel
    ,columns          : [
       currentsSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 25}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderLayerCalloutButton    ,width : 20}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      layersToSyncBbox['currents'] = true;
      needToInitGridPanel['currents'] = true;
      syncLayersToBbox('currents');
    }}
    ,tbar             : [
      {
         text    : 'Turn all currents off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          currentsSelModel.clearSelections();
        }
      }
    ]
  });

  var windsSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var windsGridPanel = new Ext.grid.GridPanel({
     id               : 'windsGridPanel'
    ,hidden           : hideWindsGridPanel
    ,height           : Math.min(windsStore.getCount(),5) * 21.1 + 26 + 11 + 25
    ,title            : 'Winds'
    ,collapsible      : true
    ,store            : windsStore
    ,border           : false
    ,selModel         : windsSelModel
    ,columns          : [
       windsSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 25}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderLayerCalloutButton    ,width : 20}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      layersToSyncBbox['winds'] = true;
      needToInitGridPanel['winds'] = true;
      syncLayersToBbox('winds');
    }}
    ,tbar             : [
      {
         text    : 'Turn all winds off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          windsSelModel.clearSelections();
        }
      }
    ]
  });

  var wavesSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var wavesGridPanel = new Ext.grid.GridPanel({
     id               : 'wavesGridPanel'
    ,hidden           : hideWavesGridPanel
    ,height           : Math.min(wavesStore.getCount(),5) * 21.1 + 26 + 11 + 25
    ,title            : 'Waves'
    ,collapsible      : true
    ,store            : wavesStore
    ,border           : false
    ,selModel         : wavesSelModel
    ,columns          : [
       wavesSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 25}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderLayerCalloutButton    ,width : 20}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      layersToSyncBbox['waves'] = true;
      needToInitGridPanel['waves'] = true;
      syncLayersToBbox('waves');
    }}
    ,tbar             : [
      {
         text    : 'Turn all waves off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          wavesSelModel.clearSelections();
        }
      }
    ]
  });

  var temperaturesSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var temperaturesGridPanel = new Ext.grid.GridPanel({
     id               : 'temperaturesGridPanel'
    ,hidden           : hideTemperaturesGridPanel
    ,height           : Math.min(temperaturesStore.getCount(),5) * 21.1 + 26 + 11 + 25
    ,title            : 'Water temperatures'
    ,collapsible      : true
    ,store            : temperaturesStore
    ,border           : false
    ,selModel         : temperaturesSelModel
    ,columns          : [
       temperaturesSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 25}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderLayerCalloutButton    ,width : 20}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      layersToSyncBbox['temperatures'] = true;
      needToInitGridPanel['temperatures'] = true;
      syncLayersToBbox('temperatures');
    }}
    ,tbar             : [
      {
         text    : 'Turn all temperatures off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          temperaturesSelModel.clearSelections();
        }
      }
    ]
  });

  var otherSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var otherGridPanel = new Ext.grid.GridPanel({
     id               : 'otherGridPanel'
    ,hidden           : hideOtherGridPanel
    ,height           : Math.min(otherStore.getCount(),5) * 21.1 + 26 + 11 + 25
    ,title            : 'Other'
    ,collapsible      : true
    ,store            : otherStore
    ,border           : false
    ,selModel         : otherSelModel
    ,columns          : [
       otherSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 25}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderLayerCalloutButton    ,width : 20}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      layersToSyncBbox['other'] = true;
      needToInitGridPanel['other'] = true;
      syncLayersToBbox('other');
    }}
    ,tbar             : [
      {
         text    : 'Turn all other off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          otherSelModel.clearSelections();
        }
      }
    ]
  });

  var modelsSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var modelsGridPanel = new Ext.grid.GridPanel({
     id               : 'modelsGridPanel'
    ,hidden           : hideModelsGridPanel
    ,height           : modelsStore.getCount() * 21.1 + 26 + 11 + 25
    ,title            : 'Models'
    ,collapsible      : true
    ,store            : modelsStore
    ,border           : false
    ,selModel         : modelsSelModel
    ,columns          : [
       modelsSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 25}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderLayerCalloutButton    ,width : 20}
      // ,{id : 'settings'   ,dataIndex : 'settings'   ,renderer : renderSettingsButton,width : 25,align : 'right'}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      modelsSelModel.suspendEvents();
      var i = 0;
      modelsStore.each(function(rec) {
        if (rec.get('status') == 'on') {
          modelsSelModel.selectRow(i,true);
        }
        i++;
      });
      modelsSelModel.resumeEvents();
    }}
    ,tbar             : [
      {
         text    : 'Turn all models off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          modelsSelModel.clearSelections();
        }
      }
    ]
  });

  var observationsSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var observationsGridPanel = new Ext.grid.GridPanel({
     id               : 'observationsGridPanel'
    ,hidden           : hideObservationsGridPanel
    ,height           : observationsStore.getCount() * 21.1 + 26 + 11 + 25
    ,title            : 'Spatial Observations'
    ,collapsible      : true
    ,store            : observationsStore
    ,border           : false
    ,selModel         : observationsSelModel
    ,columns          : [
       observationsSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 25}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderLayerCalloutButton    ,width : 20}
      // ,{id : 'settings'   ,dataIndex : 'settings'   ,renderer : renderSettingsButton,width : 25,align : 'right'}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      observationsSelModel.suspendEvents();
      var i = 0;
      observationsStore.each(function(rec) {
        if (rec.get('status') == 'on') {
          observationsSelModel.selectRow(i,true);
        }
        i++;
      });
      observationsSelModel.resumeEvents();
    }}
    ,tbar             : [
      {
         text    : 'Turn all observations off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          observationsSelModel.clearSelections();
        }
      }
    ]
  });

  var glidersSelModel = new Ext.grid.CheckboxSelectionModel({
     header    : ''
    ,checkOnly : true
    ,listeners : {
      rowdeselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(false);
      }
      ,rowselect : function(sm,idx,rec) {
        map.getLayersByName(rec.get('name'))[0].setVisibility(true);
      }
    }
  });
  var glidersGridPanel = new Ext.grid.GridPanel({
     id               : 'glidersGridPanel'
    ,hidden           : hideGlidersGridPanel
    ,height           : glidersStore.getCount() * 25.1 + 26 + 11 + 25
    ,title            : 'Gliders'
    ,collapsible      : true
    ,store            : glidersStore
    ,border           : false
    ,selModel         : glidersSelModel
    ,columns          : [
       glidersSelModel
      ,{id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 35}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {viewready : function(grid) {
      glidersSelModel.suspendEvents();
      var i = 0;
      glidersStore.each(function(rec) {
        if (rec.get('status') == 'on') {
          glidersSelModel.selectRow(i,true);
        }
        i++;
      });
      glidersSelModel.resumeEvents();
    }}
    ,tbar             : [
      {
         text    : 'Turn all gliders off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          glidersSelModel.clearSelections();
        }
      }
    ]
  });

  var glidersYearsFormPanel = new Ext.FormPanel({
     id              : 'glidersYearsFormPanel'
    ,hidden          : hideGlidersYearsFormPanel
    ,collapsible     : true
    ,title           : 'Filter by time'
    ,height          : 52
    ,layout          : 'fit'
    ,border          : false
    ,bodyStyle       : {paddingTop : '2px'}
    ,items           : new Ext.form.ComboBox({
      store : new Ext.data.ArrayStore({
         fields : ['year']
      })
      ,id             : 'glidersYearsComboBox'
      ,displayField   : 'year'
      ,valueField     : 'year'
      ,mode           : 'local'
      ,forceSelection : true
      ,triggerAction  : 'all'
      ,editable       : false
      ,listeners      : {
        select : function(combo,rec) {
          checkRealtimeAlert();
          syncGliders(true);
        }
      }
    })
  });

  var glidersProvidersSelModel = new Ext.grid.CheckboxSelectionModel({
    header : ''
  });
  var glidersProvidersGridPanel = new Ext.grid.GridPanel({
     id               : 'glidersProvidersGridPanel'
    ,hidden           : hideGlidersGridPanel
    ,collapsible      : true
    ,collapsed        : true
    ,title            : 'Filter by provider'
    ,store            : glidersMetadataStore
    ,height           : 200
    ,border           : false
    ,autoExpandColumn : 'description'
    ,columns          : [
       glidersProvidersSelModel
      ,{id : 'description',dataIndex : 'description',renderer : renderGlidersDescription}
    ]
    ,hideHeaders      : true
    ,loadMask         : true
    ,deferRowRender   : false
    ,selModel         : glidersProvidersSelModel
    ,listeners        : {
      rowclick : function(grid,rowIndex,e) {
        syncGliders(true);
      }
    }
    ,tbar             : [
      {
         text    : 'Hide all providers'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          Ext.getCmp('glidersProvidersGridPanel').getSelectionModel().clearSelections();
          Ext.getCmp('glidersProvidersGridPanel').fireEvent('rowclick',Ext.getCmp('glidersProvidersGridPanel'));
        }
      }
      ,'->'
      ,{
         text    : 'Show all providers'
        ,icon    : 'img/add.png'
        ,handler : function() {
          Ext.getCmp('glidersProvidersGridPanel').getSelectionModel().selectAll();
          Ext.getCmp('glidersProvidersGridPanel').fireEvent('rowclick',Ext.getCmp('glidersProvidersGridPanel'));
        }
      }
    ]
  });

  var legendsGridPanel = new Ext.grid.GridPanel({
     id               : 'legendsGridPanel'
    ,hidden           : hideLegendsGridPanel
    ,region           : 'east'
    ,width            : 180
    ,title            : 'Legends'
    ,collapsible      : true
    ,store            : legendsStore
    ,split            : true
    ,columns          : [
       {id : 'status',dataIndex : 'status',renderer : renderLayerStatus}
      ,{id : 'legend',dataIndex : 'name'  ,renderer : renderLegend}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,listeners        : {afterrender : function() {
      this.addListener('bodyresize',function(p,w,h) {
        this.getColumnModel().setConfig([
           {id : 'status',dataIndex : 'status',renderer : renderLayerStatus,width : (config == 'gliders' ? 42 : 30)}
          ,{id : 'legend',dataIndex : 'name'  ,renderer : renderLegend     ,width : w - 4 - 42}
        ]);
      });
    }}
    ,tbar : {items : [
<?php
  if ($_SESSION['config'] != 'ecop') {
?>
      {
         icon    : 'img/Places-bookmarks-icon.png'
        ,text    : 'Bookmark'
        ,tooltip : 'Bookmark active map'
        ,handler : function() {
          var p = {
             'center'  : map.getCenter().lon + ',' + map.getCenter().lat
            ,'zoom'    : map.getZoom()
            ,'base'    : ''
            ,'lyrs'    : []
            ,'styls'   : []
            ,'opcty'   : []
            ,'imgTyps' : []
            ,'esriO'   : esriOcean.visibility ? esriOcean.opacity * 100 : ''
            ,'lyrLyrs' : []
          };
          for (var i = 0; i < map.layers.length; i++) {
            if (map.layers[i].visibility) {
              if (map.layers[i].isBaseLayer) {
                p['base'] = map.layers[i].name;
                p['lyrs'].push(map.layers[i].name);
                p['styls'].push('');
                p['opcty'].push(map.layers[i].opacity * 100);
                p['imgTyps'].push('');
                p['lyrLyrs'].push('');
              }
              else if (mainStore.find('name',map.layers[i].name) >= 0) {
                p['lyrs'].push(map.layers[i].name);
                if (map.layers[i].DEFAULT_PARAMS) {
                  p['styls'].push(OpenLayers.Util.getParameters(map.layers[i].getFullRequestString({}))['STYLES']);
                  p['opcty'].push(map.layers[i].opacity * 100);
                  p['lyrLyrs'].push(OpenLayers.Util.getParameters(map.layers[i].getFullRequestString({}))['LAYERS']);
                  p['imgTyps'].push(OpenLayers.Util.getParameters(map.layers[i].getFullRequestString({}))['FORMAT'].split('/')[1]);
                }
                else if (map.layers[i].grid) {
                  p['styls'].push('');
                  p['opcty'].push(map.layers[i].opacity * 100);
                  p['lyrLyrs'].push('');
                  p['imgTyps'].push('');
                }
                else {
                  p['styls'].push('');
                  p['opcty'].push('');
                  p['lyrLyrs'].push('');
                  p['imgTyps'].push('');
                }
              }
            }
          }
          p['lyrs']   = p['lyrs'].join(',');
          p['config'] = config;
          var u = [];
          for (var i in p) {
            u.push(i + '=' + p[i]);
          }
          var url = "<?php echo 'http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/'))?>?" + u.join('&');
          Ext.Msg.alert('Bookmark','The following link will launch the ' + globalTitle + ' Explorer with your current confiuration and may be used as a bookmark. <a target=_blank href="' + url.replace(/ /g,'%20') + '">Link to my ' + globalTitle + ' Explorer</a>');
        }
      }
      ,'->'
      ,{
         icon    : 'img/help-icon.png'
        ,text    : 'Help'
        ,tooltip : 'View a tutorial and provide feedback.'
        ,menu    : {items : [
          {
             text    : 'View tutorial'
            ,icon    : 'img/help-icon.png'
            ,handler : function() {showHelp(true)}
            ,tooltip : 'View help tutorial'
          }
          ,{
             text    : 'Provide feedback'
            ,icon    : 'img/comments.png'
            ,tooltip : 'Provide feedback'
            ,handler : function() {
              if (fdbkUnavailable) {
                Ext.Msg.alert('Help',"We're sorry, but feedback is currently unavailable.");
                return;
              }
              Ext.Msg.alert('Feedback','We are very interested in your feedback.  Please send us an email at this address, <a href="mailto:maracoosinfo@udel.edu">maracoosinfo@udel.edu</a>.');
            }
          }
        ]}
      }
<?php
  }
  else {
?>
       '->'
      ,{
         icon    : 'img/door_out.png'
        ,text    : 'Logout'
        ,tooltip : 'Logout of this map session'
        ,handler : function() {
          document.location = 'logout.php';
        }
      }
<?php
  }
?>
    ]}
  });

  var managerItems = [
     introPanel
    ,assetsGridPanel
    ,glidersGridPanel
    ,glidersYearsFormPanel
    ,glidersProvidersGridPanel
    ,currentsGridPanel
    ,windsGridPanel
    ,wavesGridPanel
    ,temperaturesGridPanel
    ,otherGridPanel
    ,modelsGridPanel
    ,observationsGridPanel
  ];

  new Ext.Viewport({
     layout : 'border'
    ,id     : 'viewport'
    ,items  : [
      new Ext.Panel({
         region      : 'west'
        ,id          : 'managerPanel'
        ,width       : 255
        ,title       : globalTitleOverride ? globalTitleOverride : globalTitle + ' Manager'
        ,collapsible : managerPanelCollapsible
        ,items       : managerItems
        ,listeners        : {afterrender : function() {
          if (config == 'ecop') {
            this.addListener('bodyresize',function(p,w,h) {
              if (currentsGridPanel.getStore().getCount() > 10) {
                currentsGridPanel.setHeight(h - introPanel.getHeight() - windsGridPanel.getHeight() - temperaturesGridPanel.getHeight() - wavesGridPanel.getHeight() - otherGridPanel.getHeight());
              }
              else {
                otherGridPanel.setHeight(h - introPanel.getHeight() - currentsGridPanel.getHeight() - windsGridPanel.getHeight() - temperaturesGridPanel.getHeight() - wavesGridPanel.getHeight());
              }
            });
          }
        }}
<?php
  if ($_SESSION['config'] == 'ecop') {
?>
        ,tbar      : [
           {
             icon : 'img/blank.png'
           }
          ,'->'
          ,'Only list & map layers in current extents?'
          ,' '
          ,new Ext.form.Checkbox({
             checked   : false
            ,id        : 'restrictLayersToBbox'
            ,listeners : {check : function() {
              syncLayersToBbox();
            }}
          })
        ]
<?php
  }
?>
      })
      ,new Ext.Panel({
         region    : 'center'
        ,title     : !hideMapTitle ? globalTitle + ' Explorer' : ''
        ,layout    : 'border'
        ,items     : [
          {
             html      : '<div id="map"></div>'
              + '<div id="notRealtimeAlert">The environmental overlays display near real time data only. By selecting a year other than the most recent available, you are encouraged to turn all environmental overlays off to avoid confusion.</div>'
            ,region    : 'center'
            ,border    : false
            ,bbar      : {hidden : hideTimeSlider,items : [
              {
                 xtype     : 'buttongroup'
                ,autoWidth : true
                ,columns   : 1
                ,title     : 'Map date & time'
                ,items     : [{
                   id    : 'mapTime'
                  ,text  : dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + ' ' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00 UTC'
                  ,width : 135
                }]
              }
              ,{
                 xtype     : 'buttongroup'
                ,autoWidth : true
                ,columns   : 5
                ,title     : 'Change map date & time'
                ,items     : [
                   {
                     text    : 'Date'
                    ,tooltip : 'Change the map\'s date and time'
                    ,icon    : 'img/calendar_view_day16.png'
                    ,menu    : new Ext.menu.Menu({showSeparator : false,items : [
                      new Ext.DatePicker({
                         value     : new Date(dNow.getTime() + dNow.getTimezoneOffset() * 60000)
                        ,id        : 'datePicker'
                        ,listeners : {
                          select : function(picker,d) {
                            d.setUTCHours(0);
                            d.setUTCMinutes(0);
                            d.setUTCSeconds(0);
                            d.setUTCMilliseconds(0);
                            dNow = d;
                            setMapTime();
                          }
                        }
                      })
                    ]})
                  }
                  ,{
                     text    : '-6h'
                    ,icon    : 'img/ButtonRewind.png'
                    ,handler : function() {dNow = new Date(dNow.getTime() - 6 * 3600000);setMapTime();}
                  }
                  ,{
                     text    : '-1h'
                    ,icon    : 'img/ButtonPlayBack.png'
                    ,handler : function() {dNow = new Date(dNow.getTime() - 1 * 3600000);setMapTime();}
                  }
                  ,{
                     text    : '+1h'
                    ,icon    : 'img/ButtonPlay.png'
                    ,handler : function() {dNow = new Date(dNow.getTime() + 1 * 3600000);setMapTime();}
                  }
                  ,{
                     text    : '+6h'
                    ,icon    : 'img/ButtonForward.png'
                    ,handler : function() {dNow = new Date(dNow.getTime() + 6 * 3600000);setMapTime();}
                  }
                ]
              }
              ,{
                 id    : 'currentTime'
                ,scale : 'large'
                ,width : 0 // 135
                ,hidden : true
              }
              ,'->'
              ,{
                 xtype     : 'buttongroup'
                ,autoWidth : true
                ,columns   : 2
                ,title     : 'Map options'
                ,items     : [
                  {text : 'Bathymetry',icon : 'img/map16.png',menu : {items : [
                    {
                       text         : 'Hide bathymetry contours'
                      ,checked      : typeof defaultLayers['Bathymetry contours'] == 'undefined'
                      ,hideOnClick  : false
                      ,group        : 'bathy'
                      ,handler      : function() {
                        var lyr = map.getLayersByName('Bathymetry contours')[0];
                        if (!lyr) {
                          Ext.Msg.alert('Bathymetry contours',"We're sorry, but this layer is not available.");
                        }
                        else {
                          lyr.setVisibility(false);
                        }
                      }
                    }
                    ,{
                       text         : 'Show bathymetry contours'
                      ,checked      : typeof defaultLayers['Bathymetry contours'] != 'undefined'
                      ,hideOnClick  : false
                      ,group        : 'bathy'
                      ,handler      : function() {
                        var lyr = map.getLayersByName('Bathymetry contours')[0];
                        if (!lyr) {
                          Ext.Msg.alert('Bathymetry contours',"We're sorry, but this layer is not available.");
                        }
                        else {
                          lyr.setVisibility(true);
                        }
                      }
                    }
                  ]}}
                  ,{text : 'Basemap',icon : 'img/world16.png',menu : {items : [
                    {
                       text         : 'Show ESRI Ocean basemap'
                      ,checked      : defaultBasemap == 'ESRI Ocean'
                      ,hideOnClick  : false
                      ,group        : 'basemap'
                      ,handler      : function() {
                        var lyr = map.getLayersByName('ESRI Ocean')[0];
                        if (lyr.isBaseLayer) {
                          lyr.setOpacity(1);
                          map.setBaseLayer(lyr);
                          lyr.redraw();
                        }
                      }
                    }
                    ,{
                       text         : 'Show Google Hybrid basemap'
                      ,checked      : defaultBasemap == 'Google Hybrid'
                      ,hideOnClick  : false
                      ,group        : 'basemap'
                      ,handler      : function() {
                        var lyr = map.getLayersByName('Google Hybrid')[0];
                        if (lyr.isBaseLayer) {
                          lyr.setOpacity(1);
                          map.setBaseLayer(lyr);
                          lyr.redraw();
                        }
                      }
                    }
                    ,{
                       text         : 'Show Google Satellite basemap'
                      ,checked      : defaultBasemap == 'Google Satellite'
                      ,hideOnClick  : false
                      ,group        : 'basemap'
                      ,handler      : function() {
                        var lyr = map.getLayersByName('Google Satellite')[0];
                        if (lyr.isBaseLayer) {
                          lyr.setOpacity(1);
                          map.setBaseLayer(lyr);
                          lyr.redraw();
                        }
                      }
                    }
                    ,{
                       text         : 'Show Google Terrain basemap'
                      ,checked      : defaultBasemap == 'Google Terrain'
                      ,hideOnClick  : false
                      ,group        : 'basemap'
                      ,handler      : function() {
                        var lyr = map.getLayersByName('Google Terrain')[0];
                        if (lyr.isBaseLayer) {
                          lyr.setOpacity(1);
                          map.setBaseLayer(lyr);
                          lyr.redraw();
                        }
                      }
                    }
                  ]}}
                ]
              }
            ]}
            ,listeners : {
              afterrender : function(panel) {
                if (hideMapToolbar) {
                  panel.getTopToolbar().hide();
                }
                initMap();
              }
              ,bodyresize : function(p,w,h) {
                var el = document.getElementById('map');
                if (el) {
                  el.style.width = w;
                  el.style.height = h;
                  map.updateSize();
                }
              }
            }
          }
          ,new Ext.Panel({
             region      : 'south'
            ,hidden      : hideTimeseriesPanel
            ,id          : 'timeseriesPanel'
            ,title       : 'Time-Series Query Results'
            ,tbar        : [
              {
                 text : 'Active model query layer: '
                ,id   : 'activeLabel'
              }
              ,' '
              ,new Ext.form.ComboBox({
                 mode           : 'local'
                ,id             : 'chartLayerCombo'
                ,width          : 300
                ,store          : chartLayerStore
                ,displayField   : 'displayName'
                ,valueField     : 'name'
                ,forceSelection : true
                ,triggerAction  : 'all'
                ,editable       : false
              })
              ,'->'
              ,{
                 text    : 'Requery'
                ,icon    : 'img/arrow_refresh.png'
                ,id      : 'requery'
                ,hidden  : true
                ,handler : function() {
                  if (lyrQueryPts.features.length > 0) {
                    mapClick(lastMapClick['xy'],true,true);
                  }
                }
              }
              ,{
                 text    : 'Clear query'
                ,icon    : 'img/trash-icon.png'
                ,id      : 'graphAction'
                ,width   : 90
                ,handler : function() {
                  if (this.icon == 'img/blueSpinner.gif') {
                    return;
                  }
                  lyrQueryPts.removeFeatures(lyrQueryPts.features);
                  Ext.getCmp('requery').hide();
                  document.getElementById('tsResults').innerHTML = '<table class="obsPopup timeSeries"><tr><td><img width=3 height=3 src="img/blank.png"><br/><img width=8 height=1 src="img/blank.png">Click anywhere on the map or on a dot to view a time-series graph of model or observation output.<br/><img width=8 height=1 src="img/blank.png"><img src="info/graph_primer.png"></td></tr></table>';
                  chartData = [];
                  $('#tooltip').remove();
                  Ext.getCmp('chartLayerCombo').show();
                  Ext.getCmp('activeLabel').setText('Active model query layer: ');
                }
              }
            ]
            ,border      : false
            ,height      : 220
            ,collapsible : true
            ,split       : true
            ,items       : {border : false,html : '<div style="width:10;height:10" id="tsResults"/>'}
            ,listeners   : {
              afterrender : function(win) {
                var prevPt;
                $('#tsResults').bind('plothover',function(event,pos,item) {
                  if (item) {
                    var x = new Date(item.datapoint[0] + new Date().getTimezoneOffset() * 60 * 1000);
                    var y = item.datapoint[1];
                    var label = item.series.label ? item.series.label + ' : ' : 'Map Time : ';
                    if (prevPoint != item.dataIndex) {
                      $('#tooltip').remove();
                      showToolTip(item.pageX,item.pageY,x + '<br/>' + label + y);
                    }
                    prevPoint = item.dataIndex;
                  }
                  else {
                    $('#tooltip').remove();
                    prevPoint = null;
                  }
                });
                win.addListener('resize',function(win) {
                  var ts = document.getElementById('tsResults');
                  ts.style.width  = win.getWidth() - 15;
                  ts.style.height = win.getHeight() - 55;
                  var spd = [];
                  var dir = []; 
                  if (!chartData || chartData.length <= 0) {
                    ts.innerHTML = '<table class="obsPopup timeSeries"><tr><td><img width=3 height=3 src="img/blank.png"><br/><img width=8 height=1 src="img/blank.png">Click anywhere on the map or on a dot to view a time-series graph of model or observation output.<br/><img width=8 height=1 src="img/blank.png"><img src="info/graph_primer.png"></td></tr></table>';
                  }
                  else if (chartData && chartData.length > 0 && typeof chartData[0] == 'string' && chartData[0].indexOf('QUERY ERROR') == 0) {
                    ts.innerHTML = '<table class="obsPopup timeSeries"><tr><td><img width=3 height=3 src="img/blank.png"><br/><font color="red">' + chartData[0] + '</font><br/>' + '<img width=8 height=1 src="img/blank.png">Click anywhere on the map or on a dot to view a time-series graph of model or observation output.<br/><img width=8 height=1 src="img/blank.png"><img src="info/graph_primer.png"></td></tr></table>'; 
                  }
                  else {
                    for (var i = 0; i < chartData.length; i++) {
                      if (new RegExp(/Velocity|Speed/).test(chartData[i].label)) {
                        spd.push(chartData[i]);
                      }
                      else if (chartData[i].label.indexOf('Direction') >= 0) {
                        dir.push(chartData[i]);
                      }
                    }
                    ts.innerHTML    = '';
                    var p = $.plot(
                       $('#tsResults')
                      ,spd.length > 0 && dir.length > 0 ? spd : chartData
                      ,{
                         xaxis     : {mode  : "time"}
                        ,crosshair : {mode  : 'x'   }
                        ,grid      : {backgroundColor : {colors : ['#fff','#eee']},borderWidth : 1,borderColor : '#99BBE8',hoverable : true}
                        ,zoom      : {interactive : false}
                        ,pan       : {interactive : false}
                        ,legend    : {backgroundOpacity : 0.3}
                      }
                    );
                    if (spd.length > 0 && dir.length > 0 && spd.length == dir.length) {
                      // assume that #spd == #dir
                      for (var j = 0; j < spd.length; j++) {
                        var imageSize = 80;
                        for (var i = spd[j].data.length - 1; i >= 0; i--) {
                          var type = 'arrow';
                          if (spd[j].label.indexOf('Wind') >= 0) {
                            type = 'barb';
                          }
                          var o = p.pointOffset({x : spd[j].data[i][0],y : spd[j].data[i][1]});
                          if (type == 'barb') {
                            var val = Math.round(dir[j].data[i][1]);
                            if (spd[j].type == 'obs') {
                              val = (val + 180) % 360;
                            }
                            $('#tsResults').prepend('<div class="dir" style="position:absolute;left:' + (o.left-imageSize/2) + 'px;top:' + (o.top-(imageSize/2)) + 'px;background-image:url(\'vector.php?w=' + imageSize + '&h=' + imageSize + '&dir=' + val + '&spd=' + Math.round(spd[j].data[i][1]) + '&type=' + type + '&color=' + lineColor2VectorColor(dir[j].color).replace('#','') + '\');width:' + imageSize + 'px;height:' + imageSize + 'px;"></div>');
                          }
                          else {
                            // pull arrows from cache
                            $('#tsResults').prepend('<div class="dir" style="position:absolute;left:' + (o.left-imageSize/2) + 'px;top:' + (o.top-(imageSize/2)) + 'px;background-image:url(\'img/vectors/' + type + '/' + imageSize + 'x' + imageSize + '.dir' + Math.round(dir[j].data[i][1]) + '.' + lineColor2VectorColor(dir[j].color).replace('#','') + '.png\');width:' + imageSize + 'px;height:' + imageSize + 'px;"></div>');
                          }
                        }
                      }
                    }
                    if (chartData[0].nowIdx != '' && chartData[0].data[chartData[0].nowIdx]) {
                      var imageSize = 16;
                      var o = p.pointOffset({x : chartData[0].data[chartData[0].nowIdx][0],y : chartData[0].data[chartData[0].nowIdx][1]});
                      $('#tsResults').prepend('<div class="dir" style="position:absolute;left:' + (o.left-imageSize/2) + 'px;top:' + (o.top-(imageSize/2)) + 'px;background-image:url(\'img/asterisk_orange.png\');width:' + imageSize + 'px;height:' + imageSize + 'px;"></div>');
                    }
                  }
                  lyrQueryPts.features.length > 0 ? Ext.getCmp('requery').show() : Ext.getCmp('requery').hide();
                });
              }
            }
          })
        ]
      })
      ,legendsGridPanel
    ]
  });
  if (!cp.get('hideAssetsHelpOnStartup') && showHelpOnStartup) {
    showHelp(false);
  }
}

function initMap() {
  // set transformation functions from/to alias projection
  OpenLayers.Projection.addTransform("EPSG:4326","EPSG:3857",OpenLayers.Layer.SphericalMercator.projectForward);
  OpenLayers.Projection.addTransform("EPSG:3857","EPSG:4326",OpenLayers.Layer.SphericalMercator.projectInverse);

  OpenLayers.Util.onImageLoadError = function() {this.src = 'img/blank.png';}

  // patch openlayers 2.11RC to fix problem when switching to a google layer
  // from a non google layer after resizing the map
  // http://osgeo-org.1803224.n2.nabble.com/trunk-google-v3-problem-resizing-and-switching-layers-amp-fix-td6578816.html
  OpenLayers.Layer.Google.v3.onMapResize = function() {
    var cache = OpenLayers.Layer.Google.cache[this.map.id];
    cache.resized = true;
  };
  OpenLayers.Layer.Google.v3.setGMapVisibility_old =
  OpenLayers.Layer.Google.v3.setGMapVisibility;
  OpenLayers.Layer.Google.v3.setGMapVisibility = function(visible) {
    var cache = OpenLayers.Layer.Google.cache[this.map.id];
    if (visible && cache && cache.resized) {
      google.maps.event.trigger(this.mapObject, "resize");
      delete cache.resized;
    }
    OpenLayers.Layer.Google.v3.setGMapVisibility_old.apply(this,arguments);
  };

  lyrQueryPts = new OpenLayers.Layer.Vector(
     'Query points'
    ,{styleMap : new OpenLayers.StyleMap({
      'default' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
         externalGraphic : 'img/${img}'
        ,pointRadius     : 10
        ,graphicOpacity  : 1
        ,graphicWidth    : 16
        ,graphicHeight   : 16
      }))
    })}
  );

  esriOcean = new OpenLayers.Layer.XYZ(
     'ESRI Ocean'
    ,'http://services.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/${z}/${y}/${x}.jpg'
    ,{sphericalMercator: true,visibility : defaultBasemap == 'ESRI Ocean',isBaseLayer : true,opacity : defaultOpacities['ESRI Ocean'] / 100,wrapDateLine : true,attribution : "GEBCO, NOAA, National Geographic, AND data by <a href='http://www.arcgis.com/home/item.html?id=6348e67824504fc9a62976434bf0d8d5'>ESRI</a>"} // ,serverResolutions : basemapResolutions,resolutions : basemapResolutions.slice(1)}
  );

  map = new OpenLayers.Map('map',{
    layers            : [
       esriOcean
      ,new OpenLayers.Layer.Google('Google Hybrid',{
         type          : google.maps.MapTypeId.HYBRID
        ,projection    : proj900913
        ,opacity       : defaultOpacities['Google Hybrid'] / 100
        ,visibility    : defaultBasemap == 'Google Hybrid'
        ,minZoomLevel  : 2
      })
      ,new OpenLayers.Layer.Google('Google Satellite',{
         type          : google.maps.MapTypeId.SATELLITE
        ,projection    : proj900913
        ,opacity       : defaultOpacities['Google Satellite'] / 100
        ,visibility    : defaultBasemap == 'Google Satellite'
        ,minZoomLevel  : 2
      })
      ,new OpenLayers.Layer.Google('Google Terrain',{
         type          : google.maps.MapTypeId.TERRAIN
        ,projection    : proj900913
        ,opacity       : defaultOpacities['Google Terrain'] / 100
        ,visibility    : defaultBasemap == 'Google Terrain'
        ,minZoomLevel  : 2
      })
      ,lyrQueryPts
    ]
    ,projection        : proj900913
    ,displayProjection : proj4326
    ,units             : "m"
    ,maxExtent         : new OpenLayers.Bounds(-20037508,-20037508,20037508,20037508.34)
  });

  for (var i = 0; i < map.layers.length; i++) {
    var lyr = map.getLayersByName(defaultBasemap)[0];
    if (!lyr.visibility) {
      map.setBaseLayer(lyr);
    }
  }

  map.events.register('click',this,function(e) {
    mapClick(e.xy,true,true);
  });

  map.events.register('addlayer',this,function() {
    map.setLayerIndex(lyrQueryPts,map.layers.length - 1);
  });

  map.setCenter(new OpenLayers.LonLat(defaultCenter[0],defaultCenter[1]),defaultZoom);
<?php
  if (($_SESSION['config'] == 'ecop') && isset($_COOKIE['bounds'])) {
?>
  map.zoomToExtent(new OpenLayers.Bounds(<?php echo $_COOKIE['bounds']?>).transform(proj4326,proj900913));
<?php
  }
?>

  var navControl = new OpenLayers.Control.NavToolbar();
  map.addControl(navControl);
  // only need 1 zoom wheel responder!
  navControl.controls[0].disableZoomWheel();

  var mouseControl = new OpenLayers.Control.MousePosition({
    formatOutput: function(lonLat) {
      return convertDMS(lonLat.lat.toFixed(5), "LAT") + ' ' + convertDMS(lonLat.lon.toFixed(5), "LON");
    }
  });
  mouseControl.displayProjection = new OpenLayers.Projection('EPSG:4326');
  if (!hideMouseControl) {
    map.addControl(mouseControl);
  }

  map.events.register('zoomend',this,function() {
    if (popupObs && !popupObs.isDestroyed) {
      popupObs.hide();
    }
  });
  map.events.register('moveend',this,function() {
    if (config == 'ecop') {
      syncLayersToBbox();
    }
    if (navControl.controls[1].active) {
      navControl.controls[1].deactivate();
      navControl.draw();
    }
    syncObs({name : 'NDBC'});
    syncObs({name : 'CO-OPS'});
    syncObs({name : 'USGS'});
    syncObs({name : 'Ship'});
    syncObs({name : 'NERRS'});
    syncObs({name : 'MDDNR'});
//    syncObs({name : 'Weatherflow'});
    syncObs({name : 'HRECOS'});
    syncObs({name : 'HF Radar'});
    syncObs({name : 'Satellites'});
    syncObs({name : 'Gliders'});
    syncObs({name : 'Drifters'});
    if (popupObs && !popupObs.isDestroyed) {
      popupObs.show();
    }
  });
  map.events.register('changelayer',this,function(e) {
    if (e.property == 'params') {
      // keep legend in sync if a GetLegendGraphic legend
      var idx = mainStore.find('name',e.layer.name);
      if (idx >= 0 && mainStore.getAt(idx).get('legend').indexOf('GetLegendGraphic') >= 0) {
        var params = {
           REQUEST : 'GetLegendGraphic'
          ,LAYER   : OpenLayers.Util.getParameters(e.layer.getFullRequestString({}))['LAYERS']
        };
        if (mainStore.getAt(idx).get('legend').indexOf('GetMetadata') >= 0) {
          params.GetMetadata     = '';
          params.COLORSCALERANGE = getColorScaleRange();
        }
        mainStore.getAt(idx).set('legend',e.layer.getFullRequestString(params));
        mainStore.getAt(idx).commit();
      }
    }
  });

  if (config != 'ecop') {
    addWMS({
       name   : 'NCOM SST'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'NCOM_SST'
      ,format : 'image/' + defaultImageTypes['NCOM SST']
      ,styles : ''
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'Satellite water temperature'
      ,url    : 'http://tds.maracoos.org/ncWMS/wms?GFI_TIME=min/max'
      ,layers : defaultLayerLayers['Satellite water temperature']
      ,format : 'image/png'
      ,styles : defaultStyles['Satellite water temperature']
      ,singleTile : false
      ,projection : proj3857
    });
    addWMS({
       name   : 'WWIII waves'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'WW3_WAVE_HEIGHT'
      ,format : 'image/' + defaultImageTypes['WWIII waves']
      ,styles : ''
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'GOES visible imagery'
      ,url    : 'http://mesonet.agron.iastate.edu/cgi-bin/wms/goes/conus_vis.cgi?'
      ,layers : 'conus_vis_1km_900913'
      ,format : 'image/' + defaultImageTypes['GOES visible imagery']
      ,styles : ''
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'Chlorophyll concentration'
      ,url    : 'http://tds.maracoos.org/ncWMS/wms?GFI_TIME=min/max'
      ,layers : defaultLayerLayers['Chlorophyll concentration']
      ,format : 'image/png'
      ,styles : defaultStyles['Chlorophyll concentration']
      ,singleTile : false
      ,projection : proj3857
    });

    addWMS({
       name   : 'ROMS'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'NOSCBOFSCUR_CURRENTS'
      ,format : 'image/' + defaultImageTypes['ROMS']
      ,styles : defaultStyles['ROMS']
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'STPS'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'CODARSTPS_CURRENTS'
      ,format : 'image/' + defaultImageTypes['STPS']
      ,styles : defaultStyles['STPS']
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'Stevens NYHOPS'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'NYHOPSCUR_currents'
      ,format : 'image/' + defaultImageTypes['Stevens NYHOPS']
      ,styles : defaultStyles['Stevens NYHOPS']
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'ROMS ESPRESSO'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'ESPRESSO_CURRENTS'
      ,format : 'image/' + defaultImageTypes['ROMS ESPRESSO']
      ,styles : defaultStyles['ROMS ESPRESSO']
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'HOPS'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'PESHELF_CURRENTS'
      ,format : 'image/' + defaultImageTypes['HOPS']
      ,styles : defaultStyles['HOPS']
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'NCOM currents'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'NCOM_CURRENTS'
      ,format : 'image/' + defaultImageTypes['NCOM currents']
      ,styles : defaultStyles['NCOM currents']
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'HYCOM currents'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'HYCOM_CURRENTS'
      ,format : 'image/' + defaultImageTypes['HYCOM currents']
      ,styles : defaultStyles['HYCOM currents']
      ,singleTile : true
      ,projection : proj3857
    });
  //  addWMS({
  //     name   : 'UMass'
  //    ,url    : 'http://coastmap.com/ecop/wms.aspx?'
  //    ,layers : 'FVCOM_MASS_CURRENTS'
  //    ,format : 'image/' + defaultImageTypes['UMass']
  //    ,styles : defaultStyles['UMass']
  //    ,singleTile : true
  //    ,projection : proj3857
  //  });
    addWMS({
       name   : 'NAM winds'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'NAM_WINDS'
      ,format : 'image/' + defaultImageTypes['NAM winds']
      ,styles : defaultStyles['NAM winds']
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'HF radar currents'
      ,url    : 'http://coastmap.com/ecop/wms.aspx?'
      ,layers : 'MARCOOSHFRADAR_CURRENTS'
      ,format : 'image/' + defaultImageTypes['HF radar currents']
      ,styles : defaultStyles['HF radar currents']
      ,singleTile : true
      ,projection : proj3857
    });
    addWMS({
       name   : 'NHC storm tracks'
      ,url    : 'http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/wwa?BGCOLOR=0xCCCCFE&'
      ,layers : 'NHC_TRACK_POLY,NHC_TRACK_LIN,NHC_TRACK_PT,NHC_TRACK_PT_72DATE,NHC_TRACK_PT_120DATE,NHC_TRACK_PT_0NAMEDATE,NHC_TRACK_PT_MSLPLABELS,NHC_TRACK_PT_72WLBL,NHC_TRACK_PT_120WLBL,NHC_TRACK_PT_72CAT,NHC_TRACK_PT_120CAT'
      ,format : 'image/png'
      ,styles : ''
      ,singleTile : true
      ,projection : proj900913
    });

    addTileCache({
       name   : 'Bathymetry contours'
      ,url    : 'http://assets.maracoos.org/tilecache/'
      ,layer  : 'bathy'
      ,projection : proj900913
    });

    addObs({
       name       : 'NDBC'
      ,visibility : typeof defaultLayers['NDBC'] != 'undefined'
    });
    addObs({
       name       : 'CO-OPS'
      ,visibility : typeof defaultLayers['CO-OPS'] != 'undefined'
    });
    addObs({
       name       : 'USGS'
      ,visibility : typeof defaultLayers['USGS'] != 'undefined'
    });
    addObs({
       name       : 'Ship'
      ,visibility : typeof defaultLayers['Ship'] != 'undefined'
    });
    addObs({
       name       : 'MDDNR'
      ,visibility : typeof defaultLayers['MDDNR'] != 'undefined'
    });
    addObs({
       name       : 'NERRS'
      ,visibility : typeof defaultLayers['NERRS'] != 'undefined'
    });
  /*
    addObs({
       name       : 'Weatherflow'
      ,visibility : typeof defaultLayers['Weatherflow'] != 'undefined'
    });
  */
    addObs({
       name       : 'HRECOS'
      ,visibility : typeof defaultLayers['HRECOS'] != 'undefined'
    });
    addObs({
       name       : 'HF Radar'
      ,visibility : typeof defaultLayers['HF Radar'] != 'undefined'
    });
    addObs({
       name       : 'Satellites'
      ,visibility : typeof defaultLayers['Satellites'] != 'undefined'
    });
    addObs({
       name       : 'Gliders'
      ,visibility : typeof defaultLayers['Gliders'] != 'undefined'
    });
    addObs({
       name       : 'Drifters'
      ,visibility : typeof defaultLayers['Drifters'] != 'undefined'
    });
    addObs({
       name       : 'Sea gliders'
      ,visibility : typeof defaultLayers['Sea gliders'] != 'undefined'
    });
    addObs({
       name       : 'Slocum gliders'
      ,visibility : typeof defaultLayers['Slocum gliders'] != 'undefined'
    });
    addObs({
       name       : 'Spray gliders'
      ,visibility : typeof defaultLayers['Spray gliders'] != 'undefined'
    });
    addObs({
       name       : 'Unknown gliders'
      ,visibility : typeof defaultLayers['Unknown gliders'] != 'undefined'
    });
    if (config == 'gliders') {
      addWMS({
         name   : 'GFS winds'
        ,url    : 'http://coastmap.com/ecop/wms.aspx?'
        ,layers : 'GFS_WINDS'
        ,format : 'image/' + defaultImageTypes['GFS winds']
        ,styles : defaultStyles['GFS winds']
        ,singleTile : true
        ,projection : proj3857
      });
    }
  }
  else {
    for (var i = 0; i < ecop.layerStack.length; i++) {
      addWMS({
         name       : ecop.layerStack[i].title
        ,url        : 'http://coastmap.com/ecop/wms.aspx?'
        ,layers     : ecop.layerStack[i].name
        ,format     : 'image/png'
        ,styles     : defaultStyles[ecop.layerStack[i].title]
        ,singleTile : true
        ,projection : proj3857
      });
    }
  }

  if (config == 'gliders') {
    glidersMetadataStore.fireEvent('beforeload');
    OpenLayers.Request.issue({
       method  : 'POST'
      ,url     : 'proxy.php'
      ,headers : {'Content-Type' : 'application/x-www-form-urlencoded'}
      ,data    : OpenLayers.Util.getParameterString({
        u : 'http://marine.rutgers.edu/cool/auvs/track.php?service=info'
      })
      ,callback : function(r) {
        var json = new OpenLayers.Format.JSON().read(r.responseText);
        var menu = [];
        var data = [];
        for (var i in json.providers) {
          if (i != 'remove' && i != 'indexOf') {
            data.push([json.providers[i].name,json.providers[i].description]);
          }
        }
        data.push(['scripps','Scripps Institution of Oceanography']);
        data.push(['uw','University of Washington']);
        glidersMetadataStore.loadData(data);
        glidersMetadataStore.sort('description','ASC');
        Ext.getCmp('glidersProvidersGridPanel').getSelectionModel().selectAll();
        Ext.getCmp('glidersProvidersGridPanel').setHeight(Math.min(glidersMetadataStore.getCount(),8) * 21.1 + 26 + 11 + 25);
        var ymd = json.timespan.start.split(' ')[0].split('-');
        var sto = Ext.getCmp('glidersYearsComboBox').getStore();
        for (var i = json.timespan.end.split(' ')[0].split('-')[0]; i >= json.timespan.start.split(' ')[0].split('-')[0]; i--) {
          sto.add(new sto.recordType({year : i}));
        }
        sto.insert(0,(new sto.recordType({year : 'Currently deployed'})));
        if (sto.getCount() > 1) {
          Ext.getCmp('glidersYearsComboBox').setValue(sto.getAt(1).get('year'));
        }
        syncGliders(true);
      }
    });
  }

  refreshCurrentTime();
}

function showLayerInfo(layerName) {
  if (!activeInfoWindows[layerName]) {
    var idx = mainStore.find('name',layerName);
    var pos = getOffset(document.getElementById('info.' + layerName));
    activeInfoWindows[layerName] = new Ext.Window({
       width      : 400
      ,x          : pos.left
      ,y          : pos.top
      ,autoScroll : true
      ,constrainHeader : true
      ,title      : mainStore.getAt(idx).get('displayName').split('||')[0] + ' :: info'
      ,items      : {border : false,bodyCssClass : 'popup',html : mainStore.getAt(idx).get('infoBlurb')}
      ,listeners  : {hide : function() {
        activeInfoWindows[layerName] = null;
      }}
    }).show();
  }
}

function setLayerInfo(layerName,on) {
  var mainRec = mainStore.getAt(mainStore.find('name',layerName));
  mainRec.set('info',on ? 'on' : 'off');
  mainRec.commit();

  // only one popup can be displayed at a time
  mainStore.each(function(rec) {
    if (layerName != rec.get('name') && rec.get('info') == 'on') {
      rec.set('info','off');
      rec.commit();
      if (Ext.getCmp('info.popup.' + rec.get('name'))) {
        Ext.getCmp('info.popup.' + rec.get('name')).destroy();
      }
    }
    else if (layerName == rec.get('name') && rec.get('info') == 'off') {
      rec.set('info','off');
      rec.commit();
      var el = Ext.getCmp('info.popup.' + layerName);
      if (el) {
        el.hide();
        return;
      }
    }
  });

  if (on && (!Ext.getCmp('info.popup.' + layerName) || !Ext.getCmp('info.popup.' + layerName).isVisible())) {
    var customize = '<a class="blue-href-only" href="javascript:setLayerSettings(\'' + mainRec.get('name') + '\');setLayerInfo(\'' + layerName + '\',false)"><img width=32 height=32 src="img/settings_tools_big.png"><br>Customize<br>appearance</a>';
    if (new RegExp(/glider|asset/).test(mainRec.get('type'))) {
      customize = '<img width=32 height=32 src="img/settings_tools_big_disabled.png"><br><font color="lightgray">Customize<br>appearance</font>';
    }
    new Ext.ToolTip({
       id        : 'info.popup.' + layerName
      ,title     : mainRec.get('displayName').split('||')[0]
      ,anchor    : 'right'
      ,target    : 'info.' + layerName 
      ,autoHide  : false
      ,closable  : true
      ,width     : 250
      ,items     : {
         layout   : 'column'
        ,defaults : {border : false}
        ,height   : 75
        ,bodyStyle : 'padding:6'
        ,items    :  [
           {columnWidth : 0.33,items : {xtype : 'container',autoEl : {tag : 'center'},items : {border : false,html : '<a class="blue-href-only" href="javascript:zoomToBbox(\'' + mainRec.get('bbox') + '\');setLayerInfo(\'' + layerName + '\',false)"><img width=32 height=32 src="img/find_globe_big.png"><br>Zoom<br>to layer</a>'}}}
          ,{columnWidth : 0.33,items : {xtype : 'container',autoEl : {tag : 'center'},items : {border : false,html : customize}}}
          ,{columnWidth : 0.33,items : {xtype : 'container',autoEl : {tag : 'center'},items : {border : false,html : '<a class="blue-href-only" href="javascript:showLayerInfo(\'' + mainRec.get('name') + '\');setLayerInfo(\'' + layerName + '\',false)"><img width=32 height=32 src="img/document_image.png"><br>Layer<br>information</a>'}}}
        ]
      }
      ,listeners : {
        hide : function() {
          this.destroy();
          mainRec.set('info','off');
          mainRec.commit();
        }
      }
    }).show();
  }
}

function setLayerSettings(layerName) {
  if (!activeSettingsWindows[layerName]) {
    var pos = getOffset(document.getElementById('info.' + layerName));
    var idx = mainStore.find('name',layerName);
    var height = 26;
    var id = Ext.id();
    var items = [
      new Ext.Slider({
         fieldLabel : 'Opacity<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.opacity' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.opacity' + '" src="img/info.png"></a>'
        ,id       : 'opacity.' + id
        ,width    : 130
        ,minValue : 0
        ,maxValue : 100
        ,value    : mainStore.getAt(idx).get('settingsOpacity')
        ,plugins  : new Ext.slider.Tip({
          getText : function(thumb) {
            return String.format('<b>{0}%</b>', thumb.value);
          }
        })
        ,listeners : {
          afterrender : function() {
            new Ext.ToolTip({
               id     : 'tooltip.' + id + '.opacity'
              ,target : id + '.opacity'
        ,html   : "Use the slider to adjust the layer's opacity.  The lower the opacity, the greater the transparency."
      });
          }
          ,change : function(slider,val) {
            mainStore.getAt(idx).set('settingsOpacity',val);
            mainStore.getAt(idx).commit();
            map.getLayersByName(mainStore.getAt(idx).get('name'))[0].setOpacity(val / 100);
          }
        }
      })
    ];
    if (mainStore.getAt(idx).get('settingsImageQuality') != '') {
      height += 27;
      items.push(
        new Ext.form.ComboBox({
           fieldLabel     : 'Image quality<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.imageQuality' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.imageQuality' + '" src="img/info.png"></a>'
          ,id             : 'imageType.' + id
          ,store          : imageQualityStore
          ,displayField   : 'name'
          ,valueField     : 'value'
          ,value          : mainStore.getAt(idx).get('settingsImageQuality')
          ,editable       : false
          ,triggerAction  : 'all'
          ,mode           : 'local'
          ,width          : 130
          ,forceSelection : true
          ,listeners      : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.imageQuality'
                ,target : id + '.imageQuality'
                ,html   : "Selecting high quality may result in longer download times."
              });
            }
            ,select : function(comboBox,rec) {
              mainStore.getAt(idx).set('settingsImageQuality',rec.get('value'));
              mainStore.getAt(idx).commit();
              setCustomStyles(mainStore.getAt(idx));
            }
          }
        })
      )
    }
    if (mainStore.getAt(idx).get('settingsPalette') != '') {
      height += 27;
      items.push(
        new Ext.form.ComboBox({
           fieldLabel     : 'Palette<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.palette' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.palette' + '" src="img/info.png"></a>'
          ,id             : 'palette.' + id
          ,store          : palettesStore[layerName]
          ,displayField   : 'name'
          ,valueField     : 'name'
          ,value          : mainStore.getAt(idx).get('settingsPalette')
          ,editable       : false
          ,triggerAction  : 'all'
          ,mode           : 'local'
          ,width          : 130
          ,forceSelection : true
          ,tpl            : new Ext.XTemplate('<tpl for="."><div class="x-combo-list-item"><table class="smallFont"><tr><td><img width=50 height=10 src="legends/{icon}.png"> </td><td>{icon}</td></tr></table></div></tpl>')
          ,listeners      : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.palette'
                ,target : id + '.palette'
                ,html   : "Feature contrasts may become more obvious based on the selected palette."
              });
            }
            ,select : function(comboBox,rec) {
              mainStore.getAt(idx).set('settingsPalette',rec.get('name'));
              mainStore.getAt(idx).commit();
              var lyr = map.getLayersByName(mainStore.getAt(idx).get('name'))[0];
              lyr.mergeNewParams({STYLES : rec.get('name'),PALETTE : rec.get('name').split('/')[1]});
            }
          }
        })
      )
    }
    if (mainStore.getAt(idx).get('settingsLayers') != '') {
      height += 27;
      items.push(
        new Ext.form.ComboBox({
           fieldLabel     : 'Compositing<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.layers' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.layers' + '" src="img/info.png"></a>'
          ,id             : 'layers.' + id
          ,store          : layersStore[layerName]
          ,displayField   : 'name'
          ,valueField     : 'wmsName'
          ,value          : mainStore.getAt(idx).get('settingsLayers')
          ,editable       : false
          ,triggerAction  : 'all'
          ,mode           : 'local'
          ,width          : 130
          ,forceSelection : true
          ,listeners      : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.layers'
                ,target : id + '.layers'
                ,html   : "Select the type of composite or pass you wish to view."
              });
            }
            ,select : function(comboBox,rec) {
              mainStore.getAt(idx).set('settingsLayers',rec.get('wmsName'));
              mainStore.getAt(idx).commit();
              var lyr = map.getLayersByName(mainStore.getAt(idx).get('name'))[0];
              lyr.mergeNewParams({LAYERS : rec.get('wmsName')});
            }
          }
        })
      )
    }
    if (mainStore.getAt(idx).get('settingsBaseStyle') != '') {
      height += 27;
      items.push(
        new Ext.form.ComboBox({
           fieldLabel     : 'Base style<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.baseStyle' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.baseStyle' + '" src="img/info.png"></a>'
          ,id             : 'baseStyle.' + id
          ,store          : baseStylesStore
          ,displayField   : 'name'
          ,valueField     : 'value'
          ,value          : mainStore.getAt(idx).get('settingsBaseStyle')
          ,editable       : false
          ,triggerAction  : 'all'
          ,mode           : 'local'
          ,width          : 130
          ,forceSelection : true
          ,lastQuery      : ''
          ,listeners      : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.baseStyle'
                ,target : id + '.baseStyle'
                ,html   : "In general, the Black base style has a better appearance if high resolution is also selected."
              });
            }
            ,select : function(comboBox,rec) {
              if (rec.get('value') == 'CURRENTS_STATIC_BLACK' && Ext.getCmp('colorMap')) {
                Ext.getCmp('colorMap').disable();
              }
              else if (Ext.getCmp('colorMap')) {
                Ext.getCmp('colorMap').enable();
              }
              mainStore.getAt(idx).set('settingsBaseStyle',rec.get('value'));
              mainStore.getAt(idx).commit();
              setCustomStyles(mainStore.getAt(idx));
            }
            ,beforerender : function() {
              baseStylesStore.filter('type',mainStore.getAt(idx).get('settingsBaseStyle').split('_')[0]);
            }
          }
        })
      )
    }
    if (mainStore.getAt(idx).get('settingsColorMap') != '') {
      height += 27;
      items.push(
        new Ext.form.ComboBox({
           fieldLabel     : 'Colormap<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.colormap' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.colormap' + '" src="img/info.png"></a>'
          ,id             : 'colorMap.' + id
          ,disabled       : mainStore.getAt(idx).get('settingsBaseStyle') == 'CURRENTS_STATIC_BLACK'
          ,store          : colorMapStore
          ,displayField   : 'name'
          ,valueField     : 'name'
          ,value          : mainStore.getAt(idx).get('settingsColorMap')
          ,editable       : false
          ,triggerAction  : 'all'
          ,mode           : 'local'
          ,width          : 130
          ,forceSelection : true
          ,listeners      : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.colormap'
                ,target : id + '.colormap'
                ,html   : "Feature contrasts may become more obvious based on the selected colormap."
              });
            }
            ,select : function(comboBox,rec) {
              mainStore.getAt(idx).set('settingsColorMap',rec.get('name'));
              mainStore.getAt(idx).commit();
              setCustomStyles(mainStore.getAt(idx));
            }
          }
        })
      )
    }
    if (mainStore.getAt(idx).get('settingsMinMaxBounds') != '') {
      height += 27;
      var settingsParam = mainStore.getAt(idx).get('settingsParam').split(',');
      var settings = {};
      for (var i = 0; i < settingsParam.length; i++) {
        if (settingsParam[i] != '') {
          settings[settingsParam[i]] = guaranteeDefaultStyles[mainStore.getAt(idx).get('name')].split('-')[i];
        }
      }
      items.push(
        new Ext.slider.MultiSlider({
           fieldLabel : 'Min/max<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.minMax' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.minMax' + '" src="img/info.png"></a>'
          ,id       : 'minMax.' + id
          ,width    : 130
          ,minValue : mainStore.getAt(idx).get('settingsMinMaxBounds').split('-')[0]
          ,maxValue : mainStore.getAt(idx).get('settingsMinMaxBounds').split('-')[1]
          ,decimalPrecision : 1
          ,values   : [mainStore.getAt(idx).get('settingsMin'),mainStore.getAt(idx).get('settingsMax')]
          ,plugins  : new Ext.slider.Tip({
            getText : function(thumb) {
              return String.format('<b>{0}</b>', thumb.value);
            }
          })
          ,listeners : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.minMax'
                ,target : id + '.minMax'
                ,html   : "Use the slider to adjust the layer's minimum and maximum values."
              });
            }
            ,change : function(slider) {
              mainStore.getAt(idx).set('settingsMin',slider.getValues()[0]);
              mainStore.getAt(idx).set('settingsMax',slider.getValues()[1]);
              mainStore.getAt(idx).commit();
              setCustomStyles(mainStore.getAt(idx));
            }
          }
        })
      )
    }
    if (mainStore.getAt(idx).get('settingsStriding') != '') {
      height += 27;
      items.push(
        new Ext.Slider({
           fieldLabel : 'Data density<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.striding' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.striding' + '" src="img/info.png"></a>'
          ,id       : 'striding.' + id
          ,width    : 130
          ,minValue : 0
          ,maxValue : stridingStore.getCount() - 1
          ,value    : stridingStore.find('param',mainStore.getAt(idx).get('settingsStriding'))
          ,plugins  : new Ext.slider.Tip({
            getText : function(thumb) {
              var pct = stridingStore.getAt(thumb.value).get('param');
              var s;
              if (thumb.value == 0) {
                s = 'sparsest';
              }
              else if (pct < 1) {
                s = 'sparser';
              }
              else if (pct == 1) {
                s = 'normal';
              }
              else if (thumb.value < stridingStore.getCount() - 1) {
                s = 'denser';
              }
              else if (thumb.value == stridingStore.getCount() - 1) {
                s = 'densest';
              }
              return String.format('<b>{0}</b>',s);
            }
          })
          ,listeners : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.striding'
                ,target : id + '.striding'
                ,html   : "Adjust the space between vectors with the data density factor.  The impact of this value varies based on the zoom level."
              });
            }
            ,change : function(slider,val) {
              mainStore.getAt(idx).set('settingsStriding',stridingStore.getAt(val).get('param'));
              mainStore.getAt(idx).commit();
              setCustomStyles(mainStore.getAt(idx));
            }
          }
        })
      )
    }
    if (mainStore.getAt(idx).get('settingsTailMag') != '') {
      height += 27;
      items.push(
        new Ext.form.ComboBox({
           fieldLabel     : 'Tail magnitude<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.tailMagnitude' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.tailMagnitude' + '" src="img/info.png"></a>'
          ,id             : 'tailMag.' + id
          ,store          : tailMagStore
          ,displayField   : 'name'
          ,valueField     : 'name'
          ,value          : mainStore.getAt(idx).get('settingsTailMag')
          ,editable       : false
          ,triggerAction  : 'all'
          ,mode           : 'local'
          ,width          : 130
          ,forceSelection : true
          ,listeners      : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.tailMagnitude'
                ,target : id + '.tailMagnitude'
                ,html   : "Choose whether or not the vector tail length will vary based on its magnitude.  The difference may be subtle in layers with small magnitude variability." 
              });
            }
            ,select : function(comboBox,rec) {
              mainStore.getAt(idx).set('settingsTailMag',rec.get('name'));
              mainStore.getAt(idx).commit();
              setCustomStyles(mainStore.getAt(idx));
            }
          }
        })
      )
    }
    if (mainStore.getAt(idx).get('settingsBarbLabel') != '') {
      height += 27;
      items.push(
        new Ext.form.ComboBox({
           fieldLabel     : 'Magnitude label<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.magnitudeLabel' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.magnitudeLabel' + '" src="img/info.png"></a>'
          ,id             : 'barbLabel.' + id
          ,store          : barbLabelStore
          ,displayField   : 'name'
          ,valueField     : 'name'
          ,value          : mainStore.getAt(idx).get('settingsBarbLabel')
          ,editable       : false
          ,triggerAction  : 'all'
          ,mode           : 'local'
          ,width          : 130
          ,forceSelection : true
          ,listeners      : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.magnitudeLabel'
                ,target : id + '.magnitudeLabel'
                ,html   : "Choose whether or not a text label should be drawn by each vector to identify its magnitude."
              });
            }
            ,select : function(comboBox,rec) {
              mainStore.getAt(idx).set('settingsBarbLabel',rec.get('name'));
              mainStore.getAt(idx).commit();
              setCustomStyles(mainStore.getAt(idx));
            }
          }
        })
      )
    }

    activeSettingsWindows[layerName] = new Ext.Window({
       bodyStyle : 'background:white;padding:5'
      ,x         : pos.left
      ,y         : pos.top
      ,resizable : false
      ,width     : 270
      ,constrainHeader : true
      ,title     : mainStore.getAt(idx).get('displayName').split('||')[0] + ' :: settings'
      ,items     : [
         new Ext.FormPanel({buttonAlign : 'center',border : false,bodyStyle : 'background:transparent',width : 240,height : height + 35,labelWidth : 100,labelSeparator : '',items : items,buttons : [{text : 'Restore default settings',width : 150,handler : function() {restoreDefaultStyles(layerName,items,id)}}]})
      ]
      ,listeners : {hide : function() {
        activeSettingsWindows[layerName] = null;
      }}
    }).show();
  }
}

function renderLayerButton(val,metadata,rec) {
  if (rec.get('type') == 'gliders') {
    return '<img  width=30 height=25 src="img/' + rec.get('name') + '.drawn.png">';
  }
  else if (config == 'ecop') {
    return '<img  width=20 height=20 src="img/DEFAULT.drawn.png">';
  }
  else {
    return '<img  width=20 height=20 src="img/' + rec.get('name') + '.drawn.png">';
  }
}

function renderLayerInfoLink(val,metadata,rec) {
  return '<span class="name">' + val.split('||')[0] + '</span>';
}

function renderSettingsButton(val,metadata,rec) {
  if (val != '') {
    return '<a id="settings.' + rec.get('name') + '" href="javascript:setLayerSettings(\'' + rec.get('name')  + '\',\'' + rec.get('settings') + '\' != \'on\')"><img title="Customize layer appearance" class="settingsIcon" width=20 height=20 src="img/settings.' + rec.get('settings') + '.png"></a>';
  }
}

function renderLayerCalloutButton(val,metadata,rec) {
  return '<a id="info.' + rec.get('name') + '" href="javascript:setLayerInfo(\'' + rec.get('name')  + '\',\'' + rec.get('info') + '\' != \'on\')"><img title="Customize layer appearance" style="margin-top:2px" src="img/page_go.png"></a>';
}

function renderLayerStatus(val,metadata,rec) {
  if (val == 'loading') {
    if (rec.get('type') == 'gliders') {
      return '<img src="img/blank.png" height=23 width=0><img src="img/loading.gif">';
    }
    else {
      return '<img src="img/loading.gif">';
    }
  }
  else {
    if (rec.get('type') == 'gliders') {
      return '<img class="layerIconGlider" src="img/' + rec.get('name') + '.drawn.png">';
    }
    else if (config == 'ecop') {
      return '<img class="layerIcon" src="img/DEFAULT.drawn.png">';
    }
    else {
      return '<img class="layerIcon" src="img/' + rec.get('name') + '.drawn.png">';
    }
  }
}

function renderLegend(val,metadata,rec) {
  var idx = mainStore.find('name',rec.get('name'));
  var a = [rec.get('displayName').split('||')[0]];
  if (rec.get('timestamp') && rec.get('timestamp') != '') {
    a.push(rec.get('timestamp'));
  }
  if (mainStore.getAt(idx).get('legend') != '') {
    if (!legendImages[rec.get('name')]) {
      var img = new Image();
      img.src = 'getLegend.php?' + mainStore.getAt(idx).get('legend');
      legendImages[rec.get('name')] = img;
    }

    var customize = '<table><tr><td width=20><a id="settings.' + rec.get('name') + '" title="Customize this layer\'s appearance" href="javascript:setLayerSettings(\'' + rec.get('name') + '\',true)"><img width=16 height=16 src="img/setting_tools.png"></a></td><td><a title="Customize this layer\'s appearance" href="javascript:setLayerSettings(\'' + rec.get('name') + '\',true)">Customize&nbsp;this&nbsp;layer</a></td></tr></table>';
    if (map.getLayersByName(rec.get('name'))[0].featureFactor) {
      customize = '';
    }

    a.push('<img src="getLegend.php?' + mainStore.getAt(idx).get('legend') + '">');
  }
  return a.join('<br/>');
}

function renderGlidersDescription(val,metadata,rec) {
  metadata.attr = 'ext:qtip="' + val + '"';
  return val + ' (' + rec.get('name') + ')';
}

function addLayer(lyr,timeSensitive) {
  lyr.events.register('visibilitychanged',this,function(e) {
    if (!lyr.visibility) {
      var idx = legendsStore.find('name',lyr.name);
      if (idx >= 0) {
        legendsStore.removeAt(idx);
      }
      idx = chartLayerStore.find('name',lyr.name);
      if (idx >= 0) {
        chartLayerStore.removeAt(idx);
        if (Ext.getCmp('chartLayerCombo').getValue() == lyr.name) {
          Ext.getCmp('chartLayerCombo').clearValue();
        }
      }
      layerLoadendUnmask();
    }
    checkRealtimeAlert();
  });
  lyr.events.register('loadstart',this,function(e) {
    layerLoadstartMask();
    var idx = legendsStore.find('name',lyr.name);
    if (idx >= 0) {
      var rec = legendsStore.getAt(idx);
      rec.set('status','loading');
      rec.commit();
    }
    else {
      var rec = mainStore.getAt(mainStore.find('name',lyr.name));
      legendsStore.add(new legendsStore.recordType({
         name        : lyr.name
        ,displayName : rec.get('displayName')
        ,status      : 'loading'
        ,rank        : rec.get('rank')
        ,fetchTime   : rec.get('timestamp') != 'false'
        ,type        : rec.get('type')
      }));
    }
    idx = chartLayerStore.find('name',lyr.name);
    if (idx < 0) {
      var mainIdx = mainStore.find('name',lyr.name);
      if (mainStore.getAt(mainIdx).get('queryable') == 'true') {
        chartLayerStore.add(new chartLayerStore.recordType({
           rank        : mainStore.getAt(mainIdx).get('rank')
          ,name        : lyr.name
          ,displayName : mainStore.getAt(mainIdx).get('displayName').split('||')[0]
          ,category    : mainStore.getAt(mainIdx).get('category')
        }));
      }
    }
    // record the action on google analytics
    pageTracker._trackEvent('layerView','loadStart',mainStore.getAt(mainStore.find('name',lyr.name)).get('displayName'));
  });
  lyr.events.register('loadend',this,function(e) {
    var idx = legendsStore.find('name',lyr.name);
    if (idx >= 0) {
      var rec = legendsStore.getAt(idx);
      rec.set('status','drawn');
      rec.commit();
      if (rec.get('fetchTime')) {
        OpenLayers.Request.GET({
           url      : 'getTimestamp.php?'
            + lyr.getFullRequestString({})
            + '&WIDTH='  + map.getSize().w
            + '&HEIGHT=' + map.getSize().h
            + '&BBOX=' +  map.getExtent().toArray().join(',')
            + '&' + new Date().getTime()
            + '&drawImg=false'
          ,callback : function(r) {
            if (r.responseText == '') {
              rec.set('timestamp','<span class="alert">There was a problem<br/>drawing this layer.<span>');
            }
            else if (r.responseText == 'invalidBbox') {
              rec.set('timestamp','<span class="alert">This layer\'s domain<br/>is out of bounds.<span>');
            }
            else if (r.responseText == 'dateNotAvailable') {
              rec.set('timestamp','');
            }
            else {
              var prevTs = rec.get('timestamp');
              var newTs  = shortDateString(new Date(r.responseText * 1000));
              rec.set('timestamp',newTs);
            }
          }
        });
      }
    }
    layerLoadendUnmask();
    // record the action on google analytics
    pageTracker._trackEvent('layerView','loadEnd',mainStore.getAt(mainStore.find('name',lyr.name)).get('displayName'));
  });
  if (timeSensitive) {
    lyr.mergeNewParams({TIME : dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + 'T' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00'});
    // the sat SST tds layer can be ID'ed by GFI_TIME -- this layer also needs COLORSCALERANGE
    // I didn't want to make this part of the URL
    if (lyr.url.indexOf('GFI_TIME') >= 0 && lyr.name != 'Chlorophyll concentration') {
      lyr.mergeNewParams({COLORSCALERANGE : getColorScaleRange()}); 
    }
  }
  map.addLayer(lyr);
}

function addWMS(l) {
  if (restrictLayers && !restrictLayers[l.name]) {
    return;
  }
  var lyr = new OpenLayers.Layer.WMS(
     l.name
    ,l.url
    ,{
       layers      : l.layers
      ,format      : l.format
      ,transparent : true
      ,styles      : l.styles
    }
    ,{
       isBaseLayer : false
      ,projection  : l.projection
      ,singleTile  : l.singleTile
      ,visibility  : mainStore.getAt(mainStore.find('name',l.name)).get('status') == 'on'
      ,opacity     : mainStore.getAt(mainStore.find('name',l.name)).get('settingsOpacity') / 100
      ,wrapDateLine : true
    }
  );
  addLayer(lyr,true);
}

function addTileCache(l) {
  if (restrictLayers && !restrictLayers[l.name]) {
    return;
  }
  var lyr = new OpenLayers.Layer.TileCache(
     l.name
    ,l.url
    ,l.layer
    ,{
       visibility        : mainStore.find('name',l.name) >= 0 ? mainStore.getAt(mainStore.find('name',l.name)).get('status') == 'on' : false
      ,isBaseLayer       : false
      ,wrapDateLine      : true
      ,projection        : l.projection
      ,opacity           : mainStore.find('name',l.name) >= 0 ? mainStore.getAt(mainStore.find('name',l.name)).get('settingsOpacity') / 100 : 1
      ,scales            : [
         55468034.09273208   // ESRI Ocean zoom 3
        ,27734017.04636604
        ,13867008.52318302
        ,6933504.26159151
        ,3466752.130795755
        ,1733376.0653978775
        ,866688.0326989387
        ,433344.01634946937
        ,216672.00817473468
      ]
    }
  );
  lyr.getURL = function(bounds) {
    var res = this.map.getResolution();
    var bbox = this.maxExtent;
    var size = this.tileSize;
    var tileX = Math.round((bounds.left - bbox.left) / (res * size.w));
    var tileY = Math.round((bounds.bottom - bbox.bottom) / (res * size.h));
    var tileZ = this.serverResolutions != null ?
        OpenLayers.Util.indexOf(this.serverResolutions, res) :
        this.map.getZoom();
    // this is the trick
    tileZ += map.baseLayer.minZoomLevel ? map.baseLayer.minZoomLevel : 0;
    /**
     * Zero-pad a positive integer.
     * number - {Int}
     * length - {Int}
     *
     * Returns:
     * {String} A zero-padded string
     */
    function zeroPad(number, length) {
        number = String(number);
        var zeros = [];
        for(var i=0; i<length; ++i) {
            zeros.push('0');
        }
        return zeros.join('').substring(0, length - number.length) + number;
    }
    var components = [
        this.layername,
        zeroPad(tileZ, 2),
        zeroPad(parseInt(tileX / 1000000), 3),
        zeroPad((parseInt(tileX / 1000) % 1000), 3),
        zeroPad((parseInt(tileX) % 1000), 3),
        zeroPad(parseInt(tileY / 1000000), 3),
        zeroPad((parseInt(tileY / 1000) % 1000), 3),
        zeroPad((parseInt(tileY) % 1000), 3) + '.' + this.extension
    ];
    var path = components.join('/');
    var url = this.url;
    if (url instanceof Array) {
        url = this.selectUrl(path, url);
    }
    url = (url.charAt(url.length - 1) == '/') ? url : url + '/';
    return url + path;
  };
  addLayer(lyr,false);
}

function addTMS(l) {
  if (restrictLayers && !restrictLayers[l.name]) {
    return;
  }
  var lyr = new OpenLayers.Layer.TMS(
     l.name
    ,l.url
    ,{
       layername   : l.layer
      ,type        : l.format
      ,visibility  : mainStore.find('name',l.name) >= 0 ? mainStore.getAt(mainStore.find('name',l.name)).get('status') == 'on' : false
      ,isBaseLayer : false
      ,projection  : l.projection
      ,opacity     : mainStore.find('name',l.name) >= 0 ? mainStore.getAt(mainStore.find('name',l.name)).get('settingsOpacity') / 100 : 1
      ,scales      : [
         55468034.09273208   // ESRI Ocean zoom 3
        ,27734017.04636604
        ,13867008.52318302
        ,6933504.26159151
        ,3466752.130795755
        ,1733376.0653978775
        ,866688.0326989387
        ,433344.01634946937
        ,216672.00817473468
      ]
      ,time        : new Date().getTime()
      ,getURL      : function (bounds) {
        bounds = this.adjustBounds(bounds);
        var res = this.map.getResolution();
        var x = Math.round((bounds.left - this.tileOrigin.lon) / (res * this.tileSize.w));
        // var y = Math.round((bounds.bottom - this.tileOrigin.lat) / (res * this.tileSize.h));
        var y = Math.round((this.maxExtent.top - bounds.top) / (res * this.tileSize.h));
        var z = this.serverResolutions != null ?
            OpenLayers.Util.indexOf(this.serverResolutions, res) :
            this.map.getZoom() + this.zoomOffset;
        z += map.baseLayer.minZoomLevel ? map.baseLayer.minZoomLevel : 0;
        var path = this.serviceVersion + "/" + this.layername + "/" + z + "/" + x + "/" + y + "." + this.type;
        var url = this.url;
        if (OpenLayers.Util.isArray(url)) {
            url = this.selectUrl(path, url);
        }
        return url + path + '?time=' + this.options.time;
      }
    }
  );
  addLayer(lyr,false);
}

function addObs(l) {
  if (restrictLayers && !restrictLayers[l.name]) {
    return;
  }
  var lyr = new OpenLayers.Layer.Vector(
     l.name
    ,{
      styleMap : new OpenLayers.StyleMap({
        'default' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
           externalGraphic : 'img/' + l.name + '.png'
          ,pointRadius     : 8
          ,graphicWidth    : '${graphicWidth}'
          ,graphicHeight   : '${graphicHeight}'
          ,graphicOpacity  : 1
          // ,rotation        : '${rotation}'
          ,strokeWidth     : '${strokeWidth}'
          ,strokeColor     : '${strokeColor}'
          ,strokeOpacity   : '${strokeOpacity}'
          ,strokeDashstyle : '${strokeDashstyle}'
        }))
        ,'select' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
           externalGraphic : 'img/' + l.name + '.select.png'
          ,pointRadius     : 8
          ,graphicWidth    : '${graphicWidthBig}'
          ,graphicHeight   : '${graphicHeightBig}'
          ,graphicOpacity  : 1
          // ,rotation        : '${rotation}'
          ,strokeWidth     : '${strokeWidth}'
          ,strokeColor     : '${strokeColor}'
          ,strokeOpacity   : '${strokeOpacity}'
          ,strokeDashstyle : '${strokeDashstyle}'
        }))
        ,'temporary' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
           externalGraphic : 'img/' + l.name + '.hilite.png'
          ,pointRadius     : 8
          ,graphicWidth    : '${graphicWidthBig}'
          ,graphicHeight   : '${graphicHeightBig}'
          ,graphicOpacity  : 1
          // ,rotation        : '${rotation}'
          ,strokeWidth     : '${strokeWidth}'
          ,strokeColor     : '${strokeColor}'
          ,strokeOpacity   : '${strokeOpacity}'
          ,strokeDashstyle : '${strokeDashstyle}'
        }))
      })
      ,visibility : l.visibility
    }
  );

  lyr.events.register('visibilitychanged',this,function(e) {
    if (!lyr.visibility) {
      var idx = legendsStore.find('name',lyr.name);
      if (idx >= 0) {
        legendsStore.removeAt(idx);
      }
      layerLoadendUnmask(); 
    }
    else {
      syncObs({name : lyr.name});
    }
  });
  lyr.events.register('loadstart',this,function(e) {
    layerLoadstartMask();
    var idx = legendsStore.find('name',lyr.name);
    if (idx >= 0) {
      var rec = legendsStore.getAt(idx);
      rec.set('status','loading');
      rec.commit();
    }
    else if (lyr.visibility) {
      var rec = mainStore.getAt(mainStore.find('name',lyr.name));
      legendsStore.add(new legendsStore.recordType({
         name        : lyr.name
        ,displayName : rec.get('displayName')
        ,status      : 'loading'
        ,rank        : rec.get('rank')
        ,type        : rec.get('type')
      }));
    }
    // record the action on google analytics
    pageTracker._trackEvent('layerView','loadStart',mainStore.getAt(mainStore.find('name',lyr.name)).get('displayName'));
  });
  lyr.events.register('loadend',this,function(e) {
    var idx          = legendsStore.find('name',lyr.name);
    var assetsIndex  = assetsStore.find('name',lyr.name);
    var glidersIndex = glidersStore.find('name',lyr.name);
    if (idx >= 0) {
      var rec = legendsStore.getAt(idx);
      rec.set('status','drawn');
      var mainStoreRec = mainStore.getAt(mainStore.find('name',lyr.name));
      if (map.getZoom() + zoomOffset() < obsMinZoom[lyr.name]) {
        mainStoreRec.set('legend','img/zoom.png');
        rec.set('timestamp',(lyr.featureFactor ? lyr.features.length * lyr.featureFactor : 0) + ' site(s) fetched<br/><span class="alert">More sites available<br/>at a closer zoom.<span>');
      }
      else if (assetsIndex >= 0) {
        var leg = assetsStore.getAt(assetsIndex).get('legend');
        if (leg.indexOf('legends') < 0) {
          leg = '';
        }
        mainStoreRec.set('legend',leg);
        rec.set('timestamp',(lyr.featureFactor ? lyr.features.length * lyr.featureFactor : 0) + ' site(s) fetched');
      }
      else if (glidersIndex >= 0) {
        var leg = glidersStore.getAt(glidersIndex).get('legend');
        if (leg.indexOf('legends') < 0) {
          leg = '';
        }
        mainStoreRec.set('legend',leg);
        var a = ['<td colspan=2>' + (lyr.features.length / 2) + ' glider(s) fetched' + '</td>']; 
        var activity = {
           active   : 0
          ,inactive : 0
        };
        var providerHits = {};
        var maxT;
        glidersMetadataStore.each(function(rec) {
          providerHits[rec.get('name')] = 0;
        });
        for (var i = 0; i < lyr.features.length; i++) {
          if (lyr.features[i].attributes.active) {
            activity['active'] += 0.5;
          }
          else if (lyr.features[i].attributes.inactive) {
            activity['inactive']++;
          }
          providerHits[lyr.features[i].attributes.provider]++;
          if (lyr.features[i].attributes.maxT && (!maxT || maxT < lyr.features[i].attributes.maxT)) {
            maxT = lyr.features[i].attributes.maxT;
          }
        }
        if (lyr.features.length > 0) {
          a.push('<td>active</td><td align=right>' + activity['active'] + '</td>');
          a.push('<td>inactive</td><td align=right>' + activity['inactive'] + '</td>');
          a.push('<td colspan=2 align=center>by provider</td>'); 
        }
        glidersMetadataStore.each(function(rec) {
          if (providerHits[rec.get('name')] > 0) {
            a.push('<td>' + rec.get('name') + '</td><td align=right>' + providerHits[rec.get('name')] + '</td>');
          }
        });
        if (maxT) {
          a.push('<td colspan=2 align=center>latest report</td>');
          a.push('<td colspan=2 align=center>' + maxT + '</td>');
        }
        rec.set('timestamp','<table><tr>' + a.join('</tr><tr>') + '</tr></table>');
      }
      mainStoreRec.commit();
      rec.commit();
    }
    layerLoadendUnmask();
    // record the action on google analytics
    pageTracker._trackEvent('layerView','loadEnd',mainStore.getAt(mainStore.find('name',lyr.name)).get('displayName'));
  });
  map.addLayer(lyr);

  if (!hiliteCtl) {
    hiliteCtl = new OpenLayers.Control.SelectFeature(lyr,{
       hover         : true
      ,highlightOnly : true
      ,renderIntent  : 'temporary'
      ,eventListeners : {
        beforefeaturehighlighted : function(e) {
          if (mouseoverObs && mouseoverObs.isVisible()) {
            mouseoverObs.hide();
          }
          // figure out the target id (the id of the dot)
          var showPopup = false;
          var target;
          var title;
          for (var i in e.feature.attributes.data) {
            for (var j = 0; j < e.feature.attributes.data[i].length; j++) {
              title = e.feature.attributes.data[i][0].descr;
              var glidersIdx = glidersMetadataStore.find('name',title.split(' ')[0]);
              if (glidersIdx >= 0) {
                title = glidersMetadataStore.getAt(glidersIdx).get('description') + ' ::' + title.replace(title.split(' ')[0],'');
              }
              target = 'OpenLayers.Geometry.Point_' + (Number(e.feature.id.split('_')[e.feature.id.split('_').length - 1]) - 1);
              if (e.feature.attributes.featureId) {
                target = 'OpenLayers.Geometry.Point_' + (Number(e.feature.attributes.featureId.split('_')[e.feature.attributes.featureId.split('_').length - 1]) - 3);
              }
              if (e.feature.attributes.data[i][0].url) {
                showPopup = true;
              }
            }
          }
          if (!showPopup) {
            return;
          }
          mouseoverObs = new Ext.ToolTip({
             html         : title
            ,anchor       : 'bottom'
            ,target       : target
            ,hideDelay    : 0
            ,listeners    : {
              hide    : function(tt) {
                if (!tt.isDestroyed && !Ext.isIE) {
                  tt.destroy();
                }
              }
            }
          });
          mouseoverObs.show();
        }
      }
    });
    map.addControl(hiliteCtl);
    hiliteCtl.activate();
  }
  else {
    var layers = [lyr];
    if (hiliteCtl.layers) {
      for (var i = 0; i < hiliteCtl.layers.length; i++) {
        layers.push(hiliteCtl.layers[i]);
      }
    }
    else {
      layers.push(hiliteCtl.layer);
    }
    hiliteCtl.setLayer(layers);
  }

  if (!popupCtl) {
    popupCtl = new OpenLayers.Control.SelectFeature(lyr,{
      eventListeners : {
        featurehighlighted : function(e) {
          if (popupObs && popupObs.isVisible()) {
            popupObs.hide();
          }
          // don't relaunch the popup request if it's already up
          if (e.feature.attributes.featureId) {
            if (Ext.getCmp(e.feature.attributes.featureId)) {
              return;
            }
          }
          else if (e.feature.id) {
            if (Ext.getCmp(e.feature.id)) {
              return;
            }
          }
          // figure out the target id (the id of the dot)
          var showPopup = false;
          var target;
          var title;
          for (var i in e.feature.attributes.data) {
            for (var j = 0; j < e.feature.attributes.data[i].length; j++) {
              title = e.feature.attributes.data[i][0].descr;
              var glidersIdx = glidersMetadataStore.find('name',title.split(' ')[0]);
              if (glidersIdx >= 0) {
                title = glidersMetadataStore.getAt(glidersIdx).get('description') + ' ::' + title.replace(title.split(' ')[0],'');
              }
              target = 'OpenLayers.Geometry.Point_' + (Number(e.feature.id.split('_')[e.feature.id.split('_').length - 1]) - 1);
              if (e.feature.attributes.featureId) {
                target = 'OpenLayers.Geometry.Point_' + (Number(e.feature.attributes.featureId.split('_')[e.feature.attributes.featureId.split('_').length - 1]) - 3);
              }
              if (e.feature.attributes.data[i][0].url) {
                showPopup = true;
              }
            }
          }
          if (!showPopup) {
            return;
          }
          popupObs = new Ext.ToolTip({
             title     : title
            ,id        : e.feature.attributes.featureId ? e.feature.attributes.featureId : e.feature.id
            ,anchor    : 'bottom'
            ,width     : 345
            ,target    : target
            ,autoHide  : false
            ,closable  : true
            ,items     : {bodyCssClass : 'obsPopup',html : "<span id ='" + target + ".data'><table style='width:100%'><tr><td style='text-align:center'><img width=44 height=44 src='img/spinner.gif'></td></tr></table></span>"}
            ,listeners : {
              hide    : function(tt) {
                if (!tt.isDestroyed) {
                  tt.destroy();
                }
                if (e.feature.layer) {
                  popupCtl.unselect(e.feature);
                }
              }
              ,render : function() {
                for (var i in e.feature.attributes.data) {
                  for (var j = 0; j < e.feature.attributes.data[i].length; j++) {
                    OpenLayers.Request.GET({
                       url      : e.feature.attributes.data[i][0].url + '&tz=' + new Date().getTimezoneOffset() + '&uom=english'
                      ,callback : OpenLayers.Function.bind(obsPopupCallback,null,target)
                    });
                  }
                }
              }
            }
          });
          popupObs.show();
          // record the action on google analytics
          pageTracker._trackEvent('obsView','popup',e.feature.attributes.data[i][0].descr);

        }
      }
    });
    map.addControl(popupCtl);
    popupCtl.activate();
  }
  else {
    var layers = [lyr];
    if (popupCtl.layers) {
      for (var i = 0; i < popupCtl.layers.length; i++) {
        layers.push(popupCtl.layers[i]);
      }
    }
    else {
      layers.push(popupCtl.layer);
    }
    popupCtl.setLayer(layers);
  }
}

function obsPopupCallback(target,r) {
  var obs = new OpenLayers.Format.JSON().read(r.responseText);
  var html = '';
  if (!obs) {
    html = '<table id="obsPopup"><tr><th style="text-align:center">No recent observations</th></tr></table>';
  }
  else {
    html = '<table id="obsPopup"><tr><td>' + obs.html + '</td></tr></table>';
  }
  if (document.getElementById(target + '.data')) {
    document.getElementById(target + '.data').innerHTML = html;
  }
  if (popupObs) {
    popupObs.suspendEvents();
    popupObs.hide();
    popupObs.show();
    // do a 2nd show to get the layout to work right
    Ext.defer(function(){popupObs.show()},100);
    popupObs.resumeEvents();
  }
}

function syncObs(l,force) {
  var lyrIdx;
  for (var j = 0; j < map.layers.length; j++) {
    if (map.layers[j].name == l.name) {
      lyrIdx = j;
    } 
  }

  if (!force && (!lyrIdx || !map.layers[lyrIdx].visibility)) {
    return;
  }

  var realExtent = map.getExtent();
  var bigExtent  = new OpenLayers.Geometry.LinearRing(map.getExtent().toGeometry().getVertices()).resize(obsBigExtentScale,new OpenLayers.Geometry.Point(map.getCenter().lon,map.getCenter().lat)).getBounds();

  map.layers[lyrIdx].events.triggerEvent('loadstart');
  if (force || !obsBbox[l.name] || !obsBbox[l.name].containsBounds(realExtent) || map.getZoom() + zoomOffset() != obsZoom[l.name]) {
    var everyNth = 1;
    if (map.getZoom() + zoomOffset() < obsMinZoom[l.name]) {
      everyNth = Math.pow(2,(obsMinZoom[l.name] - (map.getZoom() + zoomOffset())));
    }
    OpenLayers.Request.GET({
       url      : 'getObsLocations.php'
         + '?bbox='        + bigExtent.clone().transform(map.getProjectionObject(),proj4326).toArray() 
         + '&realBbox='    + realExtent.clone().transform(map.getProjectionObject(),proj4326).toArray()
         + '&zoom='        + (map.getZoom() + zoomOffset())
         + '&provider='    + l.name
         + '&everyNth='    + everyNth
         + getDateRange()
         + getFilter()
      ,callback : function(r) {
        map.layers[lyrIdx].removeFeatures(map.layers[lyrIdx].features);
        var obs = new OpenLayers.Format.JSON().read(r.responseText);
        obsBbox[l.name] = new OpenLayers.Bounds(obs.bbox[0],obs.bbox[1],obs.bbox[2],obs.bbox[3]).transform(proj4326,map.getProjectionObject());
        obsZoom[l.name] = obs.zoom;
        var boundsEqual = true;
        for (var loc in obs.data) {
          if (loc == 'remove' || loc == 'indexOf') {
            // not sure why this is coming back in the json 
          }
          // Gliders and drifters are unique beasts.
          else if (loc.toLowerCase().indexOf('gliders') >= 0 || loc.toLowerCase().indexOf('drifters') >= 0) {
            var provider = 'Gliders';
            if (loc.toLowerCase().indexOf('drifters') >= 0) {
              provider = 'Drifters';
            } 
            for (var i = 0; i < obs.data[loc][loc].length; i++) {
              var pts = [];
              for (var j = 0; j < obs.data[loc][loc][i].track.length; j++) {
                pts.push(new OpenLayers.Geometry.Point(obs.data[loc][loc][i].track[j][0],obs.data[loc][loc][i].track[j][1]).transform(proj4326,map.getProjectionObject()));
              }
              var ls = new OpenLayers.Geometry.LineString(pts);
              for (var k = 0; k < 4; k++) {
                if (obs.realBbox[k] != map.getExtent().transform(map.getProjectionObject(),proj4326).toArray()[k]) {
                  boundsEqual = false;
                }
              }
              if (boundsEqual) {
                var vec = new OpenLayers.Feature.Vector(ls);
                vec.attributes.provider      = provider;
                vec.attributes.active        = obs.data[loc][loc][i].active;
                vec.attributes.strokeWidth   = 2;
                vec.attributes.strokeColor   = gliderTracks[l.name] ? gliderTracks[l.name] : '#ffff00';
                if (provider == 'Drifters') {
                  vec.attributes.strokeColor = 'rgb(6,170,61)';
                }
                vec.attributes.strokeDashstyle = 'solid';
                if (obs.data[loc][loc][i].active) {
                  vec.attributes.strokeOpacity   = 0.90; // 0.80;
                }
                else {
                  vec.attributes.strokeOpacity   = 0.90; // 0.50;
                }
                map.layers[lyrIdx].addFeatures(vec);
                var f = new OpenLayers.Feature.Vector(pts[pts.length - 1]);
                f.attributes.featureId           = f.id;
                f.attributes.provider            = provider;
                f.attributes.data                = obs.data[loc];
                f.attributes.active              = obs.data[loc][loc][i].active;
                if (obs.data[loc][loc][i].t) {
                  f.attributes.maxT = obs.data[loc][loc][i].t[obs.data[loc][loc][i].t.length - 1];
                }
                f.attributes.graphicWidth        = 20;
                f.attributes.graphicWidthBig     = 20 * 2;
                f.attributes.graphicHeight       = 20;
                f.attributes.graphicHeightBig    = 20 * 2;
                f.attributes.rotation            = 0;
                f.attributes.inactive            = !f.attributes.active ? '.inactive' : '';
                if (loc.indexOf('gliders') >= 0) {
                  f.attributes.provider            = obs.data[loc][loc][i].provider;
                  f.attributes.graphicWidth        = 30;
                  f.attributes.graphicWidthBig     = 45;
                  f.attributes.graphicHeight       = 25;
                  f.attributes.graphicHeightBig    = 38;
                  if (pts.length >= 2) {
                    f.attributes.rotation = greatCircle(
                       obs.data[loc][loc][i].track[obs.data[loc][loc][i].track.length - 1][0]
                      ,obs.data[loc][loc][i].track[obs.data[loc][loc][i].track.length - 1][1]
                      ,obs.data[loc][loc][i].track[obs.data[loc][loc][i].track.length - 2][0]
                      ,obs.data[loc][loc][i].track[obs.data[loc][loc][i].track.length - 2][1]
                    ) + 90;
                  }
                }
                map.layers[lyrIdx].featureFactor = 0.5;
                map.layers[lyrIdx].addFeatures(f);
              }
            }
          }
          else {
            var p = loc.split(',');
            var f = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(p[0],p[1]).transform(proj4326,map.getProjectionObject()));
            f.attributes.data             = obs.data[loc];
            f.attributes.lon              = p[0];
            f.attributes.lat              = p[1];
            f.attributes.graphicOpacity   = 1;
            f.attributes.graphicWidth     = 20;
            f.attributes.graphicWidthBig  = 40;
            f.attributes.graphicHeight    = 20;
            f.attributes.graphicHeightBig = 40;
            f.attributes.rotation         = 0;
            f.attributes.inactive         = '';
            var p = [];
            for (var provider in obs.data[loc]) {
              p.push(provider);
            }
            // this is leftover from when all providers could be merged into 1 -- now we're only fetching 1 provider @ a time
            f.attributes.provider = p.join('-',p.sort());
            // make sure that a previous request doesn't overwrite this one
            for (var i = 0; i < 4; i++) {
              if (obs.realBbox[i] != map.getExtent().transform(map.getProjectionObject(),proj4326).toArray()[i]) {
                boundsEqual = false;
              }
            }
            if (boundsEqual) {
              map.layers[lyrIdx].featureFactor = 1;
              map.layers[lyrIdx].addFeatures(f);
            }
          }
        }
        map.layers[lyrIdx].events.triggerEvent('loadend');
      }
    });
  }
  else {
    map.layers[lyrIdx].events.triggerEvent('loadend');
  }
}

function showObsTimeseries(href) {
  graphLoadstartMask();
  var p = OpenLayers.Util.getParameters(href[0].split('http://')[href[0].split('http://').length - 1]);
  // USGS is differnent . . . of course
  if (href[0].indexOf('&USGS=') >= 0) {
    p = OpenLayers.Util.getParameters('http://foo.bar/' + href[0].substr(0,href[0].indexOf('http')));
  }
  var pix = map.getPixelFromLonLat(new OpenLayers.LonLat(p['lon'],p['lat']).transform(proj4326,map.getProjectionObject()));

  // see if there are any WMS layers we can use as a pivot point based on the cat param
  var a = [];
  if (p['cat'] != '') {
    Ext.getCmp('chartLayerCombo').getStore().each(function(rec) {
      if (p['cat'] == mainStore.getAt(mainStore.find('name',rec.get('name'))).get('category')) {
        Ext.getCmp('chartLayerCombo').setValue(rec.get('name'));
        a = mapClick({x : pix.x,y : pix.y},true,false) || [];
      }
    });
  }
  for (var i = 0 ; i < href.length; i++) {
    a.push({url : href[i],title : popupObs.title.split(' - ')[0],type : 'obs',dontAdvanceColor : i == 1});
  }
  var model = false;
  for (var i = 0; i < a.length; i++) {
    model = model || a[i].type == 'model';
  }
  makeChart(model ? 'model' : 'obs',a);
}

function makeChart(type,a) {
  if (type == 'obs') {
    Ext.getCmp('chartLayerCombo').hide();
    Ext.getCmp('activeLabel').setText(popupObs.title);
  }
  else {
    Ext.getCmp('chartLayerCombo').show();
    Ext.getCmp('activeLabel').setText('Active model query layer: ');
  }
  for (var j = 0; j < a.length; j++) {
    chartUrls[a[j].url] = true;
  }
  chartData = [];
  var color;
  for (var j = 0; j < a.length; j++) {
    OpenLayers.Request.GET({
       url      : a[j].url
      ,callback : OpenLayers.Function.bind(makeChartCallback,null,a[j].title,lineColors[(j + (a[j].dontAdvanceColor ? -1 : 0)) % lineColors.length][0],a[j].type,a[j].url)
    });
  }
  function makeChartCallback(title,lineColor,type,url,r) {
    var obs = new OpenLayers.Format.JSON().read(r.responseText);
    var yaxis = 1;
    if (obs && obs.error) {
      chartData.push({
         data   : []
        ,label  : title.split('||')[0] + ': QUERY ERROR ' + obs.error
        ,nowIdx : ''
      });
      // record the action on google analytics
      pageTracker._trackEvent('chartView',title,'error');
    }
    else if (!obs || obs.d == '' || obs.d.length == 0) {
      chartData.push({
         data   : []
        ,label  : title.split('||')[0] + ': QUERY ERROR'
        ,nowIdx : ''
      });
      // record the action on google analytics
      pageTracker._trackEvent('chartView',title,'error');
    }
    else {
      // get rid of any errors if good, new data has arrived
      if (chartData.length == 1 && String(chartData[0]).indexOf('QUERY ERROR') == 0) {
        chartData.pop();
      }
      for (var v in obs.d) {
        // get the data
        chartData.push({
           data   : []
          ,label  : title.split('||')[0] + ' : ' + v + ' (' + toEnglish({typ : 'title',src : obs.u[v],val : obs.u[v]}) + ')'
          ,yaxis  : yaxis
          ,lines  : {show : true}
          ,nowIdx : obs.d[v].length > 1 ? obs.nowIdx : ''
          ,color  : lineColor
          ,type   : type
        });
        for (var i = 0; i < obs.d[v].length; i++) {
          chartData[chartData.length-1].data.push([obs.t[i],toEnglish({typ : 'obs',src : obs.u[v],val : obs.d[v][i]})]);
        }
        if (obs.d[v].length == 1) {
          chartData[chartData.length - 1].points = {show : true};
        }
        yaxis++;
      }
      // record the action on google analytics
      pageTracker._trackEvent('chartView',title,'ok');
    }
    delete chartUrls[url];
    var hits = 0;
    for (var i in chartUrls) {
      hits++;
    }
    if (hits == 0) {
      graphLoadendUnmask();
    }
    Ext.getCmp('timeseriesPanel').fireEvent('resize',Ext.getCmp('timeseriesPanel'));
  }
}

function zoomOffset() {
  return 1;
}

function showToolTip(x,y,contents) {
  $('<div id="tooltip">' + contents + '</div>').css({
     position           : 'absolute'
    ,display            : 'none'
    ,top                : y + 10
    ,left               : x + 10
    ,border             : '1px solid #99BBE8'
    ,padding            : '2px'
    ,'background-color' : '#fff'
    ,opacity            : 0.80
  }).appendTo("body").fadeIn(200);
}

function setCustomStyles(rec) {
  var styles = [rec.get('settingsBaseStyle')];
  if (rec.get('settingsColorMap') != '') {
    styles.push(rec.get('settingsColorMap'));
    // record the action on google analytics
    pageTracker._trackEvent('setStyle','colorMap',rec.get('name'));
  }
  if (rec.get('settingsBarbLabel') != '') {
    styles.push(rec.get('settingsBarbLabel'));
    // record the action on google analytics
    pageTracker._trackEvent('setStyle','barbLabel',rec.get('name'));
  }
  if (rec.get('settingsStriding') != '') {
    styles.push(rec.get('settingsStriding'));
    // record the action on google analytics
    pageTracker._trackEvent('setStyle','striding',rec.get('name'));
  }
  if (rec.get('settingsTailMag') != '') {
    styles.push(rec.get('settingsTailMag'));
    // record the action on google analytics
    pageTracker._trackEvent('setStyle','tailMag',rec.get('name'));
  }
  if (rec.get('settingsMin') != '') {
    styles.push(rec.get('settingsMin'));
    // record the action on google analytics
    pageTracker._trackEvent('setStyle','minVal',rec.get('name'));
  }
  if (rec.get('settingsMax') != '') {
    styles.push(rec.get('settingsMax'));
    // record the action on google analytics
    pageTracker._trackEvent('setStyle','maxVal',rec.get('name'));
  }
  if (rec.get('settingsImageQuality') != '') {
    styles.push(rec.get('settingsImageQuality'));
    // record the action on google analytics
    pageTracker._trackEvent('setStyle','imageQuality',rec.get('name'));
  }
  map.getLayersByName(rec.get('name'))[0].mergeNewParams({STYLES : styles.join('-')});
}

function restoreDefaultStyles(l,items,id) {
  var rec = mainStore.getAt(mainStore.find('name',l));
  var settingsParam = rec.get('settingsParam').split(',');
  var settings = {};
  for (var i = 0; i < settingsParam.length; i++) {
    if (settingsParam[i] != '') {
      settings[settingsParam[i]] = guaranteeDefaultStyles[l].split('-')[i];
    }
  }
  for (var i = 0; i < items.length; i++) {
    var cmp = Ext.getCmp(items[i].id);
    if (items[i].id == 'opacity.' + id) {
      cmp.setValue(guaranteeDefaultOpacities[l]);
    }
    else if (items[i].id == 'imageType.' + id) {
      cmp.setValue('png');
      cmp.fireEvent('select',cmp,new imageQualityStore.recordType({value : 'png'}));
    }
    else if (items[i].id == 'palette.' + id) {
      cmp.setValue(settings['palette']);
      cmp.fireEvent('select',cmp,new palettesStore[l].recordType({name : settings['palette']}));
    }
    else if (items[i].id == 'layers.' + id) {
      cmp.setValue(defaultLayerLayers[l]);
      cmp.fireEvent('select',cmp,new layersStore[l].recordType({wmsName : defaultLayerLayers[l]}));
    }
    else if (items[i].id == 'baseStyle.' + id) {
      cmp.setValue(settings['baseStyle']);
      cmp.fireEvent('select',cmp,new baseStylesStore.recordType({value : settings['baseStyle']}));
    }
    else if (items[i].id == 'colorMap.' + id) {
      cmp.setValue(settings['colorMap']);
      cmp.fireEvent('select',cmp,new colorMapStore.recordType({name : settings['colorMap']}));
    }
    else if (items[i].id == 'striding.' + id) {
      cmp.setValue(stridingStore.find('param',settings['striding']));
      cmp.fireEvent('change',cmp,stridingStore.find('param',settings['striding']));
    }
    else if (items[i].id == 'tailMag.' + id) {
      cmp.setValue(settings['tailMag']);
      cmp.fireEvent('select',cmp,new tailMagStore.recordType({name : settings['tailMag']}));
    }
    else if (items[i].id == 'barbLabel.' + id) {
      cmp.setValue(settings['barbLabel']);
      cmp.fireEvent('select',cmp,new barbLabelStore.recordType({name : settings['barbLabel']}));
    }
    else if (items[i].id == 'minMax.' + id) {
      cmp.setValue(0,settings['min']);
      cmp.setValue(1,settings['max']);
      cmp.fireEvent('change',cmp);
    }
  }
}

function mapClick(xy,doWMS,chartIt) {
  if (ignoreMapClick) {
    return;
  }
  lastMapClick['xy'] = xy;
  lyrQueryPts.removeFeatures(lyrQueryPts.features);

  var modelQueryLyr = map.getLayersByName(Ext.getCmp('chartLayerCombo').getValue())[0];
  var modelQueryRec = mainStore.getAt(mainStore.find('name',modelQueryLyr.name));
  if ((modelQueryLyr && modelQueryLyr.visibility && modelQueryLyr.DEFAULT_PARAMS)) {
    var lonLat = map.getLonLatFromPixel(xy);
    var f = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(lonLat.lon,lonLat.lat));
    f.attributes.img = 'Delete-icon.png';
    lyrQueryPts.addFeatures(f);
  }

  var queryLyrs = [modelQueryLyr];
  if (doWMS && modelQueryLyr && modelQueryLyr.visibility && modelQueryLyr.DEFAULT_PARAMS) {
    // now that we've established our pivot point, see if there are any other active layers to drill into based on the category
    var displayName = mainStore.getAt(mainStore.find('name',modelQueryLyr.name)).get('displayName');
    var lyrType = displayName.substr(displayName.lastIndexOf(' ') + 1);
    Ext.getCmp('chartLayerCombo').getStore().each(function(rec) {
      if (rec.get('name') != modelQueryLyr.name && rec.get('category') == modelQueryRec.get('category')) {
        var lyr = map.getLayersByName(rec.get('name'))[0];
        if (lyr && lyr.visibility && lyr.DEFAULT_PARAMS) {
          queryLyrs.push(lyr);
        }
      }
    });
    return queryWMS(xy,queryLyrs,chartIt);
  }
}

function queryWMS(xy,a,chartIt) {
  lastMapClick['layer'] = a[0].name;
  if (chartIt) {
    graphLoadstartMask();
  }
  var targets = [];
  for (var i = 0; i < a.length; i++) {
    var mapTime;
    var legIdx = legendsStore.find('name',a[i].name);
    if (legIdx >= 0 && legendsStore.getAt(legIdx).get('timestamp') && String(legendsStore.getAt(legIdx).get('timestamp')).indexOf('alert') < 0) {
      mapTime = '&mapTime=' + (new Date(shortDateToDate(legendsStore.getAt(legIdx).get('timestamp')).getTime() - new Date().getTimezoneOffset() * 60000) / 1000);
    }
    var paramOrig = OpenLayers.Util.getParameters(a[i].getFullRequestString({}));
    var paramNew = {
       REQUEST       : 'GetFeatureInfo'
      ,EXCEPTIONS    : 'application/vnd.ogc.se_xml'
      ,BBOX          : map.getExtent().toBBOX()
      ,X             : xy.x
      ,Y             : xy.y
      ,INFO_FORMAT   : 'text/xml'
      ,FEATURE_COUNT : 1
      ,WIDTH         : map.size.w
      ,HEIGHT        : map.size.h
      ,QUERY_LAYERS  : forceQueryLayers(a[i].name,paramOrig['LAYERS'])
    };
    if (paramOrig['GFI_TIME'] == 'min/max') {
      dMin = new Date(dNow.getTime() - 12 * 60 * 60 * 1000);
      dMax = new Date(dNow.getTime() + 24 * 60 * 60 * 1000);
      paramNew['TIME'] =
          dMin.getUTCFullYear() + '-' + String.leftPad(dMin.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dMin.getUTCDate(),2,'0') + 'T' + String.leftPad(dMin.getUTCHours(),2,'0') + ':00Z'
        + '/'
        + dMax.getUTCFullYear() + '-' + String.leftPad(dMax.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dMax.getUTCDate(),2,'0') + 'T' + String.leftPad(dMin.getUTCHours(),2,'0') + ':00Z';
      paramNew['GFI_TIME'] = 'min/max';
    }
    targets.push({url : a[i].getFullRequestString(paramNew,'getFeatureInfo.php?' + a[i].url + '&tz=' + new Date().getTimezoneOffset() + mapTime),title : mainStore.getAt(mainStore.find('name',a[i].name)).get('displayName'),type : 'model'});
  }
  if (chartIt) {
    makeChart('model',targets);
  }
  return targets;
}

function zoomToBbox(bbox) {
  var p = bbox.split(',');
  map.zoomToExtent(new OpenLayers.Bounds(p[0],p[1],p[2],p[3]).transform(proj4326,map.getProjectionObject()));
}

function showHelp(fromButton) {
  if (helpUnavailable) {
    Ext.Msg.alert('Help',"We're sorry, but help is currently unavailable.");
    return;
  }

  if (!helpWin || helpWin.hidden) {
    helpWin = new Ext.Window({
       width      : 500
      ,autoHeight : true
      ,title      : 'How to use this site'
      ,bodyStyle  : 'background:white'
      ,constrainHeader : true
      ,modal           : true
      ,html       : '<div id="help"><?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/help.html')))?></div>'
      ,closeAction : 'hide'
      ,listeners   : {hide : function() {
        // don't show help next visit
        cp.set('hideAssetsHelpOnStartup',true);
      }}
    }).show();
  }
}

function forceQueryLayers(name,layer) {
  if (layersStore[name]) {
    return layersStore[name].getAt(layersStore[name].find('wmsName',layer)).get('queryName');
  }
  return layer;
}

function toEnglish(v) {
  if (String(v.src).indexOf('Celcius') >= 0) {
    if (v.typ == 'title') {
      return v.val.replace('Celcius','Fahrenheit');
    }
    else {
      return v.val * 9/5 + 32;
    }
  }
  else if (String(v.src).indexOf('Meters') >= 0) {
    if (v.typ == 'title') {
      return v.val.replace('Meters','Feet');
    }
    else {
      return v.val * 3.281;
    }
  }
  return v.val;
}

function printSaveMap(printSave) {
  if (!(new OpenLayers.Bounds(-180,-90,180,90).containsBounds(map.getExtent().transform(map.getProjectionObject(),proj4326)))) {
    Ext.Msg.alert('Print request error','No portion of your map may be outside of real world boundaries.  Please either zoom in or resize your map to hide any "blank" space and try again.');
    return;
  }

  if (map.baseLayer.name != 'Open StreetMap') {
    Ext.Msg.alert('Basemap error','Due to copyright limitations, only the ESRI Ocean baselayer may be printed or saved.  Please change your basemap selection and try again.');
    return;
  }
  var tempBase = new OpenLayers.Layer.WMS(
     'Blue Marble (EPSG:4326)'
    ,'http://asascience.mine.nu:8080/geoserver/wms?'
    ,{
      layers : 'base:BlueMarble'
    }
    ,{
       isBaseLayer   : false
      ,visibility    : false
      ,wrapDateLine  : true
    }
  );
  map.addLayer(tempBase);
  var layers   = [];
  var features = {};
  var tracks   = {};
  for (var i = 0; i < map.layers.length; i++) {
    var lyr = map.layers[i];
    var legIdx = legendsStore.find('name',lyr.name);
    var assIdx = assetsStore.find('name',lyr.name); 
    var gliIdx = glidersStore.find('name',lyr.name);
    if (legIdx >= 0) {
      if (lyr.DEFAULT_PARAMS) {
        layers.push({url : lyr.getFullRequestString({width : map.div.style.width.replace('px',''),height : map.div.style.height.replace('px',''),bbox : map.getExtent()}),x : 0,y : 0})
      }
      else if (lyr.grid) {
        for (tilerow in lyr.grid) {
          for (tilei in lyr.grid[tilerow]) {
            var tile = lyr.grid[tilerow][tilei];
            if (tile.bounds) {
              var url      = lyr.getURL(tile.bounds);
              var position = tile.position;
              layers.push({url : url,x : position.x,y : position.y});
            }
          }
        }
      }
      else if (assIdx >= 0 || gliIdx >= 0) {
        features[lyr.name] = [];
        tracks[lyr.name]   = [];
        for (var j = 0; j < lyr.features.length; j++) {
          var verts = lyr.features[j].geometry.getVertices();
          if (verts.length == 1) {
            var cen = verts[0].getCentroid();
            var pix = map.getPixelFromLonLat(new OpenLayers.LonLat(cen.x,cen.y));
            features[lyr.name].push([pix.x,pix.y,Math.round(lyr.features[j].attributes.rotation)]);
          }
          else {
            var a = [];
            for (var k = 0; k < verts.length; k++) {
              var cen = verts[k].getCentroid();
              var pix = map.getPixelFromLonLat(new OpenLayers.LonLat(cen.x,cen.y));
              a.push([pix.x,pix.y]);
            }
            tracks[lyr.name].push({
               stroke : lyr.features[j].attributes.strokeDashstyle
              ,color  : lyr.features[j].attributes.strokeColor
              ,data   : a
            });
          }
        }
      }
    }
  }

  var legends  = [];
  var icons    = [];
  legendsStore.each(function(rec) {
    var mainIdx = mainStore.find('name',rec.get('name'));
    icons.push('<img class="layerIcon" src="<?php echo substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],'/')+1)?>img/' + rec.get('name') + '.drawn.png">');
    var p = ['<b>' + rec.get('displayName') + '</b>'];
    if (rec.get('timestamp')) {
      p.push(rec.get('timestamp'));
    }
    if (mainStore.getAt(mainIdx).get('legend') != '') {
      p.push('<img src="<?php echo substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],'/')+1)?>getLegend.php?' + mainStore.getAt(mainIdx).get('legend') + '">');
    }
    p.push('&nbsp;');
    legends.push(p.join('<br>'));
  });

  var basemap = [];
  var baseLayer = esriOcean;
  for (tilerow in baseLayer.grid) {
    for (tilei in baseLayer.grid[tilerow]) {
      var tile = baseLayer.grid[tilerow][tilei];
      if (tile.bounds) {
        var url      = baseLayer.getURL(tile.bounds);
        var position = tile.position;
        basemap.push({url : url,x : position.x,y : position.y});
      }
    }
  }

  Ext.MessageBox.show({
     title        : 'Please wait'
    ,msg          : 'Generating template...'
    ,width        : 300
    ,wait         : true
    ,waitConfig   : {interval : 200}
  });
  checkPrintTimer = setTimeout('printErrorAlert()',20000);

  OpenLayers.Request.issue({
     method  : 'POST'
    ,url     : 'print.php'
    ,headers : {'Content-Type' : 'application/x-www-form-urlencoded'}
    ,data    : OpenLayers.Util.getParameterString({
       lyr : Ext.encode(layers)
      ,leg : Ext.encode(legends)
      ,ico : Ext.encode(icons)
      ,ftr : Ext.encode(features)
      ,trk : Ext.encode(tracks)
      ,bm  : Ext.encode(basemap)
      ,out : printSave
      ,w   : map.div.style.width.replace('px','')
      ,h   : map.div.style.height.replace('px','')
    })
    ,callback : OpenLayers.Function.bind(printSaveCallback,null,printSave)
  });

  map.removeLayer(tempBase);

  function printSaveCallback(printSave,r) {
    clearTimeout(checkPrintTimer);
    if (r.responseText == '') {
      printErrorAlert();
      return;
    }
    if (printSave == 'print') {
      Ext.Msg.alert('Print','A printer-friendly page is ready.  Click <a target=_blank href="' + r.responseText + '">here</a> to open it.');
    }
    else {
      Ext.Msg.alert('Save','A ZIP file containting the map and legend is ready.  ' + bathyAlert + 'Click <a target=_blank href="' + r.responseText + '">here</a> to open it.');
    }
  }
}

function printErrorAlert() {
  Ext.MessageBox.hide();
  Ext.Msg.alert('Print/save error',"We're sorry, but a print/save error has occured.  Please try again.");
}

function getDateRange() {
  if (config == 'gliders' && Ext.getCmp('glidersYearsComboBox') && Ext.getCmp('glidersYearsComboBox').getStore().getCount() > 0) {
    var min = new Date(Ext.getCmp('glidersYearsComboBox').getValue(),0,0,0,0,0,0);
    var max = new Date(Ext.getCmp('glidersYearsComboBox').getValue() * 1 + 1,0,0,0,0,0,0);
    if (new RegExp(/^Current/).test(Ext.getCmp('glidersYearsComboBox').getValue())) {
      min = new Date(new Date().getTime() - 1000 * 3600 * 24 * 5);
      max = new Date();
    }
    var t0 = min.getUTCFullYear() + '-' + String.leftPad(min.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(min.getUTCDate(),2,'0') + ' ' + String.leftPad(min.getUTCHours(),2,'0') + ':00';
    var t1 = max.getUTCFullYear() + '-' + String.leftPad(max.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(max.getUTCDate(),2,'0') + ' ' + String.leftPad(max.getUTCHours(),2,'0') + ':00';
    return '&t0=' + t0 + '&t1=' + t1;
  }
  else {
    return '';
  }
}

function getFilter() {
  if (config == 'gliders' && glidersMetadataStore.getCount() > 0) {
    var p = [];
    var sel = Ext.getCmp('glidersProvidersGridPanel').getSelectionModel().getSelections();
    for (var i = 0; i < sel.length; i++) {
      p.push(sel[i].get('name'));
    }
    return '&filterProvider=' + escape('&provider[]=' + p.join('&provider[]='));
  }
  else {
    return '';
  }
}

function syncGliders(force) {
  syncObs({name : 'Sea gliders'},force);
  syncObs({name : 'Slocum gliders'},force);
  syncObs({name : 'Spray gliders'},force);
  syncObs({name : 'Unknown gliders'},force);
}

function mkTbar() {
  return {tbar : []};
}

function lineColor2VectorColor(l) {
  for (var i = 0; i < lineColors.length; i++) {
    if (lineColors[i][0] == l) {
      return lineColors[i][1];
    }
  }
  return lineColors[0][1];
}

function checkRealtimeAlert() {
  if (hideRealtimeAlert) {
    return;
  }
  var vizCount = 0;
  for (var i = 0; i < map.layers.length; i++) {
    // WMS layers only
    if (map.layers[i].DEFAULT_PARAMS && map.layers[i].visibility) {
      vizCount++;
    }
  }
  if (Ext.getCmp('glidersYearsComboBox').getValue() != Ext.getCmp('glidersYearsComboBox').getStore().getAt(0).get('year') && vizCount > 0) {
    document.getElementById('notRealtimeAlert').style.visibility = 'visible';
  }
  else {
    document.getElementById('notRealtimeAlert').style.visibility = 'hidden';
  }
}

function syncLayersToBbox(l) {
  if (!Ext.getCmp('restrictLayersToBbox') || !Ext.getCmp('restrictLayersToBbox').checked) {
    return;
  }
  for (var type in layersToSyncBbox) {
    if ((typeof l == 'string' && l == type) || (typeof l != 'string')) {
      var gp  = Ext.getCmp(type + 'GridPanel');
      var sto = gp.getStore();
      var sm  = gp.getSelectionModel();
      if (needToInitGridPanel[type]) {
        if (gp.isVisible() && sto.getCount() == 0) {
          gp.hide();
        }
        needToInitGridPanel[type] = false;
      }
      sm.suspendEvents();
      sto.removeAll();
      mainStore.each(function(rec) {
        if (rec.get('type') == type) {
          var bbox = String(rec.get('bbox')).split(',');
          if (
            map.getExtent().transform(map.getProjectionObject(),proj4326).intersectsBounds(new OpenLayers.Bounds(bbox[0],bbox[1],bbox[2],bbox[3]))
            || new OpenLayers.Bounds(bbox[0],bbox[1],bbox[2],bbox[3]).containsBounds(map.getExtent().transform(map.getProjectionObject(),proj4326))
            || !Ext.getCmp('restrictLayersToBbox').checked
          ) {
            sto.add(rec);
          }
          else if (map.getLayersByName(rec.get('name'))[0].visibility) {
            map.getLayersByName(rec.get('name'))[0].setVisibility(false);
          }
        }
      });
      var j = 0;
      sto.each(function(rec) {
        if (map.getLayersByName(rec.get('name'))[0].visibility) {
          sm.selectRow(j,true);
        }
        j++;
      });
      sm.resumeEvents();
    }
  }
}

function getColorScaleRange() {
  var d = new Date();
  d.setHours(0);
  d.setMinutes(0);
  d.setSeconds(0);
  d.setMilliseconds(0);

  d.setMonth(0);
  d.setDate(4);
  if (dNow < d) {
    return '2,29';
  }

  d.setMonth(1);
  d.setDate(2);
  if (dNow < d) {
    return '2,29';
  }

  d.setMonth(2);
  d.setDate(8);
  if (dNow < d) {
    return '1,29';
  }

  d.setMonth(3);
  d.setDate(16);
  if (dNow < d) {
    return '2,29';
  }

  d.setMonth(4);
  d.setDate(12);
  if (dNow < d) {
    return '3,31';
  }

  d.setMonth(5);
  d.setDate(11);
  if (dNow < d) {
    return '4,32';
  }

  d.setMonth(6);
  d.setDate(12);
  if (dNow < d) {
    return '5,33';
  }

  d.setMonth(7);
  d.setDate(10);
  if (dNow < d) {
    return '7,32';
  }

  d.setMonth(8);
  d.setDate(1);
  if (dNow < d) {
    return '7,32';
  }

  d.setMonth(9);
  d.setDate(13);
  if (dNow < d) {
    return '6,31';
  }

  d.setMonth(10);
  d.setDate(9);
  if (dNow < d) {
    return '4,31';
  }

  return '3,29';
}

function setMapTime() {
  Ext.getCmp('mapTime').setText(dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + ' ' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00 UTC');
  var dStr = dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + 'T' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00';
  for (var i = 0; i < map.layers.length; i++) {
    // WMS layers only
    if (map.layers[i].DEFAULT_PARAMS) {
      map.layers[i].mergeNewParams({TIME : dStr});
      if (OpenLayers.Util.getParameters(map.layers[i].getFullRequestString({}))['COLORSCALERANGE']) {
        map.layers[i].mergeNewParams({COLORSCALERANGE : getColorScaleRange()});
      }
      // record the action on google analytics
      if (mainStore.find('name',map.layers[i].name) >= 0) {
        pageTracker._trackEvent('timeSlider',mainStore.getAt(mainStore.find('name',map.layers[i].name)).get('displayName'));
      }
    }
  }

  if (Ext.getCmp('datePicker')) {
    var dp = Ext.getCmp('datePicker');
    dp.suspendEvents();
    dp.setValue(new Date(dNow.getTime() + dNow.getTimezoneOffset() * 60000));
    dp.resumeEvents();
  }
}

function refreshCurrentTime() {
  var now = new Date();
  time = '<table><tr><td style="text-align : center"><b>Current date & time</b><br>' + now.getUTCFullYear() + '-' + String.leftPad(now.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(now.getUTCDate(),2,'0') + ' ' + String.leftPad(now.getUTCHours(),2,'0') + ':' + String.leftPad(now.getUTCMinutes(),2,'0') + ':' + String.leftPad(now.getUTCSeconds(),2,'0') + ' UTC</td></tr></table>';
  var el = Ext.getCmp('currentTime');
  if (el) {
    el.setText(time);
    setTimeout("refreshCurrentTime()", 1000);
  }
}

function layerLoadstartMask() {
  Ext.getCmp('legendsGridPanel').getEl().mask('<table><tr><td>Updating map...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>','mask');
}

function layerLoadendUnmask() {
  var stillLoading = 0;
  legendsStore.each(function(rec) {
    stillLoading += (rec.get('status') != 'drawn' ? 1 : 0);
  });
  if (stillLoading == 0) {
    Ext.getCmp('legendsGridPanel').getEl().unmask();
  }
}

function graphLoadstartMask() {
  Ext.getCmp('timeseriesPanel').getEl().mask('<table><tr><td>Updating graph...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>','mask');
}

function graphLoadendUnmask() {
  Ext.getCmp('timeseriesPanel').getEl().unmask();
}
