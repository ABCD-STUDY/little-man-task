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
  if( isset($_SESSION['ABCD']) && isset($_SESSION['ABCD']['little-man-task']) ) {
     if (isset($_SESSION['ABCD']['little-man-task']['subjid'])) {
        $subjid  = $_SESSION['ABCD']['little-man-task']['subjid'];
     }
     if (isset($_SESSION['ABCD']['little-man-task']['sessionid'])) {
        $sessionid  = $_SESSION['ABCD']['little-man-task']['sessionid'];
     }
  }
  echo('<script type="text/javascript"> SubjectID = "'.$subjid.'"; </script>'."\n");
  echo('<script type="text/javascript"> Session = "'.$sessionid.'"; </script>'."\n");

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
    <script src="js/moment.min.js"></script>
    <!-- Load the stylesheet -->
    <!-- <link href="experiment.css" type="text/css" rel="stylesheet"></link> -->
    <link href="js/jspsych/css/jspsych.css" rel="stylesheet" type="text/css"></link>

  </head>

  <body>
    <!-- added a background color to match the images seamlessly -->
    <body style="background-color: #555555 ;">
    <div id="jspsych_target"></div>
  </body>

  <script>

function exportToCsv(filename, rows) {
    var k = { "SubjectID": 1, "Site": 1, "Session": 1 };
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
    //jQuery('body').on('touchstart', function() { var e = jQuery.Event('keydown'); e.which = 13; jQuery('body').trigger(e); });

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

    var stimuli_types = ["left","right","left","right","right","left",
    			"right","left","right","right","left","left",
			"right","left","left","right","right","left",
			"left","right","left","right","right","left",
			"left","right","right","right","left","left",
			"right","left",
			];


    // Example slides
    var stimuli_EX =["images/EX 1.png", "images/EX 2.png", "images/EX 3.png", "images/EX 4.png"];

    var stimuli_types_EX = ["left", "left", "right", "right"];

    // Experiment Instructions
    var welcome_message = "<images id='instructions' src='images/Instructions.png'>";

    var instructions = "<div id='instructions'><p>Now you will begin " +
    	"the actual program.</p><p>Press enter to start.</p>";

    var instructions_EX = "<div id='instructions'><p>You will see a " +
	"series of images that look similar to this:</p><p>" +
	"<images src='images/1.png'></p><p>Press key '8' " +
	"if the little man is holding the case left, key '9' if the little man is holding " +
	"his briefcase in in his right hand. For example, in this case you would press " +
	"the key '8' (left hand).</p><p>The first four images are practice " + 
	"Press enter to start.</p>";

    var debrief = "<div id='instructions'><p>Thank you for " +
	  "participating! Press enter to see the data.</p></div>";

    var EX_1 = {
	type: 'single-stim',
	choices: [56, 57],
	//timing_post_trial: post_trial_gap,
	data: {stimulus_type: 'left'},
	stimulus: 'images/EX 1.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({is_data_element: false});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.key_press == 56){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 57){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}

     }
     var EX_1_C = {
	//attempting a multiple if system
	type: 'single-stim',
	timeline: [{ stimulus: 'images/EX 1 C.png', 
		     timing_response: 3000,
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
	type: 'single-stim',
	timeline: [{ stimulus: 'images/EX 1 W.png',
		     timing_response: 3000,
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
	type: 'single-stim',
	choices: [56, 57],
	//timing_post_trial: post_trial_gap,
	data: {stimulus_type: 'left'},
	stimulus: 'images/EX 2.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({is_data_element: false});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.key_press == 56){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 57){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}

     }
     var EX_2_C = {
	//attempting a multiple if system
	type: 'single-stim',
	timeline: [{ stimulus: 'images/EX 2 C.png', 
	             timing_response: 3000,
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
	type: 'single-stim',
	timeline: [{ stimulus: 'images/EX 2 W.png',
		     timing_response: 3000,
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
	type: 'single-stim',
	choices: [56, 57],
	//timing_post_trial: post_trial_gap,
	data: {stimulus_type: 'right'},
	stimulus: 'images/EX 3.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({is_data_element: false});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.key_press == 56){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 57){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}

     }
     var EX_3_C = {
	//attempting a multiple if system
	type: 'single-stim',
	timeline: [{ stimulus: 'images/EX 3 C.png', 
	             timing_response: 3000,
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
	type: 'single-stim',
	timeline: [{ stimulus: 'images/EX 3 W.png',
		     timing_response: 3000,
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
	type: 'single-stim',
	choices: [56, 57],
	//timing_post_trial: post_trial_gap,
	data: {stimulus_type: 'right'},
	stimulus: 'images/EX 4.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({is_data_element: false});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.key_press == 56){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 57){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}

     }
     var EX_4_C = {
	//attempting a multiple if system
	type: 'single-stim',
	timeline: [{ stimulus: 'images/EX 4 C.png', 
		     timing_response: 3000,
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
	type: 'single-stim',
	timeline: [{ stimulus: 'images/EX 4 W.png',	
		     timing_response: 3000,
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

    // Omission trials after each test stimulus slide
    var omission = {
	//attempting a multiple if system
	type: 'single-stim',
	timeline: [{ stimulus: 'images/omission.png',
		     timing_response: 6000,
		     on_finish: function(data)
		     	{jsPsych.data.addDataToLastTrial({is_data_element: false});}
		  }],

	conditional_function: function(){
		var data = jsPsych.data.getLastTrialData();
			// response set to -1 if no response
			if(data.key_press == -1){
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
	type: 'single-stim',
	choices: [56, 57],
	timing_post_trial: post_trial_gap,
	timeline: test_trial_array,
    	//added a function to record correct or not
        on_finish: function(data){
		jsPsych.data.addDataToLastTrial({is_data_element: true});
		//labal data as correct or not.
	    	var correct = false;
	   	if(data.stimulus_type == 'left' && data.key_press == 56){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 57){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}
    }

    // Define second instruction block which uses instruction image
    var second_instruction_block = {
	  type: 'single-stim',
	  stimulus: 'images/Instructions.png',
	  timing_post_trial: 1000,
	  //adding is_data_element label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({is_data_element: false});
	  },
    };
    // Define first instruction block which uses instruction image
    var first_instruction_block = {
	  type: 'single-stim',
	  stimulus: 'images/instruction_example.png',
	  timing_post_trial: 1000,
	  //adding is_data_element label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({is_data_element: false});
	  },
    };
    var debrief_block = {
	  type: "text",
	  text: [debrief],
	  //adding is_data_element label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({is_data_element: false});
	  },
    };

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

	      jQuery.post('code/php/events.php', { "data": JSON.stringify(jsPsych.data.getData()), "date": moment().format() }, function(data) {
                  // did it work?
                  console.log(data);
		  if (data.ok == 0) {
		     alert('Error: ' + data.message);
		  }
                  // export now
                  exportToCsv("Little-Man-Task_" + Site + "_" + SubjectID + "_" + Session + "_" + moment().format() + ".csv", jsPsych.data.getData());

                  // we should remove this as an active session now... 
	      });


	  }
    });
</script>
</html>
    
