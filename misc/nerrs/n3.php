<?php 
  header('Content-Type:text/plain');
  require_once('/usr/local/nusoap/lib/nusoap.php');
  nusoap_base::setGlobalDebugLevel(0);
  $wsdl = new nusoap_client('http://129.252.139.102/webservices/xmldatarequest.cfc?wsdl');
  $result = $wsdl->call('exportAllParamsDateRangeXML',
// array('tbl'=>'niwolwq','mindate'=>'12/06/2006','maxdate'=>'12/07/2006','fieldlist'=>'pH')
    array('tbl'=>'cbvtcmet','mindate'=>'07/13/2011','maxdate'=>'07/14/2011','fieldlist'=>'CumPrcp')
  );
var_dump($result);

?>
