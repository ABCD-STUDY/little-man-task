//----------------------------------------
// User accounts
//----------------------------------------
// logout the current user
function logout() {
    jQuery.get('/code/php/logout.php', function(data) {
	if (data == "success") {
            // user is logged out, reload this page
	} else {
            alert('something went terribly wrong during logout: ' + data);
	}
	window.location.href = "/applications/User/login.php";
    });
}

function checkConnectionStatus() {
    jQuery.getJSON('/code/php/heartbeat.php', function() {
	//jQuery('#connection-status').addClass('connection-status-ok');
	jQuery('#connection-status').css('color', "#228B22");
	jQuery('#connection-status').attr('title', 'Connection established last at ' + Date());
    }).error(function() {
	// jQuery('#connection-status').removeClass('connection-status-ok');
	jQuery('#connection-status').css('color', "#CD5C5C");
	jQuery('#connection-status').attr('title', 'Connection failed at ' + Date());
    });
}


function storeSubjectAndName() {
    if (jQuery('#participant-names-from-redcap').val() == null)
	return;
    var subject = jQuery('#participant-names-from-redcap').val().replace(/\s/g, '');
    var session = jQuery('#sessions-from-redcap').val().replace(/\s/g, '');
    var run     = jQuery('#sessions-run').val();
    jQuery('#session-participant').val(subject);
    jQuery('#session-name').val(session);
    jQuery('.subject-id').text("Subject ID: " + subject);
    jQuery('.session-id').text("Session: " + session);
    jQuery('.run-id').text("Run: " + run);
    
    if (subject !== null && subject !== "" && session !== null && session !== "" && subject.length > 0 && session.length > 0) {
	jQuery('#session-active').text("Active Session");
	jQuery('#calendar-loc').fadeIn();
	jQuery('#open-save-session').fadeIn();
    } else {
	jQuery('#session-active').text("No Active Session");
	jQuery('#calendar-loc').fadeOut();
	jQuery('#open-save-session').fadeOut();
    }
    
    var data = {
	"subjid": subject,
	"sessionid": session,
	"run": run,
	"task": "little-man-task"
    };
    
    jQuery.get('../../code/php/session.php', data, function() {
	console.log('stored subject, session and run: ' +  subject + ", " + session + ", " + run );
    });
}

// forget about the current session
function closeSession() {
    // just set to empty strings and submit
    jQuery('#participants-from-redcap').val("");
    jQuery('#sessions-from-redcap').val("");
    jQuery('#session-run').val("01");
    storeSubjectAndName();
}

function exportToCsv(filename, rows) {
    var processRow = function (row) {
	if (row.substance == "undefined") {
	    row.substance = "";
	} else {
            row.substance = "\"" + row.substance + "\"";
	}
	if (row.amount == "undefined") {
	    row.amount = "";
	}
	if (row.unit == "undefined") {
	    row.unit = "";
	} else {
	    row.unit = "\"" + row.unit + "\"";
	}
	var finalVal = user_name + ",\"" + row.title + "\"," + row.substance + "," + row.amount + ","
	    + row.unit + "," + moment(row.start).format("MM/DD/YYYY") + ","
	    + moment(row.end).format("MM/DD/YYYY");
	return finalVal + '\n';
    };
    
    var csvFile = 'user name, title, substance, amount, unit, date (start), date (end)\n';
    for (var i = 0; i < rows.length; i++) {
	csvFile += processRow(rows[i]);
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

// get valid session names
function getSessionNamesFromREDCap() {
    jQuery.getJSON('/code/php/getRCEvents.php', function(data) {
	for (var i = 0; i < data.length; i++) {
	    val = "";
	    /*
	    if (i == 1) {
	        val = "selected=\"selected\"";
	    }
	    */
	    if (data[i].unique_event_name !== "baseline_year_1_arm_1") continue;
	    jQuery('#sessions-from-redcap').append("<option " + val + " value=\"" + data[i].unique_event_name + "\">" + data[i].event_name + "</option>");
	}
	getParticipantNamesFromREDCap();
	//storeSubjectAndName();
    });
}

function getParticipantNamesFromREDCap() {
    jQuery.getJSON('/code/php/getParticipantNamesFromREDCap.php', function(data) {
	for (var i = 0; i < data.length; i++) {
	    jQuery('#participant-names-from-redcap').append("<option value=\"" + data[i] + "\">" + data[i] + "</option>");
	}
	jQuery('#participant-names-from-redcap').select2({ placeholder: "Select a pGUID" });
	jQuery('#participant-names-from-redcap').val("").trigger('change');
	storeSubjectAndName();
    });
}


jQuery(document).ready(function() {

    getSessionNamesFromREDCap();
    
    // add the session variables to the interface
    jQuery('#user_name').text("User: " + user_name);
    jQuery('#session-participant').val(subjid);
    jQuery('#session-name').val(session);
    jQuery('#run').val(run);
    
    checkConnectionStatus();
    // Disable for now: setInterval( checkConnectionStatus, 5000 );
    
    /*jQuery('#session-participant').change(function() {
	storeSubjectAndName();
    });
    jQuery('#session-name').change(function() {
	storeSubjectAndName();
	}); */
    jQuery('#participant-names-from-redcap').change(function() {
	storeSubjectAndName();
    });
    jQuery('#sessions-from-redcap').change(function() {
	storeSubjectAndName();
    });
    jQuery('#sessions-run').change(function() {
	storeSubjectAndName();
    });
    
    jQuery('#open-save-session').click(function() {
	jQuery('#session-participant-again').val(""); // clear the value from before
    });

    jQuery('#open-lmt-button').click(function() {
	var pGUID   = jQuery('#participants-from-redcap').val();
	var session = jQuery('#sessions-from-redcap').val();
	var run     = jQuery('#session-run').val();
	if (pGUID === null || pGUID === "" || session === null || session === "" || run === null || run === "") {
	    alert("Error: all values are mandatory.");
	    return true;
	}
	
        jQuery.post('code/php/events.php', { "action": "test" }, function(data) {
	    // check if the current data file can be saved - does not exist already on the server and would be overwritten
	    console.log('would be overwritten');
	    if (data.ok == 0) {
		alert('Error: this data file exists already on the server, cannot be overwritten');
		return;
	    }
	    
            // mark this one as started
	    jQuery.post('code/php/events.php', { "action": "mark", "status": "started", "user_name": user_name }, function(data) {
		console.log(data);
	    }, 'json');
	    
	    // redirect to the task.php page
	    window.location = '/applications/little-man-task/task.php';
	}, 'json');
	
    });
    
    jQuery('#open-lmt2-button').click(function() {
	var pGUID   = jQuery('#participants-from-redcap').val();
	var session = jQuery('#sessions-from-redcap').val();
	var run     = jQuery('#session-run').val();
	if (pGUID === null || pGUID === "" || session === null || session === "" || run === null || run === "") {
	    alert("Error: all values are mandatory.");
	    return true;
	}

        // mark this one as started
	jQuery.getJSON('code/php/events.php?action=mark&status=started&user_name='+user_name, function(data) {
	    console.log(data);
	});

	// redirect to the task.php page
	window.location = '/applications/little-man-task/task-keyboard.php';
    });
    
    // 
    jQuery('#save-session-button').click(function() {
	// test if subjid matches
	var nameNow = jQuery('#session-participant-again').val().replace(/\s/g, '');
	var nameBefore = jQuery('#session-participant').val().replace(/\s/g, '');
	if ( nameNow != nameBefore ) {
	    alert("Error: Your subject ID is not correct, please check the subject ID for correctness again.");
	    return false;
	}
	
	// mark the session as closed
	jQuery.getJSON('code/php/events.php?action=mark&status=closed&user_name='+user_name, function(data) {
	    console.log(data);
	});
	
	// create spreadsheet with data
	setTimeout( (function( subject, session ) {
	    // return a function
	    return function() {
		var filename = user_name + "_" + subject + "_" + session + "_" + (new Date()).toLocaleString() + ".csv";
		jQuery.getJSON('code/php/events.php', function(rows) {
		    exportToCsv(filename, rows);
		    
		    // clean interface again
		    jQuery('#session-participant').val("");
		    jQuery('#session-name').val("");
		    storeSubjectAndName();
		});
	    };
	})( jQuery('#session-participant').val(), jQuery('#session-name').val() ), 1000);
    });
    
    jQuery('#session-date-picker').datetimepicker({language: 'en', format: "MM/DD/YYYY" });    
    jQuery('#session-date-picker').data("DateTimePicker").setDate(new Date());
});
