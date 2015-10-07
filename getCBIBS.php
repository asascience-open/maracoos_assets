<?php
  $url = 'http://cbibs-dev.asa.rocks/cdrh_rpc';
  $key = '0b0e81fe763a79660716bcee98a9ccbea653c8bd';
  $target_constellations = array('CBIBS');

  $constellations = request($url,$key,array(
     'method' => 'ListConstellations'
    ,'params' => array(
      $key
    )
    ,'id' => 1
  ));

  $d = array();
  foreach (array_intersect($constellations['result'],$target_constellations) as $constellation) {
    $platforms = request($url,$key,array(
       'method' => 'ListPlatforms'
      ,'params' => array(
         $constellation
        ,$key
      )
      ,'id' => 1
    ));

    for ($i = 0; $i < count($platforms['result']['id']); $i++) {
      $metadata = request($url,$key,array(
         'method' => 'GetMetaDataLocation'
        ,'params' => array(
           $constellation
          ,$platforms['result']['id'][$i]
          ,$key
        )
        ,'id' => 1
      ));

      array_push($d,array(
         'id'            => $platforms['result']['id'][$i]
        ,'name'          => $platforms['result']['cn'][$i]
        ,'lon'           => $metadata['result']['longitude']
        ,'lat'           => $metadata['result']['latitude']
        ,'constellation' => $constellation
      ));
    }
  }

  file_put_contents('xml/cbibs.json',json_encode($d));

  function request($url,$key,$d) {
    $options = array(
      'http' => array(
        'header'  => array(
           'Content-Type: application/json'
          ,'Accept: application/json'
        )
        ,'method'  => 'POST'
        ,'content' => json_encode($d)
      )
    );

    $context = stream_context_create($options);
    $result  = file_get_contents(
       $url
      ,false
      ,$context
    );
    return json_decode($result, TRUE);
  }
?>
