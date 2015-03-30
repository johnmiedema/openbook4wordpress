//this file defines the HTML form and handles the form submit for the OpenBook visual editor button

function openbook_button_validations(booknumber, templatenumber, publisherurl, revisionnumber) {

	if (booknumber == "") {
		alert("A Book Number is required");
		document.getElementById('openbook-booknumber').focus();
		return false;
	}

	if (revisionnumber != "") {			
		if (isNaN(revisionnumber)) {
			alert("Revision must be blank or a number");
			document.getElementById('openbook-revisionnumber').focus();
			return false;
		}
	}
	return true;
}

//ajax handler calls server side OpenBook preview method
function openbook_button_preview(booknumber, templatenumber, publisherurl, revisionnumber) {

	var data = {
		action: 'openbook_action',
		booknumber: booknumber,
		templatenumber: templatenumber,
		publisherurl: publisherurl,
		revisionnumber: revisionnumber
	};

	document.getElementById('openbook-request').style.display = "none";
	document.getElementById('openbook-request').style.visibility = "hidden";
	document.getElementById('openbook-response').innerHTML = "... please wait ...";

	//ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: data,
		success: function(response) { 
			document.getElementById('openbook-response').innerHTML = response; 	
			document.getElementById('openbook-response-div').style.display = "block";
			document.getElementById('openbook-response-div').style.visibility = "visible";
		},
		async: false,
		cache: false
	}); 	
}

function openbook_reset() {
	document.getElementById('openbook-booknumbertype').value="ISBN";
	document.getElementById('openbook-booknumber').value="";
	document.getElementById('openbook-templatenumber').value="1";
	document.getElementById('openbook-booknumber').value="";
	document.getElementById('openbook-publisherurl').value="";
	document.getElementById('openbook-revisionnumber').value="";
	document.getElementById('openbook-html').checked=true;

	document.getElementById('openbook-request').style.display = "block";
	document.getElementById('openbook-request').style.visibility = "visible";

	document.getElementById('openbook-response').innerHTML="";
	document.getElementById('openbook-response-div').style.display = "none";
	document.getElementById('openbook-response-div').style.visibility = "hidden";			
}

(function() {
	tinymce.PluginManager.add('my_mce_button_openbook', function( editor, url ) {
		editor.addButton('my_mce_button_openbook', {
			title: 'OpenBook',
			image: '../wp-content/plugins/openbook-book-data/libraries/openbook_button.gif',  // path to the button's image
			onclick: function() {
				// triggers the thickbox
				var width = jQuery(window).width(), H = jQuery(window).height(), W = ( 720 < width ) ? 720 : width;
				W = W - 80;
				H = H - 84;;
				tb_show( 'OpenBook', '#TB_inline?width=' + W + '&height=' + H + '&inlineId=openbook-form' );
			}
		});
	});
	
	// executes this when the DOM is ready
	jQuery(function(){
		// creates a form to be displayed everytime the button is clicked
		var form = jQuery('<div id="openbook-form">\
			<div id="openbook-request">\
			<table id="openbook-table" class="form-table">\
			<tr><td width="60%">Book Number<br /><small>Select type (usually ISBN) and enter number. You can look the number up at <a href="http://openlibrary.org/" target="_blank">Open Library</a>. If the book is not there, <a href="http://openlibrary.org/books/add" target="_blank">add it</a>.</small></td>\
				<td width="40%"><select name="openbook-booknumbertype" id="openbook-booknumbertype">\
					<option value="ISBN">ISBN (10 or 13 digits)</option>\
					<option value="LCCN">LCCN</option>\
					<option value="OCLC">OCLC</option>\
					<option value="OLID">Open Library Key (OLXXXXXXXXX)</option>\
					</select>\
					<input type="text" name="openbook-booknumber" id="openbook-booknumber" value="" />\
				</td>\
			</tr>\
			<tr><td>Revision Number<br><small>If the Book Number type is Open Library Key, you can specify a revision number, else the most recent version is used.</small></td>\
				<td><input type="text" name="openbook-revisionnumber" id="openbook-revisionnumber" value="" disabled="true" />\
				</td>\
			</tr>\
			<tr><td>Template Number<br /><small>Select an OpenBook template number. Matches the template on the <a href="../wp-admin/options-general.php?page=openbook_options.php" target="_blank">OpenBook Settings</a> page.</small></td>\
				<td><select name="openbook-templatenumber" id="openbook-templatenumber">\
					<option value="1">1</option>\
					<option value="2">2</option>\
					<option value="3">3</option>\
					<option value="4">4</option>\
					<option value="5">5</option>\
					</select>\
				</td>\
			</tr>\
			<tr><td>Publisher URL<br><small>Optional. If you enter a publisher URL it will be used in the OpenBook publisher display element.</small></td>\
				<td><input type="text" name="openbook-publisherurl" id="openbook-publisherurl" value="" />\
				</td>\
			</tr>\
			<tr><td>HTML (recommended) or Shortcode<br /><small>HTML is longer but loads faster for your readers. Shortcode is shorter but makes a live call to Open Library.</small></td>\
				<td><input type="radio" name="openbook-shortcode" id="openbook-html" value="html" checked />HTML\
				<input type="radio" name="openbook-shortcode" id="openbook-shortcode" value="shortcode" />Shortcode\
				</td>\
			</tr>\
			</table>\
			<p class="submit"><input type="button" id="openbook-preview" value="Preview" name="openbook-preview" /> <input type="button" id="openbook-reset" value="Reset" name="openbook-reset" /></p>\
			</div>\
			<div id="openbook-response-div" style="display:none;visibility:hidden;">\
			<p class="submit"><input type="button" id="openbook-back" value="<< Back" name="openbook-back" /> <input type="button" id="openbook-insert" value="Insert" name="openbook-insert" /></span></p>\
			<span id="openbook-response"></span>\
			</div>\
			</div>');
		
		var table = form.find('table');
		form.appendTo('body').hide();

		// handles the change event of the book number dropdown
		form.find('#openbook-booknumbertype').change(function(){
			var booknumbertype = document.getElementById('openbook-booknumbertype').value;
			if (booknumbertype == 'OLID') {
				document.getElementById('openbook-revisionnumber').disabled = false;
			}
			else {
				document.getElementById('openbook-revisionnumber').disabled = true;
				document.getElementById('openbook-revisionnumber').value = '';
			}
		});
		
		// handles the click event of the preview button
		form.find('#openbook-preview').click(function(){
		
			var booknumbertype = document.getElementById('openbook-booknumbertype').value;
			var booknumber = document.getElementById('openbook-booknumber').value;
			var revisionnumber = document.getElementById('openbook-revisionnumber').value;
			var booktypenumber = booknumbertype + ':' + booknumber;
			if (booknumbertype == 'OLID' && revisionnumber != '') { booktypenumber = booktypenumber + '@' + revisionnumber; }
			
			var templatenumber = document.getElementById('openbook-templatenumber').value;
			var shortcodechecked = document.getElementById('openbook-shortcode').checked;
			var publisherurl = document.getElementById('openbook-publisherurl').value;
			
			if (openbook_button_validations(booknumber, templatenumber, publisherurl, revisionnumber)) {				
				openbook_button_preview(booktypenumber, templatenumber, publisherurl, revisionnumber);
			}
		});

		// handles the click event of the reset button
		form.find('#openbook-reset').click(function(){
			openbook_reset();
		});

		// handles the click event of the back button
		form.find('#openbook-back').click(function(){	
			document.getElementById('openbook-request').style.display = "block";
			document.getElementById('openbook-request').style.visibility = "visible";
			document.getElementById('openbook-response').innerHTML="";
			document.getElementById('openbook-response-div').style.display = "none";
			document.getElementById('openbook-response-div').style.visibility = "hidden";			
		});
		
		// handles the click event of the insert button
		form.find('#openbook-insert').click(function(){
				
			var booknumbertype = document.getElementById('openbook-booknumbertype').value;
			var booknumber = document.getElementById('openbook-booknumber').value;
			var revisionnumber = document.getElementById('openbook-revisionnumber').value;
			var booktypenumber = booknumbertype + ':' + booknumber;
			if (booknumbertype == 'OLID' && revisionnumber != '') { booktypenumber = booktypenumber + '@' + revisionnumber; }
			
			var templatenumber = document.getElementById('openbook-templatenumber').value;
			var shortcodechecked = document.getElementById('openbook-shortcode').checked;
			var publisherurl = document.getElementById('openbook-publisherurl').value;
			
			if (openbook_button_validations(booknumber, templatenumber, publisherurl, revisionnumber)) {
	
				var display = '';
				if (shortcodechecked == true) {

					var shortcode = '[openbook';
					shortcode += ' booknumber="' + booktypenumber + '"';
					shortcode += ' templatenumber="' + templatenumber + '"';
					if (publisherurl != '') shortcode += ' publisherurl="' + publisherurl + '"';
					shortcode += ']';	

					display = shortcode;
				}
				else {	
					if (document.getElementById('openbook-response').innerHTML=="") {
						openbook_button_preview(booktypenumber, templatenumber, publisherurl, revisionnumber);	
					}
					display = document.getElementById('openbook-response').innerHTML;
				}

				openbook_reset();
				
				tinyMCE.activeEditor.execCommand('mceInsertContent', 0, display);
				tb_remove(); //closes form
			}
		});
	});	
})();
