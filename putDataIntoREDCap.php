<?php
// read a single json file such as data/UCSD/lmt_NDAR_INV31JTFZ92_baseline_year_1_arm_1_01.json
// usage:
//   php putDataIntoREDCap.php -f "data/UCSD/lmt_NDAR_INV31JTFZ92_baseline_year_1_arm_1_01.json"


$options = getopt("f:");

if (!isset($options['f'])) {
    echo("Error: specify a file with -f <filename>\n");
    exit(1);
}
//echo ("try to read now :".json_encode($options)."\n");
if (!is_readable($options['f'])) {
    echo("Error: file not found or not readable\n");
    exit(1);
}

$file_contents = json_decode(file_get_contents($options['f']),true);

$token = "";
$tokens = json_decode(file_get_contents("tokens.json"), true);
$token = $tokens[$file_contents['lmt_site']];
// put your token in here

$participant = $file_contents['lmt_subject_id'];
$event_name  = $file_contents['lmt_event_name'];
$pGUID = $participant;

// send over one data set
// create loop over all data entries in $data['data']

// copy values after successful import to data-import-archive and data-import-fail

$data = array(
    'token' => $token,
    'content' => 'record',
    'format' => 'json',
    'type' => 'flat',
    'records' => array($participant),
    'fields' => array('cp_consent_sign_v2'),
    'events' => array('baseline_year_1_arm_1'),
    'rawOrLabel' => 'raw',
    'rawOrLabelHeaders' => 'raw',
    'exportCheckboxLabel' => 'false',
    'exportSurveyFields' => 'false',
    'exportDataAccessGroups' => 'false',
        'returnFormat' => 'json'
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://abcd-rc.ucsd.edu/redcap/api/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
$output = curl_exec($ch);
curl_close($ch);

// check for returned value of a real participant (test with fake name)
$retval = json_decode($output, true);
$ok = false;
$consent = $retval[0];
if($consent["cp_consent_sign_v2"]=="1"){
    $ok = true;
}
if (!$ok) {
    echo ("Error: could not import, participant ".$participant. " does not exist\n");
    exit(1);
}



// add some counters, how many errors?
$num_attempted = 0;
$num_successful = 0;
$num_failed = 0;

$payload = array( "id_redcap" => $pGUID,
                  "redcap_event_name" => $event_name,
);

foreach ( $file_contents as $k => $entry ) {

  //print_r($k." => ".$entry." /////\n");
  // string entries are single data points about the task 
  if(is_string($entry)){

    // remove this if statement once instruments.csv is updated
    if($k == "lmt_user") break;
    $payload[$k] = $entry;
    $num_attempted++;
  }
  // the associative array 'data' containings the trials is handled here
  else{
    //for each trial aka slide recorded
    $i = 0;
    foreach ($entry as $kay => $trial) {
      // for each data point of each slide
      foreach ($trial as $key => $value) {
        $payload[$key . sprintf("_%02d", $i)] = $value;
        $num_attempted++;
      }
      $i++;
    }
  }
}
//print_r($payload);
if(1){
  $data = array(
      'token'             => $token,
      'content'           => 'record',
      'format'            => 'json',
      'type'              => 'flat',
      'overwriteBehavior' => 'normal',
      'data'              => '[' . json_encode($payload) . ']',
      'returnContent'     => 'count',
      'returnFormat'      => 'json' 
  );
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://abcd-rc.ucsd.edu/redcap/api/');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_VERBOSE, 0);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_AUTOREFERER, true);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
  $output = curl_exec($ch);
  echo($output);
  curl_close($ch);
}


// print out how many entries could be imported
echo("\n---Script Results---\nnumber of attempted entries: ".$num_attempted."\n");
//echo($num_successful." entries successfully imported. ".$num_failed." entries failed.\n");



?>
