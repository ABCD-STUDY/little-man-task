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
    <!-- Load the stylesheet -->
    <!-- <link href="experiment.css" type="text/css" rel="stylesheet"></link> -->
    <link href="js/jspsych/css/jspsych.css" rel="stylesheet" type="text/css"></link>

  </head>

  <body>
    <div id="jspsych_target"></div>
  </body>

  <script>
    // Experiment parameters
    var n_trials_EX = 4;
    var n_trials = 32;

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
    var welcome_message = "<div id='instructions'><p>Welcome to the " +
	"experiment. Press enter to begin.</p></div>";

    var instructions = "<div id='instructions'><p>Now you will begin " +
    	"the actual program.</p><p>Press enter to start.</p>";

    var instructions_EX = "<div id='instructions'><p>You will see a " +
	"series of images that look similar to this:</p><p>" +
	"<img src='images/1.png'></p><p>Press the arrow " +
	"key that corresponds to which hand the little man is holding " +
	"his briefcase in. For example, in this case you would press " +
	"the left arrow key.</p><p>The first four images are practice " + 
	"Press enter to start.</p>";

    var debrief = "<div id='instructions'><p>Thank you for " +
	  "participating! Press enter to see the data.</p></div>";

    // Generating Order for 4 Stimuli examples
    // Examples (EX) are not randomized
    var stimuli_order_EX = [];
    var opt_data_EX = [];

    for (var i = 0; i < n_trials_EX; i++) {
	  stimuli_order_EX.push(stimuli_EX[i]);
	  opt_data_EX.push({
		  "stimulus_type": stimuli_types_EX[i]
	  });
    }

    // Generating Random Order for Stimuli (full test)
    var stimuli_random_order = [];
    var opt_data = [];

    for (var i = 0; i < n_trials; i++) {
	  var random_choice = Math.floor(Math.random() * stimuli.length);

	  stimuli_random_order.push(stimuli[random_choice]);
	  opt_data.push({
		  "stimulus_type": stimuli_types[random_choice]
	  });
    }

    // Define experiment blocks
    var instruction_block = {
	  type: "text",
	  text: [welcome_message, instructions_EX],
	  timing_post_trial: 2500,
	  //adding ignore label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({ignore: true});
	  },
    };

    var EX_block = {
	  type: "single-stim",
	  stimuli: stimuli_order_EX,
	  choices: [37, 39],
	  data: opt_data_EX,
	  //added a function to record correct or not
	  on_finish: function(data){
	    	var correct = false;
	   	if(data.stimulus_type == 'left' && data.key_press == 37){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 39){
	      		correct = true;
	  	}
	   	 	jsPsych.data.addDataToLastTrial({correct: correct});
	  },
          
    };

    var second_instruction_block = { 
	  type: "text",
	  text: [instructions],
	  timing_post_trial: 2500,
	  //adding ignore label for welcome and instruction messages
	  on_finish: function(data){
	   	 jsPsych.data.addDataToLastTrial({ignore: true});
	  },
    };

    var test_block = {
 	  type: "single-stim",
	  stimuli: stimuli_random_order,
	  choices: [37, 39],
	  data: opt_data,

	  //added a function to record correct or not
	  on_finish: function(data){
	    	var correct = false;
	   	if(data.stimulus_type == 'left' && data.key_press == 37){
	      		correct = true;
	   	} else if(data.stimulus_type == 'right' && data.key_press == 39){
	      		correct = true;
	  	}
	   	 	jsPsych.data.addDataToLastTrial({correct: correct});
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
	  timeline: [instruction_block, EX_block, 
	  	     second_instruction_block,
	  	     test_block, debrief_block],

	  on_finish: function(data) {
	      // save data on server
              jQuery.getJSON('code/php/events.php?action=mark&status=closed&user_name='+user_name, function(data) {
		  console.log(data);
	      });
	      
	      
	      //call from tutorial displays JSON string as final page
   	      jsPsych.data.displayData();
	  }
    });
</script>
</html>
    
