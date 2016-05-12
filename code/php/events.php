<?php

  session_start(); /// initialize session, we will use session variables to store the subjid

  include($_SERVER["DOCUMENT_ROOT"]."/code/php/AC.php");
  $user_name = check_logged(); /// function checks if user is logged in

  if (!$user_name || $user_name == "") {
     echo (json_encode ( array( "message" => "no user name", "ok" => "0" ) ) );
     return; // nothing
  }

  $permissions = list_permissions_for_user( $user_name );

  // find the first permission that corresponds to a site
  // Assumption here is that a user can only add assessment for the first site he has permissions for!
  $site = "";
  foreach ($permissions as $per) {
     $a = explode("Site", $per); // permissions should be structured as "Site<site name>"

     if (count($a) > 0) {
        $site = $a[1];
	break;
     }
  }
  if ($site == "") {
     echo (json_encode ( array( "message" => "Error: no site assigned to this user", "ok" => 0 ) ) );
     return;
  }

  // Both the subject id and the visit (session) are used to make the assessment unique
   $subjid = "";
   $sessionid = "";
   $active_substances = array();
   if ( isset($_SESSION['ABCD']) && isset($_SESSION['ABCD']['little-man-task']) ) {
      if (isset($_SESSION['ABCD']['little-man-task']['subjid'])) {  
         $subjid  = $_SESSION['ABCD']['little-man-task']['subjid'];
      }
      if (isset($_SESSION['ABCD']['little-man-task']['sessionid'])) {
         $sessionid  = $_SESSION['ABCD']['little-man-task']['sessionid'];
      }      
   }
   if ($subjid == "") {
     echo(json_encode ( array( "message" => "Error: no subject id assigned", "ok" => "0" ) ) );
     return;
   }
   if ($sessionid == "") {
     echo(json_encode ( array( "message" => "Error: no session specified", "ok" => "0" ) ) );
     return;
   }
  $action = "save";
  if (isset($_POST['action'])) {
    $action = $_POST['action'];
  }

  // this event will be saved at this location
  $events_file = $_SERVER['DOCUMENT_ROOT']."/applications/little-man-task/data/" . $site . "/lmt_".$subjid."_".$sessionid.".json";

  if ($action == "test") {
     // test if the current file exists already
     if (file_exists($events_file)) {
       echo(json_encode ( array( "message" => "Error: this session already exists, overwrite session is not possible", "ok" => "0" ) ) );
       return;
     }
  }

  //if (file_exists($events_file)) {
  //   echo(json_encode ( array( "message" => "Error: this session already exists, overwrite session is not possible", "ok" => "0" ) ) );
  //   return;
  //}
  
  $ar = array( "data" => [], "lmt_serverDate" => date("Y/m/d"), "lmt_serverTime" => date("h:i:sa"), "lmt_site" => $site, "lmt_subjectid" => $subjid, "lmt_session" => $sessionid );
  if (isset($_POST['data'])) {
     $ar['data'] = json_decode($_POST['data'], true);
  }
  if (isset($_POST['date'])) {
     $ar['lmt_assessmentDate'] = $_POST['date'];
  }
  file_put_contents($events_file, json_encode( $ar, JSON_PRETTY_PRINT ));
  echo(json_encode ( array( "message" => "Saved session", "ok" => "1" ) ) );
?>
