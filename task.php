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

?>

<!doctype html>
<html>

  <head>
    <title>Little Man Task</title>
    <!-- Load jQuery -->
    <script src="js/jquery.min.js"></script>
    <!-- Load the jspsych library and plugins -->
    <script src="js/jspsych-5.0.3/jspsych.js"></script>
    <script src="js/jspsych-5.0.3/plugins/jspsych-text.js"></script>
    <script src="js/jspsych-5.0.3/plugins/jspsych-single-stim.js"></script>
    <!-- Load the stylesheet -->
    <!-- <link href="experiment.css" type="text/css" rel="stylesheet"></link> -->
    <link href="js/jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>

  </head>

  <body>
    <!-- added a background color to match the images seamlessly -->
    <body style="background-color: #555555 ;">
    <div id="jspsych_target"></div>
  </body>

  <script>
    // Experiment parameters (instruction slide gives 32 as n_trials)
    var n_trials = 6;

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
    var welcome_message = "<images src='images/Instructions.png'>";

    var instructions = "<div id='instructions'><p>Now you will begin " +
    	"the actual program.</p><p>Press enter to start.</p>";

    var instructions_EX = "<div id='instructions'><p>You will see a " +
	"series of images that look similar to this:</p><p>" +
	"<images src='images/1.png'></p><p>Press the arrow " +
	"key that corresponds to which hand the little man is holding " +
	"his briefcase in. For example, in this case you would press " +
	"the left arrow key.</p><p>The first four images are practice " + 
	"Press enter to start.</p>";

    var debrief = "<div id='instructions'><p>Thank you for " +
	  "participating! Press enter to see the data.</p></div>";

    var EX_1 = {
	type: 'single-stim',
	choices: [37, 39],
	timing_post_trial: 2500,
	data: {stimulus_type: 'left'},
	stimulus: 'images/EX 1.png',
	on_finish: function(data){
		//label data as example
		jsPsych.data.addDataToLastTrial({ignore: true});
		//labal data as correct or not.
	    	var correct = false;

	   	if(data.stimulus_type == 'left' && data.key_press == 37){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 39){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	},
	//this is the broken section
	timeline: [{ timeline: [ {stimulus: 'images/EX 1 C.png', on_finish: function(){ jsPsych.endCurrentTimeline(); }},
				 {stimulus: 'images/EX 1 W.png'}  
			       ],

		    conditional_function: function(){
			var data = jsPsych.data.getLastTrialData();
			if(data.correct == true){
				return false;
			} else {
				return true;
			}
		    }
		    }
        ]

    }

    //generating an array of non-random images
    i = 0;
    var test_trials= []; 
    for (var i = 0; i < 32 ; i++) { //32 is number of images in the folder
	test_trials.push({stimulus: stimuli[i],data: {stimulus_type: stimuli_types[i]}});
	
    }
    // using jspsych to randomize given images into array of size n_trials defined at top of file
    var test_trial_array = jsPsych.randomization.sample(test_trials, n_trials, false);


    //main test trials are defined by this trial object. 
    var test_block_main = {
	type: 'single-stim',
	choices: [37, 39],
	timing_post_trial: 2500,
	timeline: test_trial_array,
    	//added a function to record correct or not
        on_finish: function(data){
		//labal data as correct or not.
	    	var correct = false;
	   	if(data.stimulus_type == 'left' && data.key_press == 37){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 39){
	      		correct = true;
	  	}
	   	jsPsych.data.addDataToLastTrial({correct: correct});
	}
    }

    // Define first instruction block which uses instruction image
    var instruction_block = {
	  type: 'single-stim',
	  stimulus: 'images/Instructions.png',
	  timing_post_trial: 2500,
	  //adding ignore label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({ignore: true});
	  },
    };

    var debrief_block = {
	  type: "text",
	  text: [debrief],
	  //adding ignore label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({ignore: true});
	  },
    };

    jsPsych.init({
	  display_element: $('#jspsych_target'),
	  //order of experiment includes an example section
	  timeline: [instruction_block, EX_1, 
	  			 instruction_block,
	  			 test_block_main, debrief_block],

	  on_finish: function(data) {
	      	//call from tutorial displays JSON string as final page
   		jsPsych.data.displayData();
	  }
    });
</script>
</html>
    
