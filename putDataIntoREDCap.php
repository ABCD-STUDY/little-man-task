<?php
// read a single json file such as data/UCSD/lmt_NDAR_INV31JTFZ92_baseline_year_1_arm_1_01.json
// usage:
//   php putDataIntoREDCap.php -f "data/UCSD/lmt_NDAR_INV31JTFZ92_baseline_year_1_arm_1_01.json"

$script_out = date("Y-m-d h:i:sa"."\n");
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

if (!is_readable("/var/www/html/applications/little-man-task/tokens.json")) {
  echo("Error: tokens not readable\n");
  exit(1);
}
$tokens = json_decode(file_get_contents("/var/www/html/applications/little-man-task/tokens.json"), true);
$token = $tokens[$file_contents['lmt_site']];

$participant = $file_contents['lmt_subject_id'];
//$event_name  = $file_contents['lmt_event_name'];
$event_name  = "baseline_year_1_arm_1";

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
    'fields' => array('enroll_total'),
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
if($retval != null){
  $enrolled = $retval[0];
  $enroll_val = $enrolled["enroll_total___1"];
  // fix: sometimes participants get the enroll ok after they get the test. We should always add them to redcap - if the participant exists
  if($enroll_val == "1" || $enroll_val == "0"){
    $ok = true;
  } else {
    $output = $output . " Error: participant does not have enroll_total___1 equal to \"1\" or \"0\"";
  }
}
if (!$ok) {
    echo ("Error: could not import, participant ".$participant. " does not exist redcap returned : \"".$output."\"\n");
    exit(1);
}

$num_attempted = 0;

$payload = array( "id_redcap" => $pGUID,
                  "redcap_event_name" => $event_name
);

foreach ( $file_contents as $k => $entry ) {

  // string entries are single data points about the task 
  if(is_string($entry)){
    // ignore these keys, do not add to payload
    if($k == "lmt_user"){}
    else if($k == "lmt_site"){}
    else{
      $payload[$k] = $entry;
      $num_attempted++;
    }
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
// now deriving scores for separate form 
$data = $file_contents['data'];

// % correct of trials
// avg reaction time
$num_correct = 0.0;
$num_wrong = 0.0;
$reaction_sum = 0.0;
$react_correct = 0.0;
$react_wrong = 0.0;
$num_timed_out = 0.0;
foreach($data as $trial){
  // find all actual trial records
  if($trial["lmt_is_data_element"] == true){

    //check if user timed-out on this trial, rt will be -1
    if($trial["lmt_rt"] >= 0){
      $reaction_sum += $trial["lmt_rt"];
      // correct/incorrect
      if($trial["lmt_correct"] == true){
        $num_correct++;
        $react_correct += $trial["lmt_rt"];
      }
      else{
        $num_wrong++;
        $react_wrong += $trial["lmt_rt"];
      }
    }
    // if timed-out
    else{
      $num_timed_out++;
    }
  }
}

//finish derived scores
$totaltrials = 32;
$num_of_trials = $num_wrong + $num_correct;
$percent_correct = "nan";
if (isset($totaltrials) && $totaltrials > 0) {
  $percent_correct = $num_correct / $totaltrials;
}
$percent_wrong = ($num_wrong +$num_timed_out) / $totaltrials;
$average_reaction = "nan";
if (isset($num_of_trials) && $num_of_trials > 0) {
  $average_reaction = $reaction_sum / $num_of_trials;
}
$average_reaction_correct = "nan";
if (isset($num_correct) && $num_correct > 0) {
  $average_reaction_correct = $react_correct / $num_correct;
}

$average_reaction_wrong = "nan";
if (isset($num_wrong) && $num_wrong > 0) {
  $average_reaction_wrong = $react_wrong / $num_wrong;
}

$efficiency = "nan";
if (isset($average_reaction_correct) && 
    $average_reaction_correct != "nan" && 
    $average_reaction_correct > 0) {
  $efficiency = $percent_correct/ $average_reaction_correct;
}
$payload = array_merge($payload,array(
"lmt_scr_perc_correct"=>$percent_correct,
"lmt_scr_perc_wrong"=>$percent_wrong,
"lmt_scr_num_correct"=>$num_correct,
"lmt_scr_num_wrong"=>$num_wrong,
"lmt_scr_num_timed_out"=>$num_timed_out,
"lmt_scr_avg_rt"=>$average_reaction,
"lmt_scr_rt_correct"=>$average_reaction_correct,
"lmt_scr_rt_wrong"=>$average_reaction_wrong,
"lmt_scr_efficiency"=>$efficiency,
"lmt_scr_assess_date"=>$file_contents['lmt_assessment_date'],
"lmt_scr_run"=>$file_contents['lmt_run']
));
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
  $filename = substr($options['f'], strrpos($options['f'],'/') + 1 );
  $script_out.=$filename."\n";
  print_r($filename.$output."\n");
  // fill import status on redcap
  $ret = json_decode($output, true);
  if ($output == "{\"count\": 1}") { // a single entry could be added, we should be ok here
      // leave a trace in redcap that we did or did not import
      // set lmt_import_ok to 1
      // leave lmt_import_notes emptry
      $payload = array( "id_redcap" => $pGUID,
			"redcap_event_name" => $event_name,
			"lmt_import_ok" => "ok",
			"lmt_import_notes" => $output,
                        "little_man_task_score_complete" => 2,
                        "little_man_task_daic_use_only_complete" => 2
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
      $secondoutput = curl_exec($ch);
      $script_out.="import ok: ";
      //rename($options['f'], "data_import_archive/".$filename);
  } else {
      // set lmt_import_ok to 0
      // add $output to lmt_import_notes 
      $payload = array( "id_redcap" => $pGUID, "redcap_event_name" => $event_name, "lmt_import_ok" => 0, "lmt_import_notes" => $output );
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
      $secondoutput = curl_exec($ch);
      $script_out .= "import not ok: ";
  }
  $script_out.=$output;
  curl_close($ch);
}

// print out how many entries could be imported
$script_out.="\nnumber of attempted entries: ".$num_attempted."\n\n";
sleep(2);
return $script_out;

?>
