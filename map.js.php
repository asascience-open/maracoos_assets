var cp;
var map;
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
var imageTypesStore;
var mainStore;
var assetsStore;
var modelsStore;
var observationsStore;
var marineStore;
var legendsStore;
var spot;
var spotTooltip;
var obsMinZoom = {
   'NDBC'        : 1
  ,'CO-OPS'      : 2
  ,'USGS'        : 5
  ,'NERRS'       : 0
  ,'Weatherflow' : 3
  ,'HF Radar'    : 0
  ,'Satellites'  : 0
  ,'Gliders'     : 0
};
var obsBbox = {};
var obsZoom = {};
var obsBigExtentScale = 2;  // bigger this # is, the more obs it will cache
var popupObs;
var mouseoverObs;
var popupCtl;
var hiliteCtl;
var lyrQueryPts;
var chartData;
var chartLayerStore;
var esriOcean;     // special case for this layer
var navCharts;     // special case for this layer
var openStreetMap; // special case for this layer
var dNow;
var numTics = 4;           // must be even!
var ticIntervalHours = 12;
var availableTimes = [];
var lastMapClick = {
   layer : ''
  ,e     : ''
};
var timeControlsHeight = 42;
var checkPrintTimer;

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

  makeAvailableTimes();

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
     height : 48
    ,border : false
    ,html   : '<table class="smallFont" width="100%"><tr><td align=center><a target=_blank href="http://maracoos.org/"><img title="Go to the MARACOOS home page" src="img/maracoos.jpg"></a></td><td align=center><a target=_blank href="http://www.ioos.gov/"><img title="Go to the IOOS home page" src="img/ioos.gif"></a></td></tr></table>'
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
    ]
    ,data  : [
      [
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
        ,'-78,35.5,-62,44'
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
        ,'-78,35.5,-62,44'
        ,''
        ,''
      ]
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
        ,'-78,35.5,-62,44'
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
        ,'-78,35.5,-62,44'
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
        ,'-78,35.5,-62,44'
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
        ,'legends/Gliders.png'
        ,''
        ,'-78,35.5,-62,44'
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
        ,'-76.5,39,-73,41'
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
        ,'-78,35.5,-62,44'
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
        ,'legends/ROMS.png'
        ,''
        ,'-79,35.5,-74,40'
        ,'true'
        ,''
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
        ,'legends/STPS.png'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
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
        ,'legends/Stevens NYHOPS.png'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
      ]
      ,[
         'model'
        ,'UMass'
        ,'UMass currents'
        ,'off'
        ,defaultLayers['UMass'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/UMass.html')))?>'
        ,'baseStyle,colorMap,barbLabel,striding,tailMag,min,max'
        ,typeof defaultOpacities['UMass'] != 'undefined' && defaultOpacities['UMass'] != '' ? defaultOpacities['UMass'] : 100
        ,defaultImageTypes['UMass']
        ,''
        ,defaultStyles['UMass'].split('-')[0]
        ,defaultStyles['UMass'].split('-')[1]
        ,defaultStyles['UMass'].split('-')[3]
        ,defaultStyles['UMass'].split('-')[2]
        ,defaultStyles['UMass'].split('-')[4]
        ,defaultStyles['UMass'].split('-')[5]
        ,defaultStyles['UMass'].split('-')[6]
        ,'0-6'
        ,''
        ,'legends/UMass.png'
        ,''
        ,'-72,40.5,-69,43.5'
        ,'true'
        ,''
      ]
      ,[
         'model'
        ,'ROMS ESPRESSO'
        ,'ROMS ESPRESSO'
        ,'off'
        ,defaultLayers['ROMS ESPRESSO'] ? 'on' : 'off'
        ,''
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/ROMS ESPRESSO.html')))?>'
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
        ,'legends/NCOM currents.png'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
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
        ,'legends/HYCOM currents.png'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
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
        ,'legends/NAM winds.png'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
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
        ,'legends/WWIII waves.png'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
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
        ,'legends/NCOM SST.png'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,''
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
        ,'legends/HF radar currents.png'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
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
        ,'http://tds.maracoos.org/ncWMS/wms?REQUEST=GetLegendGraphic&LAYER=' + (typeof defaultLayerLayers['Satellite water temperature'] != 'undefined' && defaultLayerLayers['Satellite water temperature'] != '' ? defaultLayerLayers['Satellite water temperature'] : 'sst-seven/mcsst') + '&PALETTE=' + defaultStyles['Satellite water temperature'].split('/')[1] + '&TIME=' + dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + 'T' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00' + '&GetMetadata'
        ,''
        ,'-78,35.5,-62,44'
        ,'true'
        ,typeof defaultLayerLayers['Satellite water temperature'] != 'undefined' && defaultLayerLayers['Satellite water temperature'] != '' ? defaultLayerLayers['Satellite water temperature'] : 'sst-seven/mcsst'
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
        ,'false'
        ,'-78,35.5,-62,44'
        ,'false'
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
        ,'false'
        ,'-78,35.5,-62,44'
        ,'false'
        ,''
      ]
/*
      ,[
         'marine'
        ,'WWA'
        ,'NWS hazards'
        ,'off'
        ,defaultLayers['WWA'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/WWA.html')))?>'
        ,''
        ,typeof defaultOpacities['WWA'] != 'undefined' && defaultOpacities['WWA'] != '' ? defaultOpacities['WWA'] : 100
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
      ]
      ,[
         'marine'
        ,'Zones'
        ,'Near and offshore zones'
        ,'off'
        ,defaultLayers['Zones'] ? 'on' : 'off'
        ,'off'
        ,'<?php echo str_replace("'","\\'",str_replace("\n",' ',file_get_contents('info/Zones.html')))?>'
        ,''
        ,typeof defaultOpacities['Zones'] != 'undefined' && defaultOpacities['Zones'] != '' ? defaultOpacities['Zones'] : 100
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
      ]
*/
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
        ,'false'
        ,'-135,0,-50,50'
        ,'false'
        ,''
      ]
    ]
  });

  var i = 0;
  mainStore.each(function(rec) {
    rec.set('rank',i++);
    rec.commit();
  });

  assetsStore = new Ext.data.ArrayStore({
    fields : [
       'name'
      ,'displayName'
      ,'info'
      ,'status'
      ,'settings'
      ,'infoBlurb'
      ,'settingsParam'
      ,'settingsOpacity'
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
    ]
  });
  mainStore.each(function(rec) {
    if (rec.get('type') == 'asset') {
      assetsStore.add(rec);
    }
  });

  modelsStore = new Ext.data.ArrayStore({
    fields : [
       'name'
      ,'displayName'
      ,'info'
      ,'status'
      ,'settings'
      ,'infoBlurb'
      ,'settingsParam'
      ,'settingsOpacity'
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
    ]
  });
  mainStore.each(function(rec) {
    if (rec.get('type') == 'model') {
      modelsStore.add(rec);
    }
  });

  observationsStore = new Ext.data.ArrayStore({
    fields : [
       'name'
      ,'displayName'
      ,'info'
      ,'status'
      ,'settings'
      ,'infoBlurb'
      ,'settingsParam'
      ,'settingsOpacity'
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
    ]
  });
  mainStore.each(function(rec) {
    if (rec.get('type') == 'observation') {
      observationsStore.add(rec);
    }
  });

  marineStore = new Ext.data.ArrayStore({
    fields : [
       'name'
      ,'displayName'
      ,'info'
      ,'status'
      ,'settings'
      ,'infoBlurb'
      ,'settingsParam'
      ,'settingsOpacity'
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
    ]
  });
  mainStore.each(function(rec) {
    if (rec.get('type') == 'marine') {
      marineStore.add(rec);
    }
  });

  legendsStore = new Ext.data.ArrayStore({
    fields : [
       'name'
      ,'displayName'
      ,'status'
      ,'rank'
      ,'fetchTime'
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

  baseStylesStore = new Ext.data.ArrayStore({
    fields : [
      'name'
     ,'value'
     ,'type'
    ]
    ,data : [
       ['Ramp','CURRENTS_RAMP','CURRENTS']
      ,['Black','CURRENTS_STATIC_BLACK','CURRENTS']
      // ,['Winds','WINDS_VERY_SPARSE','WINDS']
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

  imageTypesStore = new Ext.data.ArrayStore({
    fields : [
      'name'
     ,'value'
    ]
    ,data : [
       ['low','gif']
      ,['high','png']
    ]
  });

  var assetsGridPanel = new Ext.grid.GridPanel({
     id               : 'assetsGridPanel'
    ,height           : assetsStore.getCount() * 21.1 + 26 + 11 + 25
    ,title            : 'Assets'
    ,collapsible      : true
    ,store            : assetsStore
    ,border           : false
    ,columns          : [
       {id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 45,css : 'vertical-align:middle'}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderBboxButton    ,width : 20}
      ,{id : 'settings'   ,dataIndex : 'settings'   ,renderer : renderSettingsButton,width : 25,align : 'right'}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,tbar             : [
      {
         text    : 'Turn all assets off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          assetsStore.each(function(rec) {
            var lyr = map.getLayersByName(rec.get('name'))[0];
            rec.set('status','off');
            rec.commit();
            if (lyr.visibility) {
              lyr.setVisibility(false);
            }
          });
        }
      }
      ,'->'
      ,{
         text    : 'Turn all assets on'
        ,icon    : 'img/add.png'
        ,handler : function() {
          assetsStore.each(function(rec) {
            var lyr = map.getLayersByName(rec.get('name'))[0];
            rec.set('status','on');
            rec.commit();
            if (!lyr.visibility) {
              lyr.setVisibility(true);
            }
          });
        }
      }
    ]
  });

  var modelsGridPanel = new Ext.grid.GridPanel({
     id               : 'modelsGridPanel'
    ,height           : modelsStore.getCount() * 21.1 + 26 + 11 + 25
    ,title            : 'Models'
    ,collapsible      : true
    ,store            : modelsStore
    ,border           : false
    ,columns          : [
       {id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 45,css : 'vertical-align:middle'}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderBboxButton    ,width : 20}
      ,{id : 'settings'   ,dataIndex : 'settings'   ,renderer : renderSettingsButton,width : 25,align : 'right'}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,tbar             : [
      {
         text    : 'Turn all models off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          modelsStore.each(function(rec) {
            var lyr = map.getLayersByName(rec.get('name'))[0];
            rec.set('status','off');
            rec.commit();
            if (lyr.visibility) {
              lyr.setVisibility(false);
            }
          });
        }
      }
    ]
  });

  var observationsGridPanel = new Ext.grid.GridPanel({
     id               : 'observationsGridPanel'
    ,height           : observationsStore.getCount() * 21.1 + 26 + 11 + 25
    ,title            : 'Observations'
    ,collapsible      : true
    ,store            : observationsStore
    ,border           : false
    ,columns          : [
       {id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 45,css : 'vertical-align:middle'}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderBboxButton    ,width : 20}
      ,{id : 'settings'   ,dataIndex : 'settings'   ,renderer : renderSettingsButton,width : 25,align : 'right'}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,tbar             : [
      {
         text    : 'Turn all observations off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          observationsStore.each(function(rec) {
            var lyr = map.getLayersByName(rec.get('name'))[0];
            rec.set('status','off');
            rec.commit();
            if (lyr.visibility) {
              lyr.setVisibility(false);
            }
          });
        }
      }
    ]
  });

  var marineGridPanel = new Ext.grid.GridPanel({
     id               : 'marineGridPanel'
    ,height           : marineStore.getCount() * 21.1 + 26 + 11 + 25
    ,title            : 'Marine'
    ,collapsible      : true
    ,store            : marineStore
    ,border           : false
    ,columns          : [
       {id : 'status'     ,dataIndex : 'status'     ,renderer : renderLayerButton   ,width : 45,css : 'vertical-align:middle'}
      ,{id : 'displayName',dataIndex : 'displayName',renderer : renderLayerInfoLink ,width : 167}
      ,{id : 'bbox'       ,dataIndex : 'bbox'       ,renderer : renderBboxButton    ,width : 20}
      ,{id : 'settings'   ,dataIndex : 'settings'   ,renderer : renderSettingsButton,width : 25,align : 'right'}
    ]
    ,hideHeaders      : true
    ,disableSelection : true
    ,tbar             : [
      {
         text    : 'Turn all marine off'
        ,icon    : 'img/delete.png'
        ,handler : function() {
          marineStore.each(function(rec) {
            var lyr = map.getLayersByName(rec.get('name'))[0];
            rec.set('status','off');
            rec.commit();
            if (lyr.visibility) {
              lyr.setVisibility(false);
            }
          });
        }
      }
    ]
  });

  var legendsGridPanel = new Ext.grid.GridPanel({
     id               : 'legendsGridPanel'
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
           {id : 'status',dataIndex : 'status',renderer : renderLayerStatus,width : 32}
          ,{id : 'legend',dataIndex : 'name'  ,renderer : renderLegend     ,width : w - 4 - 27}
        ]);
      });
    }}
  });

  new Ext.Viewport({
     layout : 'border'
    ,items  : [
      new Ext.Panel({
         region      : 'west'
        ,width       : 278
        ,title       : 'MARACOOS Assets Manager'
        ,collapsible : true
        ,autoScroll  : true
        ,items       : [
           introPanel
          ,assetsGridPanel
          ,modelsGridPanel
          ,observationsGridPanel
          // ,marineGridPanel
        ]
      })
      ,new Ext.Panel({
         region    : 'center'
        ,title     : 'MARACOOS Assets Explorer'
        ,layout    : 'border'
        ,items     : [
          {
             html      : '<div id="map"></div>'
            ,region    : 'center'
            ,border    : false
            ,tbar      : [
              {
                 icon    : 'img/printer.png'
                ,text    : 'Print'
                ,tooltip : 'Print active map'
                ,handler : function() {
                  printSaveMap('print');
                }
              }
              ,{
                 icon    : 'img/disk.png'
                ,text    : 'Save'
                ,tooltip : 'Save active map'
                ,handler : function() {
                  printSaveMap('save');
                }
              }
              ,{
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
                    ,'navC'    : navCharts.visibility ? navCharts.opacity * 100 : ''
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
                        else {
                          p['styls'].push('');
                          p['opcty'].push('');
                          p['lyrLyrs'].push('');
                          p['imgTyps'].push('');
                        }
                      }
                    }
                  }
                  p['lyrs'] = p['lyrs'].join(',');
                  p['bathyC'] = map.getLayersByName('Bathymetry contours')[0].visibility;
                  var u = [];
                  for (var i in p) {
                    u.push(i + '=' + p[i]);
                  }
                  var url = "<?php echo 'http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/'))?>?" + u.join('&');
                  Ext.Msg.alert('Bookmark','The following link will launch the MARACOOS Assets Explorer with your current confiuration and may be used as a bookmark. <a target=_blank href="' + url.replace(/ /g,'%20') + '">Link to my MARACOOS Assets Explorer</a>');
                }
              }
              ,{
                 icon    : 'img/comments.png'
                ,text    : 'Feedback'
                ,tooltip : 'Provide feedback'
                ,handler : function() {
                  Ext.Msg.alert('Feedback','We are very interested in your feedback.  Please send us an email at this address, <a href="mailto:maracoosinfo@udel.edu">maracoosinfo@udel.edu</a>.');
                }
              }
              ,'-'
              ,{
                 icon    : 'img/help-icon.png'
                ,text    : 'Help'
                ,tooltip : 'View help tutorial'
                ,handler : function() {
                  showHelp(true);
                }
              }
              ,'->'
              ,'Show bathymetry contours?'
              ,' '
              ,new Ext.form.Checkbox({
                 checked   : typeof defaultLayers['bathyContours'] != 'undefined'
                ,listeners : {check : function(cbox,checked) {
                  map.getLayersByName('Bathymetry contours')[0].setVisibility(checked);
                }}
              })
              ,' '
              ,new Ext.form.ComboBox({
                 store          : new Ext.data.ArrayStore({
                   fields : ['name']
                  ,data   : [['ESRI Ocean'],['Google Satellite'],['Google Terrain']]
                })
                ,valueField     : 'name'
                ,displayField   : 'name'
                ,editable       : false
                ,triggerAction  : 'all'
                ,mode           : 'local'
                ,width          : 110
                ,value          : defaultBasemap
                ,forceSelection : true
                ,listeners      : {select : function(comboBox,rec) {
                  var lyr = map.getLayersByName(rec.get('name'))[0];
                  if (lyr.isBaseLayer) {
                    lyr.setOpacity(1);
                    map.setBaseLayer(lyr);
                    lyr.redraw();
                  }

                  // special case foresri ocean
                  esriOcean.setVisibility(rec.get('name') == 'ESRI Ocean');
                  esriOcean.setOpacity(1);
                  // special case for nav charts
                  navCharts.setVisibility(rec.get('name') == 'Navigational Charts');
                  navCharts.setOpacity(1);
                }}
              })
            ]
            ,bbar      : {
               xtype    : 'container'
              ,height   : timeControlsHeight
              ,defaults : {border : false,bodyStyle : 'background:transparent'}
              ,cls      : 'x-toolbar'
              ,id       : 'timeSliderContainer'
              ,items : [
                 new Ext.Panel({
                   width       : 100
                  ,height      : 15
                  ,id          : 'sliderTics'
                  ,html        : '<table id="sliderTicsTable"><tbody></tbody></table>'
                })
                ,new Ext.Panel({
                   layout       : 'column'
                  ,defaults     : {border : false,bodyStyle : 'background:transparent'}
                  ,items        : [
                    {html : '&nbsp;',width : 5}
                    ,new Ext.Button({
                       icon : 'img/control_rewind_blue.png'
                      ,handler : function() {
                        var slider = Ext.getCmp('timeSlider');
                        slider.setValue(slider.getValue() - 1);
                      }
                    })
                    ,{html : '&nbsp;&nbsp;',width : 5}
                    ,new Ext.Slider({
                       increment   : 1
                      ,minValue    : 0
                      ,maxValue    : availableTimes.length - 1
                      ,width       : 100
                      ,id          : 'timeSlider'
                      ,plugins     : new Ext.slider.Tip({getText : function(thumb){
                        return shortDateString(availableTimes[thumb.value]);
                      }})
                      ,listeners   : {change : function(slider,val) {
                        var dStr = availableTimes[val].getUTCFullYear() + '-' + String.leftPad(availableTimes[val].getUTCMonth() + 1,2,'0') + '-' + String.leftPad(availableTimes[val].getUTCDate(),2,'0') + 'T' + String.leftPad(availableTimes[val].getUTCHours(),2,'0') + ':00';
                        for (var i = 0; i < map.layers.length; i++) {
                          // WMS layers only
                          if (map.layers[i].DEFAULT_PARAMS) {
                            map.layers[i].mergeNewParams({TIME : dStr});
                            // record the action on google analytics
                            if (mainStore.find('name',map.layers[i].name) >= 0) {
                              pageTracker._trackEvent('timeSlider',mainStore.getAt(mainStore.find('name',map.layers[i].name)).get('displayName'));
                            }
                          }
                        }
                      }}
                    })
                    ,{html : '&nbsp;&nbsp;',width : 5}
                    ,new Ext.Button({
                       icon    : 'img/control_fastforward_blue.png'
                      ,handler : function() {
                        var slider = Ext.getCmp('timeSlider');
                        slider.setValue(slider.getValue() + 1);
                      }
                    })
                    ,{html : '&nbsp;',width : 5}
                  ]
                })
              ]
            }
            ,listeners : {
              afterrender : function(panel) {
                initMap();
              }
              ,bodyresize : function(p,w,h) {
                var el = document.getElementById('map');
                if (el) {
                  el.style.width = w;
                  el.style.height = h;
                  map.updateSize();
                  Ext.getCmp('timeSlider').setWidth(w - 75);
                  Ext.getCmp('sliderTics').setWidth(w - 48);
                  // document.getElementById('sliderTicsTable').style.width = w - 90;
                }
              }
            }
          }
          ,new Ext.Panel({
             region      : 'south'
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
                 text    : 'Clear query'
                ,icon    : 'img/trash-icon.png'
                ,id      : 'graphAction'
                ,handler : function() {
                  if (this.icon == 'img/blueSpinner.gif') {
                    return;
                  }
                  lyrQueryPts.removeFeatures(lyrQueryPts.features);
                  document.getElementById('tsResults').innerHTML = '<table class="obsPopup timeSeries"><tr><td><br/>Click on the map to view a time-series graph of Model or Observation output. Only one layer may be active at a time.</td></tr></table>';
                  chartData = [];
                  $('#tooltip').remove();
                  Ext.getCmp('chartLayerCombo').show();
                  Ext.getCmp('activeLabel').setText('Active model query layer: ');
                }
              }
            ]
            ,border      : false
            ,height      : 175
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
                  if (chartData && chartData.length > 0) {
                    ts.innerHTML    = '';
                    $.plot(
                       $('#tsResults')
                      ,chartData
                      ,{
                         xaxis     : {mode  : "time"}
                        ,crosshair : {mode  : 'x'   }
                        ,grid      : {backgroundColor : {colors : ['#fff','#eee']},borderWidth : 1,borderColor : '#99BBE8',hoverable : true}
                        ,zoom      : {interactive : true}
                        ,pan       : {interactive : true}
                      }
                    );
                  }
                  else {
                    ts.innerHTML = '<table class="obsPopup timeSeries"><tr><td><br/>Click on the map to view a time-series graph of Model or Observation output. Only one layer may be active at a time.</td></tr></table>';
                  }
                  Ext.getCmp('graphAction').setText('Clear query');
                  Ext.getCmp('graphAction').setIcon('img/trash-icon.png');
                });
              }
            }
          })
        ]
      })
      ,legendsGridPanel
    ]
  });
  if (!cp.get('hideAssetsHelpOnStartup')) {
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
    ,{sphericalMercator: true,visibility : defaultBasemap == 'ESRI Ocean',isBaseLayer : false,opacity : defaultOpacities['ESRI Ocean'] / 100,wrapDateLine : true,attribution : "GEBCO, NOAA, National Geographic, AND data by <a href='http://www.arcgis.com/home/item.html?id=6348e67824504fc9a62976434bf0d8d5'>ESRI</a>"}
  );

  navCharts = new OpenLayers.Layer.WMS(
     'Navigational Charts'
    ,'http://egisws02.nos.noaa.gov/ArcGIS/services/RNC/NOAA_RNC/ImageServer/WMSServer?'
    ,{
       layers      : '1'
      ,format      : 'image/' + defaultImageTypes['Navigational Charts']
      ,transparent : true
    }
    ,{
       isBaseLayer : false
      ,projection  : proj3857
      ,visibility  : defaultBasemap == 'Navigational Charts'
      ,opacity     : defaultOpacities['Navigational Charts'] / 100
    }
  );

  openStreetMap = new OpenLayers.Layer.OSM(
     'Open StreetMap'
    ,'http://tile.openstreetmap.org/${z}/${x}/${y}.png'
    ,{opacity : defaultOpacities['Open StreetMap'] / 100,visibility : defaultBasemap == 'Open StreetMap' || defaultBasemap == 'ESRI Ocean'}
  );

  map = new OpenLayers.Map('map',{
     projection        : proj900913
    ,displayProjection : proj4326
    ,units             : "m"
    ,maxExtent         : new OpenLayers.Bounds(-20037508,-20037508,20037508,20037508.34)
    ,layers            : [
       openStreetMap
      ,esriOcean
      ,navCharts
      ,new OpenLayers.Layer.Google('Google Satellite',{
         type          : google.maps.MapTypeId.SATELLITE
        ,projection    : proj900913
        ,opacity       : defaultOpacities['Google Satellite'] / 100
        ,visibility    : defaultBasemap == 'Google Satellite'
      })
      ,new OpenLayers.Layer.Google('Google Terrain',{
         type          : google.maps.MapTypeId.TERRAIN
        ,projection    : proj900913
        ,opacity       : defaultOpacities['Google Terrain'] / 100
        ,visibility    : defaultBasemap == 'Google Terrain'
      })
      ,lyrQueryPts
    ]
  });

  esriOcean.events.register('visibilitychanged',this,function() {
    if (esriOcean.visibility) {
      openStreetMap.setOpacity(1);
      map.setBaseLayer(openStreetMap);
    }
  });
  navCharts.events.register('visibilitychanged',this,function() {
    if (navCharts.visibility) {
      openStreetMap.setOpacity(1);
      map.setBaseLayer(openStreetMap);
    }
  });

  for (var i = 0; i < map.layers.length; i++) {
    var lyr = map.getLayersByName(defaultBasemap)[0];
    if (!lyr.visibility) {
      map.setBaseLayer(lyr);
    }
  }

  map.events.register('click',this,function(e) {
    mapClick(e);
  });

  map.events.register('addlayer',this,function() {
    map.setLayerIndex(lyrQueryPts,map.layers.length - 1);
  });

  map.setCenter(new OpenLayers.LonLat(defaultCenter[0],defaultCenter[1]),defaultZoom);

  var navControl = new OpenLayers.Control.NavToolbar();
  map.addControl(navControl);

  var mouseControl = new OpenLayers.Control.MousePosition({
    formatOutput: function(lonLat) {
      return convertDMS(lonLat.lat.toFixed(5), "LAT") + ' ' + convertDMS(lonLat.lon.toFixed(5), "LON");
    }
  });
  mouseControl.displayProjection = new OpenLayers.Projection('EPSG:4326');
  map.addControl(mouseControl);

  map.events.register('zoomend',this,function() {
    if (popupObs) {
      popupObs.hide();
    }
  });
  map.events.register('moveend',this,function() {
    if (navControl.controls[1].active) {
      navControl.controls[1].deactivate();
      navControl.draw();
    }
    syncObs({name : 'NDBC'});
    syncObs({name : 'CO-OPS'});
    syncObs({name : 'USGS'});
    syncObs({name : 'NERRS'});
    syncObs({name : 'Weatherflow'});
    syncObs({name : 'HF Radar'});
    syncObs({name : 'Satellites'});
    syncObs({name : 'Gliders'});
    if (popupObs) {
      popupObs.show();
    }
  });
  map.events.register('changelayer',this,function(e) {
    if (e.property == 'opacity') {
      if (e.layer.name == 'ESRI Ocean') {
        openStreetMap.setOpacity(esriOcean.opacity);
      }
      else if (e.layer.name == 'Navigational Charts') {
        openStreetMap.setOpacity(navCharts.opacity);
      }
    }
    else if (e.property == 'params') {
      // keep legend in sync if a GetLegendGraphic legend
      var idx = mainStore.find('name',e.layer.name);
      if (idx >= 0 && mainStore.getAt(idx).get('legend').indexOf('GetLegendGraphic') >= 0) {
        var params = {
           REQUEST : 'GetLegendGraphic'
          ,LAYER   : OpenLayers.Util.getParameters(e.layer.getFullRequestString({}))['LAYERS']
        };
        mainStore.getAt(idx).get('legend').indexOf('GetMetadata') >= 0 ? params.GetMetadata = '' : false;
        mainStore.getAt(idx).set('legend',e.layer.getFullRequestString(params));
        mainStore.getAt(idx).commit();
      }
    }
  });

  addWMS({
     name   : 'NCOM SST'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
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
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
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
     name   : 'ROMS'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
    ,layers : 'NOSCBOFSCUR_CURRENTS'
    ,format : 'image/' + defaultImageTypes['ROMS']
    ,styles : defaultStyles['ROMS']
    ,singleTile : true
    ,projection : proj3857
  });
  addWMS({
     name   : 'STPS'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
    ,layers : 'CODARSTPS_CURRENTS'
    ,format : 'image/' + defaultImageTypes['STPS']
    ,styles : defaultStyles['STPS']
    ,singleTile : true
    ,projection : proj3857
  });
  addWMS({
     name   : 'Stevens NYHOPS'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
    ,layers : 'NYHOPSCUR_currents'
    ,format : 'image/' + defaultImageTypes['Stevens NYHOPS']
    ,styles : defaultStyles['Stevens NYHOPS']
    ,singleTile : true
    ,projection : proj3857
  });
  addWMS({
     name   : 'NCOM currents'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
    ,layers : 'NCOM_CURRENTS'
    ,format : 'image/' + defaultImageTypes['NCOM currents']
    ,styles : defaultStyles['NCOM currents']
    ,singleTile : true
    ,projection : proj3857
  });
  addWMS({
     name   : 'HYCOM currents'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
    ,layers : 'HYCOM_CURRENTS'
    ,format : 'image/' + defaultImageTypes['HYCOM currents']
    ,styles : defaultStyles['HYCOM currents']
    ,singleTile : true
    ,projection : proj3857
  });
  addWMS({
     name   : 'UMass'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
    ,layers : 'FVCOM_MASS_CURRENTS'
    ,format : 'image/' + defaultImageTypes['UMass']
    ,styles : defaultStyles['UMass']
    ,singleTile : true
    ,projection : proj3857
  });
  addWMS({
     name   : 'NAM winds'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
    ,layers : 'NAM_WINDS'
    ,format : 'image/' + defaultImageTypes['NAM winds']
    ,styles : defaultStyles['NAM winds']
    ,singleTile : true
    ,projection : proj3857
  });
  addWMS({
     name   : 'HF radar currents'
    ,url    : 'http://new.coastmap.com/ecop/wms.aspx?'
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

  addTMS({
     name   : 'WWA'
    ,url    : [
       'http://radarcache0.srh.noaa.gov/tc/tc.py/'
      ,'http://radarcache1.srh.noaa.gov/tc/tc.py/'
      ,'http://radarcache2.srh.noaa.gov/tc/tc.py/'
      ,'http://radarcache3.srh.noaa.gov/tc/tc.py/'
      ,'http://radarcache4.srh.noaa.gov/tc/tc.py/'
    ]
    ,layer  : 'threat'
    ,format : 'png'
    ,projection : proj4326
  });

  addTileCache({
     name   : 'Zones'
    ,url    : 'http://gentoo/tilecache/'
    ,layer  : 'marine_zones'
    ,projection : proj900913
  });

  addTileCache({
     name   : 'Bathymetry contours'
    ,url    : 'http://assets.maracoos.org/tilecache/'
    ,layer  : 'bathy'
    ,projection : proj900913
  });

  addVector({
     name       : 'ROMS ESPRESSO'
    ,visibility : typeof defaultLayers['ROMS ESPRESSO'] != 'undefined'
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
     name       : 'NERRS'
    ,visibility : typeof defaultLayers['NERRS'] != 'undefined'
  });
  addObs({
     name       : 'Weatherflow'
    ,visibility : typeof defaultLayers['Weatherflow'] != 'undefined'
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

  makeTimeSlider(true);
}

function setLayerInfo(layerName,on) {
  var idx = mainStore.find('name',layerName);
  mainStore.getAt(idx).set('info',on ? 'on' : 'off');
  mainStore.getAt(idx).commit();

  // only one popup can be displayed at a time
  mainStore.each(function(rec) {
    if (layerName != rec.get('name') && rec.get('info') == 'on') {
      rec.set('info','off');
      rec.commit();
      if (Ext.getCmp('info.popup.' + rec.get('name'))) {
        Ext.getCmp('info.popup.' + rec.get('name')).destroy();
      }
    }
  });

  if (on && (!Ext.getCmp('info.popup.' + layerName) || !Ext.getCmp('info.popup.' + layerName).isVisible())) {
    new Ext.ToolTip({
       id        : 'info.popup.' + layerName
      ,title     : mainStore.getAt(idx).get('displayName') + ' :: details'
      ,anchor    : 'bottom'
      ,target    : 'info.' + layerName 
      ,autoHide  : false
      ,closable  : true
      ,items     : {bodyCssClass : 'popup',html : mainStore.getAt(idx).get('infoBlurb')}
      ,listeners : {hide : function() {
        this.destroy();
        mainStore.getAt(idx).set('info','off');
        mainStore.getAt(idx).commit();
      }}
    }).show();
  }
}

function setLayerStatus(layerName,on) {
  var idx = mainStore.find('name',layerName);
  mainStore.getAt(idx).set('status',on ? 'on' : 'off');
  mainStore.getAt(idx).commit();
  map.getLayersByName(layerName)[0].setVisibility(on);
}

function setLayerSettings(layerName,on) {
  var idx = mainStore.find('name',layerName);

  // only one popup can be displayed at a time
  mainStore.each(function(rec) {
    if (rec.get('settings') == 'on') {
      rec.set('settings','off');
      rec.commit();
      if (Ext.getCmp('settings.popup.' + rec.get('name'))) {
        Ext.getCmp('settings.popup.' + rec.get('name')).destroy();
      }
    }
  });

  if (on && (!Ext.getCmp('settings.popup.' + layerName) || !Ext.getCmp('settings.popup.' + layerName).isVisible())) {
    var height = 26;
    var id = Ext.id();
    var items = [
      new Ext.Slider({
         fieldLabel : 'Opacity<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.opacity' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.opacity' + '" src="img/info.png"></a>'
        ,id       : 'opacity'
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
    if (mainStore.getAt(idx).get('settingsImageType') != '') {
      height += 27;
      items.push(
        new Ext.form.ComboBox({
           fieldLabel     : 'Resolution<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.resolution' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.resolution' + '" src="img/info.png"></a>'
          ,id             : 'imageType'
          ,store          : imageTypesStore
          ,displayField   : 'name'
          ,valueField     : 'value'
          ,value          : mainStore.getAt(idx).get('settingsImageType')
          ,editable       : false
          ,triggerAction  : 'all'
          ,mode           : 'local'
          ,width          : 130
          ,forceSelection : true
          ,listeners      : {
            afterrender : function() {
              new Ext.ToolTip({
                 id     : 'tooltip.' + id + '.resolution'
                ,target : id + '.resolution'
                ,html   : "Selecting high resolution results in images of greater quality as well as longer download times."
              });
            }
            ,select : function(comboBox,rec) {
              mainStore.getAt(idx).set('settingsImageType',rec.get('value'));
              mainStore.getAt(idx).commit();
              map.getLayersByName(mainStore.getAt(idx).get('name'))[0].mergeNewParams({FORMAT : 'image/' + rec.get('value')});
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
          ,id             : 'palette'
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
          ,id             : 'layers'
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
          ,id             : 'baseStyle'
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
          ,id             : 'colorMap'
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
          ,id       : 'minMax'
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
           fieldLabel : 'Striding<a href="javascript:Ext.getCmp(\'tooltip.' + id + '.striding' + '\').show()"><img style="margin-left:2px;margin-bottom:2px" id="' + id + '.striding' + '" src="img/info.png"></a>'
          ,id       : 'striding'
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
                ,html   : "Adjust the space between vectors with the striding factor.  The impact of this value varies based on the zoom level."
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
          ,id             : 'tailMag'
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
          ,id             : 'barbLabel'
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

    new Ext.ToolTip({
       id        : 'settings.popup.' + layerName
      ,title     : mainStore.getAt(idx).get('displayName') + ' :: settings'
      ,anchor    : 'left'
      ,target    : 'settings.' + layerName
      ,autoHide  : false
      ,closable  : true
      ,items     : [
         new Ext.FormPanel({buttonAlign : 'center',border : false,bodyStyle : 'background:transparent',width : 240,height : height + 35,labelWidth : 100,labelSeparator : '',items : items,buttons : [{text : 'Restore default settings',width : 150,handler : function() {restoreDefaultStyles(layerName,items)}}]})
      ]
      ,listeners : {hide : function() {
        if (spotTooltip && spotTooltip.isVisible()) {
          spotTooltip.hide();
        }
        this.destroy();
        mainStore.getAt(idx).set('settings','off');
        mainStore.getAt(idx).commit();
      }}
    }).show();
  }

  mainStore.getAt(idx).set('settings',on ? 'on' : 'off');
  mainStore.getAt(idx).commit();
}

function renderLayerButton(val,metadata,rec) {
  return '<a id="layer.' + rec.get('name') + '" href="javascript:setLayerStatus(\'' + rec.get('name')  + '\',\'' + rec.get('status') + '\' != \'on\')"><img title="Toggle layer visibility" class="layerIcon" width=40 height=20 src="img/' + rec.get('name') + '.' + rec.get('status') + '.png"></a>';
}

function renderLayerInfoLink(val,metadata,rec) {
  return '<a id="info.' + rec.get('name') + '" href="javascript:setLayerInfo(\'' + rec.get('name')  + '\',\'' + rec.get('info') + '\' != \'on\')">' + val + '<img title="View layer metadata" style="margin-left:2px;margin-bottom:2px" src="img/info.png"></a>';
}

function renderSettingsButton(val,metadata,rec) {
  if (val != '') {
    return '<a id="settings.' + rec.get('name') + '" href="javascript:setLayerSettings(\'' + rec.get('name')  + '\',\'' + rec.get('settings') + '\' != \'on\')"><img title="Customize layer appearance" class="settingsIcon" width=20 height=20 src="img/settings.' + rec.get('settings') + '.png"></a>';
  }
}

function renderBboxButton(val,metadata,rec) {
  if (val != '') {
    return '<a href="javascript:zoomToBbox(\'' + rec.get('bbox') + '\')"><img title="Zoom to layer" style="margin-top:2px" src="img/Search-Globe-icon.png"></a>';
  }
}

function renderLayerStatus(val,metadata,rec) {
  if (val == 'loading') {
    return '<img src="img/loading.gif">';
  }
  else {
    return '<img class="layerIcon" src="img/' + rec.get('name') + '.drawn.png">';
  }
}

function renderLegend(val,metadata,rec) {
  var idx = mainStore.find('name',rec.get('name'));
  var a = [rec.get('displayName')];
  if (rec.get('timestamp') && rec.get('timestamp') != '') {
    a.push(rec.get('timestamp'));
  }
  if (mainStore.getAt(idx).get('legend') != '') {
    a.push('<img src="getLegend.php?' + mainStore.getAt(idx).get('legend') + '">');
  }
  return a.join('<br/>');
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
    }
  });
  lyr.events.register('loadstart',this,function(e) {
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
      }));
    }
    idx = chartLayerStore.find('name',lyr.name);
    if (idx < 0) {
      var mainIdx = mainStore.find('name',lyr.name);
      if (mainStore.getAt(mainIdx).get('queryable') == 'true') {
        chartLayerStore.add(new chartLayerStore.recordType({
           rank        : mainStore.getAt(mainIdx).get('rank')
          ,name        : lyr.name
          ,displayName : mainStore.getAt(mainIdx).get('displayName')
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
            + '&drawing=false'
          ,callback : function(r) {
            if (r.responseText == '') {
              rec.set('timestamp','<span class="alert">There was a problem<br/>drawing this layer.<span>');
            }
            else if (r.responseText == 'invalidBbox') {
              rec.set('timestamp','<span class="alert">This layer\'s domain<br/>is out of bounds.<span>');
            }
            else {
              rec.set('timestamp',shortDateString(new Date(r.responseText * 1000)));
              if (lastMapClick['layer'] == lyr.name && lyrQueryPts.features.length > 0) {
                mapClick(lastMapClick['e']);
              }
            }
          }
        });
      }
    }
    // record the action on google analytics
    pageTracker._trackEvent('layerView','loadEnd',mainStore.getAt(mainStore.find('name',lyr.name)).get('displayName'));
  });
  if (timeSensitive) {
    lyr.mergeNewParams({TIME : dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + 'T' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00'});
  }
  map.addLayer(lyr);
}

function addWMS(l) {
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
    }
  );
  addLayer(lyr,true);
}

function addTileCache(l) {
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
  var lyr = new OpenLayers.Layer.TMS(
     l.name
    ,l.url
    ,{
       layername   : l.layer
      ,type        : l.format
      ,visibility  : mainStore.find('name',l.name) >= 0 ? mainStore.getAt(mainStore.find('name',l.name)).get('status') == 'on' : false
      ,isBaseLayer : false
      ,projection  : l.projection
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
        return url + path;
      }
    }
  );
  addLayer(lyr,false);
}

function addObs(l) {
  var lyr = new OpenLayers.Layer.Vector(
     l.name
    ,{
      styleMap : new OpenLayers.StyleMap({
        'default' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
           externalGraphic : 'img/' + l.name + '.png'
          ,pointRadius     : 8
          ,graphicWidth    : 20
          ,graphicHeight   : 20
          ,graphicOpacity  : 1
          ,strokeWidth     : '${strokeWidth}'
          ,strokeColor     : '${strokeColor}'
          ,strokeOpacity   : '${strokeOpacity}'
          ,strokeDashstyle : '${strokeDashstyle}'
        }))
        ,'select' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
           externalGraphic : 'img/' + l.name + '.select.png'
          ,pointRadius     : 8
          ,graphicWidth    : 40
          ,graphicHeight   : 40
          ,graphicOpacity  : 1
          ,strokeWidth     : '${strokeWidth}'
          ,strokeColor     : '${strokeColor}'
          ,strokeOpacity   : '${strokeOpacity}'
          ,strokeDashstyle : '${strokeDashstyle}'
        }))
        ,'temporary' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
           externalGraphic : 'img/' + l.name + '.hilite.png'
          ,pointRadius     : 8
          ,graphicWidth    : 40
          ,graphicHeight   : 40
          ,graphicOpacity  : 1
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
    }
    else {
      syncObs({name : lyr.name});
    }
  });
  lyr.events.register('loadstart',this,function(e) {
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
      }));
    }
    // record the action on google analytics
    pageTracker._trackEvent('layerView','loadStart',mainStore.getAt(mainStore.find('name',lyr.name)).get('displayName'));
  });
  lyr.events.register('loadend',this,function(e) {
    var idx = legendsStore.find('name',lyr.name);
    var assetsIndex = assetsStore.find('name',lyr.name);
    if (idx >= 0) {
      var rec = legendsStore.getAt(idx);
      rec.set('status','drawn');
      var mainStoreRec = mainStore.getAt(mainStore.find('name',lyr.name));
      if (map.getZoom() + zoomOffset() < obsMinZoom[lyr.name]) {
        mainStoreRec.set('legend','img/zoom.png');
        rec.set('timestamp',lyr.features.length * lyr.featureFactor + ' station(s) fetched<br/><span class="alert">More stations available<br/>at a closer zoom.<span>');
      }
      else {
        var leg = assetsStore.getAt(assetsIndex).get('legend');
        if (leg.indexOf('legends') < 0) {
          leg = '';
        }
        mainStoreRec.set('legend',leg);
        rec.set('timestamp',lyr.features.length * lyr.featureFactor + ' station(s) fetched');
      }
      mainStoreRec.commit();
      rec.commit();
    }
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
          // figure out the target id (the id of the dot)
          var showPopup = false;
          var target;
          var title;
          for (var i in e.feature.attributes.data) {
            for (var j = 0; j < e.feature.attributes.data[i].length; j++) {
              title = e.feature.attributes.data[i][0].descr;
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
          if (mouseoverObs) {
            mouseoverObs.hide();
          }
          mouseoverObs = new Ext.ToolTip({
             html         : title
            ,anchor       : 'bottom'
            ,target       : target
            ,dismissDelay : 2500
            ,listeners    : {
              hide    : function() {
                this.destroy();
                mouseoverObs = null;
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
              target = 'OpenLayers.Geometry.Point_' + (Number(e.feature.id.split('_')[e.feature.id.split('_').length - 1]) - 1);
              if (e.feature.attributes.featureId) {
                target = 'OpenLayers.Geometry.Point_' + (Number(e.feature.attributes.featureId.split('_')[e.feature.attributes.featureId.split('_').length - 1]) - 3);
              }
              if (e.feature.attributes.data[i][0].url) {
                showPopup = true;
              }
            }
          }
          if (popupObs) {
            popupObs.hide();
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
              hide    : function() {
                this.destroy();
                popupObs = null;
                if (e.feature.layer) {
                  popupCtl.unselect(e.feature);
                }
              }
              ,render : function() {
                for (var i in e.feature.attributes.data) {
                  for (var j = 0; j < e.feature.attributes.data[i].length; j++) {
                    OpenLayers.Request.GET({
                       url      : e.feature.attributes.data[i][0].url + '&tz=' + new Date().getTimezoneOffset() + '&uom=english'
                      ,callback : function(r) {
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
                          popupObs.resumeEvents();
                        }
                      }
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

function syncObs(l,force) {
  var lyrIdx;
  for (var j = 0; j < map.layers.length; j++) {
    if (map.layers[j].name == l.name) {
      lyrIdx = j;
    } 
  }

  if (!map.layers[lyrIdx].visibility) {
    return;
  }

  var realExtent = map.getExtent();
  var bigExtent  = new OpenLayers.Geometry.LinearRing(map.getExtent().toGeometry().getVertices()).resize(obsBigExtentScale,new OpenLayers.Geometry.Point(map.getCenter().lon,map.getCenter().lat)).getBounds();

  map.layers[lyrIdx].events.triggerEvent('loadstart');
  if (!obsBbox[l.name] || !obsBbox[l.name].containsBounds(realExtent) || map.getZoom() + zoomOffset() != obsZoom[l.name]) {
    map.layers[lyrIdx].removeFeatures(map.layers[lyrIdx].features);
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
      ,callback : function(r) {
        var obs = new OpenLayers.Format.JSON().read(r.responseText);
        obsBbox[l.name] = new OpenLayers.Bounds(obs.bbox[0],obs.bbox[1],obs.bbox[2],obs.bbox[3]).transform(proj4326,map.getProjectionObject());;
        obsZoom[l.name] = obs.zoom;
        var boundsEqual = true;
        for (var loc in obs.data) {
          // Gliders are unique beasts.
          if (loc.indexOf('Gliders') >= 0) {
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
                vec.attributes.provider      = 'Gliders';
                vec.attributes.active        = obs.data[loc][loc][i].active;
                vec.attributes.strokeWidth   = 2;
                vec.attributes.strokeColor   = '#ffff00';
                if (obs.data[loc][loc][i].active) {
                  vec.attributes.strokeOpacity = 0.80;
                  vec.attributes.strokeDashstyle = 'solid';
                }
                else {
                  vec.attributes.strokeOpacity = 0.50;
                  vec.attributes.strokeDashstyle = 'dot';
                }
                map.layers[lyrIdx].addFeatures(vec);
                var f = new OpenLayers.Feature.Vector(pts[pts.length - 1]);
                f.attributes.featureId           = f.id;
                f.attributes.provider            = 'Gliders';
                f.attributes.data                = obs.data[loc];
                f.attributes.active              = obs.data[loc][loc][i].active;
                map.layers[lyrIdx].featureFactor = 0.5;
                map.layers[lyrIdx].addFeatures(f);
              }
            }
          }
          else {
            var p = loc.split(',');
            var f = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(p[0],p[1]).transform(proj4326,map.getProjectionObject()));
            f.attributes.data = obs.data[loc];
            f.attributes.lon  = p[0];
            f.attributes.lat  = p[1];
            f.attributes.graphicOpacity = 1;
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
  Ext.getCmp('graphAction').setText('Processing');
  Ext.getCmp('graphAction').setIcon('img/blueSpinner.gif');
  makeChart(href,'obs',popupObs.title);
}

function makeChart(url,type,title) {
  if (type == 'obs') {
    Ext.getCmp('chartLayerCombo').hide();
    Ext.getCmp('activeLabel').setText(popupObs.title);
  }
  else {
    Ext.getCmp('chartLayerCombo').show();
    Ext.getCmp('activeLabel').setText('Active model query layer: ');
  }
  OpenLayers.Request.GET({
     url      : url
    ,callback : function(r) {
      var obs = new OpenLayers.Format.JSON().read(r.responseText);
      chartData = [];
      var yaxis = 1;
      if (obs.d == '') {
        Ext.Msg.alert('Query alert','There was an error fetching query results for this layer.');
        // record the action on google analytics
        pageTracker._trackEvent('chartView',title,'error');
      }
      else {
        for (var v in obs.d) {
          // get the data
          chartData.push({
             data   : []
            ,label  : v + ' (' + toEnglish({typ : 'title',src : obs.u[v],val : obs.u[v]}) + ')'
            ,yaxis  : yaxis
            ,lines  : {show : true}
          });
          for (var i = 0; i < obs.d[v].length; i++) {
            chartData[chartData.length-1].data.push([obs.t[i],toEnglish({typ : 'obs',src : obs.u[v],val : obs.d[v][i]})]);
          }
          if (obs.d[v].length == 1) {
            chartData[chartData.length - 1].points = {show : true};
          }
          if (obs.d[v].length > 1 && obs.nowIdx) {
            chartData.push({
               data   : [[obs.t[obs.nowIdx],toEnglish({typ : 'obs',src : obs.u[v],val : obs.d[v][obs.nowIdx]})]]
              ,yaxis  : yaxis
              ,points : {show : true}
            });
          }
          yaxis++;
        }
        // record the action on google analytics
        pageTracker._trackEvent('chartView',title,'ok');
      }
      Ext.getCmp('timeseriesPanel').fireEvent('resize',Ext.getCmp('timeseriesPanel'));
    }
  });
}

function zoomOffset() {
  return map.getLayersByName('Open StreetMap')[0].visibility ? -5 : 0;
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
  map.getLayersByName(rec.get('name'))[0].mergeNewParams({STYLES : styles.join('-')});
}

function restoreDefaultStyles(l,items) {
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
    if (items[i].id == 'opacity') {
      cmp.setValue(guaranteeDefaultOpacities[l]);
    }
    else if (items[i].id == 'imageType') {
      cmp.setValue('png');
      cmp.fireEvent('select',cmp,new imageTypesStore.recordType({value : 'png'}));
    }
    else if (items[i].id == 'palette') {
      cmp.setValue(settings['palette']);
      cmp.fireEvent('select',cmp,new palettesStore[l].recordType({name : settings['palette']}));
    }
    else if (items[i].id == 'layers') {
      cmp.setValue(defaultLayerLayers[l]);
      cmp.fireEvent('select',cmp,new layersStore[l].recordType({wmsName : defaultLayerLayers[l]}));
    }
    else if (items[i].id == 'baseStyle') {
      cmp.setValue(settings['baseStyle']);
      cmp.fireEvent('select',cmp,new baseStylesStore.recordType({value : settings['baseStyle']}));
    }
    else if (items[i].id == 'colorMap') {
      cmp.setValue(settings['colorMap']);
      cmp.fireEvent('select',cmp,new colorMapStore.recordType({name : settings['colorMap']}));
    }
    else if (items[i].id == 'striding') {
      cmp.setValue(stridingStore.find('param',settings['striding']));
      cmp.fireEvent('change',cmp,stridingStore.find('param',settings['striding']));
    }
    else if (items[i].id == 'tailMag') {
      cmp.setValue(settings['tailMag']);
      cmp.fireEvent('select',cmp,new tailMagStore.recordType({name : settings['tailMag']}));
    }
    else if (items[i].id == 'barbLabel') {
      cmp.setValue(settings['barbLabel']);
      cmp.fireEvent('select',cmp,new barbLabelStore.recordType({name : settings['barbLabel']}));
    }
    else if (items[i].id == 'minMax') {
      cmp.setValue(0,settings['min']);
      cmp.setValue(1,settings['max']);
      cmp.fireEvent('change',cmp);
    }
  }
}

function mapClick(e) {
  lastMapClick['e'] = e;
  lyrQueryPts.removeFeatures(lyrQueryPts.features);
  var lyr = map.getLayersByName(Ext.getCmp('chartLayerCombo').getValue())[0];
  if (!lyr) {
    return;
  }
  // WMS layers only
  if (lyr.visibility && lyr.DEFAULT_PARAMS) {
    lastMapClick['layer'] = lyr.name;
    Ext.getCmp('graphAction').setText('Processing');
    Ext.getCmp('graphAction').setIcon('img/blueSpinner.gif');
    var lonLat = map.getLonLatFromPixel(e.xy);
    var f = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(lonLat.lon,lonLat.lat));
    f.attributes.img = 'Delete-icon.png';
    lyrQueryPts.addFeatures(f);

    var mapTime;
    var legIdx = legendsStore.find('name',lyr.name);
    if (legIdx >= 0 && legendsStore.getAt(legIdx).get('timestamp') && String(legendsStore.getAt(legIdx).get('timestamp')).indexOf('alert') < 0) {
      mapTime = '&mapTime=' + (new Date(shortDateToDate(legendsStore.getAt(legIdx).get('timestamp')).getTime() - new Date().getTimezoneOffset() * 60000) / 1000);
    }
    var paramOrig = OpenLayers.Util.getParameters(lyr.getFullRequestString({}));
    var paramNew = {
       REQUEST       : 'GetFeatureInfo'
      ,EXCEPTIONS    : 'application/vnd.ogc.se_xml'
      ,BBOX          : map.getExtent().toBBOX()
      ,X             : e.xy.x
      ,Y             : e.xy.y
      ,INFO_FORMAT   : 'text/xml'
      ,FEATURE_COUNT : 1
      ,WIDTH         : map.size.w
      ,HEIGHT        : map.size.h
      ,QUERY_LAYERS  : forceQueryLayers(lyr.name,paramOrig['LAYERS'])
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
    makeChart(lyr.getFullRequestString(paramNew,'getFeatureInfo.php?' + lyr.url + '&tz=' + new Date().getTimezoneOffset() + mapTime),'model',mainStore.getAt(mainStore.find('name',lyr.name)).get('displayName'));
  }
}

function zoomToBbox(bbox) {
  var p = bbox.split(',');
  map.zoomToExtent(new OpenLayers.Bounds(p[0],p[1],p[2],p[3]).transform(proj4326,map.getProjectionObject()));
}

function showHelp(fromButton) {
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
        if (!spot && !fromButton) {
          spot = new Ext.ux.Spotlight({
             easing   : 'easeOut'
            ,duration : 0.80
          });
          setLayerSettings('NCOM currents',true);
          spot.show('settings.popup.NCOM currents');
          spotTooltip = new Ext.ToolTip({
             title     : 'Customization tip'
            ,id        : 'customizationTip'
            ,anchor    : 'bottom'
            ,target    : 'settings.popup.NCOM currents'
            ,autoHide  : false
            ,closable  : true
            ,items     : new Ext.FormPanel({buttonAlign : 'center',border : false,bodyStyle : 'background:transparent',width : 240,height : 83,labelAlign : 'right',labelWidth : 200,labelSeparator : '',items : [
              {bodyCssClass : 'popup',html : 'Layer customization options such as the ones below are available to you by clicking on a settings icon to the right of a layer.'}
              ,new Ext.form.Checkbox({
                 fieldLabel : "Don't show help and tips again."
                ,listeners  : {check : function(cbox,checked) {
                  cp.set('hideAssetsHelpOnStartup',checked);
                }}
              })
            ]})
            ,listeners : {hide : function() {
              this.destroy();
              spot.hide();
            }}
          });
          spotTooltip.show();
        }
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
  if (v.src.indexOf('Celcius') >= 0) {
    if (v.typ == 'title') {
      return v.val.replace('Celcius','Fahrenheit');
    }
    else {
      return v.val * 9/5 + 32;
    }
  }
  else if (v.src.indexOf('Meters') >= 0) {
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
      else if (assIdx >= 0) {
        features[lyr.name] = [];
        tracks[lyr.name]   = [];
        for (var j = 0; j < lyr.features.length; j++) {
          var verts = lyr.features[j].geometry.getVertices();
          if (verts.length == 1) {
            var cen = verts[0].getCentroid();
            var pix = map.getPixelFromLonLat(new OpenLayers.LonLat(cen.x,cen.y));
            features[lyr.name].push([pix.x,pix.y]);
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
  if (map.getScale() <= 433344.01634946937) {
    baseLayer = openStreetMap;
  }
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

function makeAvailableTimes() {
  dNow = new Date();
  var dNow12Hours = new Date(dNow.getTime());
  dNow12Hours.setHours(12);
  for (var i = -numTics; i <= numTics; i++) {
    availableTimes.push(new Date(dNow12Hours.getTime() + ticIntervalHours * i * 60 * 60 * 1000));
  }
  if (dNow.getHours() >= 12) {
    dNow.setHours(12);
  }
  else {
    dNow.setHours(0);
  }
}

function makeTimeSlider(initOnly) {
  if (!initOnly) {
    makeAvailableTimes();
  }

  var tbody = document.getElementById('sliderTicsTable').getElementsByTagName('tbody')[0];
  if (tbody.hasChildNodes()) {
    while (tbody.childNodes.length >= 1) {
      tbody.removeChild(tbody.firstChild);
    }
  }
  var tr = document.createElement('tr');
  for (var i = 0; i < availableTimes.length; i++) {
    var td = document.createElement('td');
    if (availableTimes[i].getHours() == 0) {
      td.innerHTML = (availableTimes[i].getMonth() + 1) + '/' + availableTimes[i].getDate();
      td.style.width = (1 / numTics * 100) + '%';
      td.style.paddingRight = i;
    }
    else {
      td.innerHTML = '<img src="img/blank.png" width=2>';
    }
    if (i == 0 || availableTimes[i].getHours() != 0 || i == availableTimes.length - 1) {
      td.className = 'fillSolid';
    }
    tr.appendChild(td);
    if (availableTimes[i].getTime() == dNow.getTime()) {
      Ext.getCmp('timeSlider').suspendEvents();
      Ext.getCmp('timeSlider').setValue(i);
      Ext.getCmp('timeSlider').resumeEvents();
      if (!initOnly) {
        var dStr = dNow.getUTCFullYear() + '-' + String.leftPad(dNow.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(dNow.getUTCDate(),2,'0') + 'T' + String.leftPad(dNow.getUTCHours(),2,'0') + ':00';
        for (var j = 0; j < map.layers.length; j++) {
          // WMS layers only
          if (map.layers[j].DEFAULT_PARAMS) {
            map.layers[j].mergeNewParams({TIME : dStr,unique : new Date().getTime()});
            // record the action on google analytics
            if (mainStore.find('name',map.layers[i].name) >= 0) {
              pageTracker._trackEvent('timeSlider',mainStore.getAt(mainStore.find('name',map.layers[i].name)).get('displayName'));
            }
          }
        }
      }
    }
  }
  tbody.appendChild(tr);
}
