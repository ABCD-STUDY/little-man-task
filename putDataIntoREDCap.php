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

$data = json_decode(file_get_contents($options['f']),true);

$token = "";
$tokens = json_decode(file_get_contents("tokens.json"), true);
$token = $tokens[$data['lmt_site']];
// put your token in here
$token = "LSKDJFL:SKJDF:LJSDFLJ";

$participant = $data['lmt_subject_id'];
$event_name  = $data['lmt_event_name'];
$pGUID = $participant;

// send over one data set
// create loop over all data entries in $data['data']

// add tests for subject does not exist,
// copy values after successful import to data-import-archive and data-import-fail


foreach ( $data['data'] as $entry ) {

  $payload = array( "id_redcap" => $pGUID,
  "redcap_event_name" => $event_name,
  //"mrif_score" => $score,
  //"mrif_hydrocephalus" => $hydrocephalus,
  //"mrif_herniation" => $herniation,
  //"mrif_other_notes" => $other_notes,
  //"mrif_score_copy" => $score,
  //"mrif_hydrocephalus_copy" => $hydrocephalus,
  //"mrif_herniation_copy" => $herniation,
  //"mrif_other_notes_copy" => $other_notes,
  //"mrif_scan_read_dte" => date("Y-m-d"));
  //if ($score > 2) {
      // also mark the date a first note was send
      //$payload["mrif_not_dte"] = date("Y-m-d");
  //}
  );
  
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
  curl_close($ch);
}




?>
