<?php
  session_start();

  include($_SERVER["DOCUMENT_ROOT"]."/code/php/AC.php");
  $user_name = check_logged(); /// function checks if visitor is logged.
  $admin = false;

  if ($user_name == "") {
    // user is not logged in

  } else {
    $admin = true;
    echo('<script type="text/javascript"> user_name = "'.$user_name.'"; </script>'."\n");
    echo('<script type="text/javascript"> admin = '.($admin?"true":"false").'; </script>'."\n");
  }

  $subjid = "";
  $sessionid = "";
  $run = "";
  if( isset($_SESSION['ABCD']) && isset($_SESSION['ABCD']['little-man-task']) ) {
     if (isset($_SESSION['ABCD']['little-man-task']['subjid'])) {
        $subjid  = $_SESSION['ABCD']['little-man-task']['subjid'];
     }
     if (isset($_SESSION['ABCD']['little-man-task']['sessionid'])) {
        $sessionid  = $_SESSION['ABCD']['little-man-task']['sessionid'];
     }
     if (isset($_SESSION['ABCD']['little-man-task']['run'])) {
        $run  = $_SESSION['ABCD']['little-man-task']['run'];
     }
  }
  echo('<script type="text/javascript"> SubjectID = "'.$subjid.'"; </script>'."\n");
  echo('<script type="text/javascript"> Session = "'.$sessionid.'"; </script>'."\n");
  echo('<script type="text/javascript"> Run = "'.$run.'"; </script>'."\n");

   $permissions = list_permissions_for_user( $user_name );

   $site = "";
   foreach ($permissions as $per) {
     $a = explode("Site", $per); // permissions should be structured as "Site<site name>"

     if (count($a) > 0) {
        $site = $a[1];
	break;
     }
   }
   if ($site == "") {
     echo (json_encode ( array( "message" => "Error: no site assigned to this user" ) ) );
     return;
   }
   echo('<script type="text/javascript"> Site = "'.$site.'"; </script>'."\n");

?>

<!doctype html>
<html>

  <head>
    <title>Little Man Task</title>
    <!-- Load jQuery -->
    <script src="js/jquery.min.js"></script>
    <!-- Load the jspsych library and plugins -->
    <script src="js/jspsych/jspsych.js"></script>
    <script src="js/jspsych/plugins/jspsych-text.js"></script>
    <script src="js/jspsych/plugins/jspsych-single-stim.js"></script>
    <script src="js/jspsych/plugins/jspsych-button-response.js"></script>
    <script src="js/moment.min.js"></script>
    <!-- Load the stylesheet -->
    <!-- <link href="experiment.css" type="text/css" rel="stylesheet"></link> -->
    <link href="js/jspsych/css/jspsych.css" rel="stylesheet" type="text/css"></link>
    <style>

p {
   color: rgb(85,255,85);
   font-family: sans-serif;
   font-weight: normal;
   font-size: 36px;
   line-height: 48px;
   text-indent: 48px;
   margin: 0;
}

.jspsych-btn {
  position: absolute;
  border-radius: 50px;
  bottom: 40px;
  
}
#jspsych-button-response-button-0 {
  width: 100px;
  height: 100px;
  left: 30%;
}
#jspsych-button-response-button-1 {
  width: 100px;
  height: 100px;
  right: 30%;
  
}
.block-center {
  width: 100%;
}
    </style>
  </head>

  <body>
    <!-- added a background color to match the images seamlessly -->
    <body style="background-color: #555555 ;">
    <div id="jspsych_target"></div>
  </body>

  <script>

function exportToCsv(filename, rows) {
    var k = { "SubjectID": 1, "Site": 1, "Session": 1, "Run": 1 };
    for (var i = 0; i < rows.length; i++) {
       var k2 = Object.keys(rows[i]);
       for (var j = 0; j < k2.length; j++) {
          k[k2[j]] = 1;
       } 
    }
    k = Object.keys(k);

    var csvFile = k.join(",") + "\n";
    for (var i = 0; i < rows.length; i++) {
       rows[i]['SubjectID'] = SubjectID;
       rows[i]['Site'] = Site;
       rows[i]['Session'] = Session;
       rows[i]['Run'] = Run;
       csvFile += k.map(function(a) { return rows[i][a] }).join(",") + "\n";
    }
    
    var blob = new Blob([csvFile], { type: 'text/csv;charset=utf-8;' });
    if (navigator.msSaveBlob) { // IE 10+
	navigator.msSaveBlob(blob, filename);
    } else {
	var link = document.createElement("a");
	if (link.download !== undefined) { // feature detection
	    // Browsers that support HTML5 download attribute
	    var url = URL.createObjectURL(blob);
	    link.setAttribute("href", url);
	    link.setAttribute("download", filename);
	    link.style.visibility = 'hidden';
	    document.body.appendChild(link);
	    link.click();
	    document.body.removeChild(link);
	}
    }
}



    var post_trial_gap = function() {
        return Math.floor( Math.random() * 1000 ) + 500;
    }

    // for a touch screen we do not have an enter button (this simulates enter on keydown)
    jQuery('body').on('touchstart', function() { var e = jQuery.Event('keydown'); e.which = 13; jQuery('body').trigger(e); });

    //arrays of all available images and L/R value are manually paired by index for later use
    var stimuli=["images/1.png", "images/2.png", "images/3.png", "images/4.png", 
    		"images/5.png", "images/6.png", "images/7.png", "images/8.png", 
		"images/9.png", "images/10.png", "images/11.png", "images/12.png", 
		"images/13.png", "images/14.png", "images/15.png", "images/16.png", 
		"images/17.png", "images/18.png", "images/19.png", "images/20.png",
		"images/21.png", "images/22.png", "images/23.png", "images/24.png",
		"images/25.png", "images/26.png", "images/27.png", "images/28.png", 
		"images/29.png", "images/30.png", "images/31.png", "images/32.png", 
		];
    //jsPsych.preloadImages(stimuli, function(){ startExperiment(); });
    var stimuli_types = ["left","right","left","right","right","left",
    			"right","left","right","right","left","left",
			"right","left","left","right","right","left",
			"left","right","left","right","right","left",
			"left","right","right","right","left","left",
			"right","left",
			];

    var correct_wrong = ["images/EX 1 C.png", "images/EX 2 C.png", "images/EX 3 C.png", "images/EX 4 C.png",
	   		 "images/EX 1 W.png", "images/EX 2 W.png", "images/EX 3 W.png", "images/EX 4 W.png",
			 "images/instruction3.png", "images/instruction2.png","images/instruction_example.png",
		 	 "images/Instructions.png", "images/omission.png"];

    // Example slides
    var stimuli_EX =["images/EX 1.png", "images/EX 2.png", "images/EX 3.png", "images/EX 4.png"];

    var stimuli_types_EX = ["left", "left", "right", "right"];

    // Experiment Instructions
 
    var instruct1 = "<div id='instructions'><p><br/>We're now going to play a game with a Little Man on " +
	    "the screen. But first we are going to learn a few things: </br></br>Always use your index finger " +
	    "of your dominant hand.</br></br> You will be asked to indicate whether the Little Man is holding " +
	    "the object in his right or left hand.</br> If the object is on his left hand press left.</br>"+
       	    "If the object is on his right hand press right.</br></br> This is your home base. You will place your "+ 
	    "index finger on home base after each response you make. </br></br>You should answer as quickly and "+
	    "as accurately as possible.</p></div>";

    var instruct2 = "<div id='instructions'><p><br/>Below you can see a drawing of a Little Man.<br/>" +
	    "The Little Man might be facing you or facing away from you. He might also be up-side down. "+
	    "Please indicate in which hand the Little Man is holding the object.<br/>" +
	    "</p><p><center><img src='images/examplepic.png'></center><br/></p></div>";

    var instruct3 = "<div id='instructions'><p><br/>Below you can see a drawing of a Little Man.<br/>" +
	    "The Little Man might be facing you or facing away from you. He might also be up-side down. "+
	    "Please indicate in which hand the Little Man is holding the object.<br/>" +
	    "</p><center><img src='images/examplepic.png'></center><p>" +
	    "<br/>In this case he is holding the object in his right hand.</p></div>";

    var instructMain = "<div id='instructions'><p><br/>Good, you have demonstrated that you understand the instructions and know what to do for this test.<br/></br>" +
	    "Use the index finger of your dominant hand to press the buttons and then place your finger on Home Base and wait for the next picture.</br></br>"+
	    "You will now have 32 test trials, just like the practice trials, but you will not be told whether your answer is correct.<br/>" +
	    "<br/>Remember, you should answer each problem as quickly and as accurately as possible. Are you ready?</p></div>";

    var debrief = "<div id='instructions'><p>Thank you for " +
	    "participating! Press enter to see the data.</p></div>";

    // line for mouse forward function
    //jQuery('body').on('touchstart', function() { jQuery('#inst').click(); jQuery('#instructions').click(); });

    var EX_1 = {
	type: 'button-response',
	choices: ['left', 'right'],
	timing_post_trial: 0,
	data: {stimulus_type: 'left'},
	stimulus: 'images/EX 1.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({is_data_element: false});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.button_pressed == 0){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.button_pressed == 1){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}

     }
     var EX_1_C = {
	     //attempting a multiple if system
 	type: 'button-response',
	choices: ['next'],
	is_html: true,
	timing_post_trial: 0,
	timeline: [{ stimulus: "<div id='instructions'><center><img src='images/EX 1.png'></center>" +
	    "<p><br/>Correct</p></div>",
		     
		     on_finish: function(data){
			jsPsych.data.addDataToLastTrial({is_data_element: false});
		     	jsPsych.data.addDataToLastTrial({skipped:true});}
		  }],

	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			if(data.correct == false){
				return false;
			} else {
				return true;
			}
	}
    }
        
    var EX_1_W = {
	//second if should only trigger if previous did not
	type: 'button-response',
	choices: ['next'],
	is_html: true,
	timing_post_trial: 0,
	timeline: [{ stimulus: "<div id='instructions'><center><img src='images/EX 1.png'></center>" +
	    "<p><br/>Wrong</p></div>",
		     
		     data: {is_data_element: false},
		  }],


	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			if(data.skipped == true){
				return false;
			} else {
				return true;
			}
	}

    }
    var EX_2 = {
	type: 'button-response',
	choices: ['left', 'right'],
	timing_post_trial: 0,
	data: {stimulus_type: 'left'},
	stimulus: 'images/EX 2.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({is_data_element: false});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.button_pressed == 0){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.button_pressed == 1){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}

     }
     var EX_2_C = {
	//attempting a multiple if system
	type: 'button-response',
	choices: ['next'],
	is_html: true,
	timing_post_trial: 0,
	timeline: [{ stimulus: "<div id='instructions'><center><img src='images/EX 2.png'></center>" +
	    "<p><br/>Correct</p></div>",
	             
		     on_finish: function(data){
			jsPsych.data.addDataToLastTrial({is_data_element: false});
		     	jsPsych.data.addDataToLastTrial({skipped:true});}
		  }],

	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			if(data.correct == false){
				return false;
			} else {
				return true;
			}
	}
    }
        
    var EX_2_W = {
	//second if should only trigger if previous did not
	type: 'button-response',
	choices: ['next'],
	is_html: true,
	timing_post_trial: 0,
	timeline: [{ stimulus: "<div id='instructions'><center><img src='images/EX 2.png'></center>" +
	    "<p><br/>Wrong</p></div>",
		     
		     data: {is_data_element: false},
		  }],


	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			if(data.skipped == true){
				return false;
			} else {
				return true;
			}
	}

    }
    var EX_3 = {
	type: 'button-response',
	choices: ['left', 'right'],
	timing_post_trial: 0,
	data: {stimulus_type: 'right'},
	stimulus: 'images/EX 3.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({is_data_element: false});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.button_pressed == 0){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.button_pressed == 1){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}

     }
     var EX_3_C = {
	//attempting a multiple if system
	type: 'button-response',
	choices: ['next'],
	is_html: true,
	timing_post_trial: 0,
	timeline: [{ stimulus: "<div id='instructions'><center><img src='images/EX 3.png'></center>" +
	    "<p><br/>Correct</p></div>",
	             
		     on_finish: function(data){
			jsPsych.data.addDataToLastTrial({is_data_element: false});
		     	jsPsych.data.addDataToLastTrial({skipped:true});}
		  }],

	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			if(data.correct == false){
				return false;
			} else {
				return true;
			}
	}
    }
        
    var EX_3_W = {
	//second if should only trigger if previous did not
	type: 'button-response',
	choices: ['next'],
	is_html: true,
	timing_post_trial: 0,
	timeline: [{ stimulus: "<div id='instructions'><center><img src='images/EX 3.png'></center>" +
	    "<p><br/>Wrong</p></div>",
		     
		     data: {is_data_element: false},
		  }],

	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			if(data.skipped == true){
				return false;
			} else {
				return true;
			}
	}

    }
    var EX_4 = {
	type: 'button-response',
	choices: ['left', 'right'],
	timing_post_trial: 0,
	data: {stimulus_type: 'right'},
	stimulus: 'images/EX 4.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({is_data_element: false});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.button_pressed == 0){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.button_pressed == 1){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}

    }
    var EX_4_C = {
	//attempting a multiple if system
	type: 'button-response',
	choices: ['next'],
	is_html: true,
	timing_post_trial: 0,
	timeline: [{ stimulus: "<div id='instructions'><center><img src='images/EX 4.png'></center>" +
	    "<p><br/>Correct</p></div>",
		     
		     on_finish: function(data){
			jsPsych.data.addDataToLastTrial({is_data_element: false});
		     	jsPsych.data.addDataToLastTrial({skipped:true});}
		  }],

	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			if(data.correct == false){
				return false;
			} else {
				return true;
			}
	}
    }
        
    var EX_4_W = {
	//second if should only trigger if previous did not
	type: 'button-response',
	choices: ['next'],
	is_html: true,
	timing_post_trial: 0,
	timeline: [{ stimulus: "<div id='instructions'><center><img src='images/EX 4.png'></center>" +
	    "<p><br/>Wrong</p></div>",	
		     
		     data: {is_data_element: false},
		  }],

	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			if(data.skipped == true){
				return false;
			} else {
				return true;
			}
	}

    }

    //generating an array of non-random images
    var test_trials= []; 
    for (var i = 0; i < 32 ; i++) { //32 is number of images in the folder
	test_trials.push({stimulus: stimuli[i],
			  data: {stimulus_type: stimuli_types[i]},
			  timing_response: 5000 } // 5 second repsonse time
			  
			);
	
    }

    //var omission_message = "<div><p></br>You have exceeded the maximum response time."+
    //	   " Please respond quickly and accurately.<br/><br/><br/>The next image will appear</p></div>";
    var omission_message = "<div><p></br>Sorry, but you have taken too long to respond. Be sure to be as quick, but accurate, as possible.</p></div>";
    // Omission trials after each test stimulus slide
    var omission = {
	//attempting a multiple if system
	type: 'single-stim',
	is_html: true,
	timeline: [{ stimulus: omission_message,
		     timing_response: 6000,
		     on_finish: function(data)
		     	{jsPsych.data.addDataToLastTrial({is_data_element: false});}
		  }],

	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			// response set to -1 if no response
			if(data.button_pressed == -1){
				return true;
			} else {
				return false;
			}
	}
    }

    // placing omission slide after each test slide
    var test_trial_array = [];
    for(var i = 0; i < 64; i++){
    	// every other slide is omission slide
	if(i%2 == 0 ){
		test_trial_array.push(test_trials[i/2]);
	}
	else{
		test_trial_array.push(omission);
	}
    }

    //main test trials are defined by this trial object. 
    var test_block_main = {
	type: 'button-response',
	choices: ['left', 'right'],
	timing_post_trial: post_trial_gap,
	timeline: test_trial_array,
    	//added a function to record correct or not
        on_finish: function(data){
		jsPsych.data.addDataToLastTrial({is_data_element: true});
		//labal data as correct or not.
	    	var correct = false;
	   	if(data.stimulus_type == 'left' && data.button_pressed == 0){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.button_pressed == 1){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}
    }

    // Define second instruction block which uses instruction image
    var second_instruction_block = {
	  is_html: true,
	  type: 'button-response',
	  choices: ['next'],
	  timing_post_trial: 1000,
	  stimulus: instructMain,
	  //adding is_data_element label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({is_data_element: false});
	  },
    };
    // Define first instruction block which uses instruction image
    var first_instruction_block = {
	  type: 'button-response',
	  is_html: true,
	  choices: ['next'],
	  timeline: [{stimulus: instruct1},
		     	 {stimulus: instruct2},
		     	 {stimulus: instruct3}],
	  timing_post_trial: 0,
	  //adding is_data_element label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({is_data_element: false});
	  },
    };
    var debrief_block = {
	  type: 'button-response',
	  is_html: true,
	  choices: ['next'],
	  stimulus: debrief,
	  //adding is_data_element label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({is_data_element: false});
	  },
    };

    //preload all images used
    jsPsych.pluginAPI.preloadImages(stimuli, function(){preloadAll();});
    function preloadAll(){
    	jsPsych.pluginAPI.preloadImages(correct_wrong);
    	jsPsych.pluginAPI.preloadImages(stimuli_EX, function(){ startExperiment(); });
    }

function startExperiment(){
    jsPsych.init({
	  display_element: $('#jspsych_target'),
	  //order of experiment includes an example section
	  timeline: [first_instruction_block,
	  			 EX_1, EX_1_C, EX_1_W, 
	  			 EX_2, EX_2_C, EX_2_W, 
	  			 EX_3, EX_3_C, EX_3_W, 
	  			 EX_4, EX_4_C, EX_4_W, 
	  			 second_instruction_block,
	  			 test_block_main, debrief_block],
	  
	  on_finish: function(data) {
	      // call from tutorial displays JSON string as final page
   	      // jsPsych.data.displayData();

	      // make this result code specific to lmt_
	      ud = makeUnique( jsPsych.data.getData(), 'lmt_' );

	      jQuery.post('code/php/events.php', { "data": JSON.stringify(ud), "date": moment().format() }, function(data) {
                  // did it work?
                  console.log(data);
		  if (data.ok == 0) {
		     alert('Error: ' + data.message);
		  }
                  // export now
                  exportToCsv("Little-Man-Task_" + Site + "_" + SubjectID + "_" + Session + "_" + moment().format() + ".csv", ud);

                  // we should remove this as an active session now... 
	      }, 'json');


	  }
    });

}

function makeUnique( data, prefix ) {
    
    var build, key, destKey, value;
    
    build = {};
    if (typeof data === "object") {
	if (data instanceof Array) { // don't change the array, only traverse
	    for (var i = 0; i < data.length; i++) {
		data[i] = makeUnique(data[i], prefix);
	    }
	    return data;
	} else {
	    for (key in data) {
		// Get the destination key
		destKey = prefix + key;

		// Get the value
		value = data[key];

		value = makeUnique(value, prefix);

		build[destKey] = value;
	    }
	}	
    } else {
	return data;
    }

    return build;
}
</script>
    </html>
    
