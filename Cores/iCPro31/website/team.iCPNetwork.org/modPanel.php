<?php

  if(isset($_GET['command'])) {
    $files = array('checkLogin');
    $file  = in_array($_GET['command'], $files) ? $_GET['command'] : '404';
    
    $allowed = true;
    include "Commands/{$file}.command.php";
    die();
  }

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
    
    <title>iCPv3 :: Moderation Panel</title>
    
    <link type='text/css' href='CSS/ui-lightness/jquery-ui-1.8.2.custom.css' rel='stylesheet' />  
    <link type='text/css' href='CSS/modPanel.css' rel='stylesheet' />  
    
    <script type='text/javascript' src='JS/jquery-1.4.2.min.js'></script>
    <script type='text/javascript' src='JS/jquery-ui-1.8.2.custom.min.js'></script>
    
    <script type='text/javascript'>
    
      var moderatorTimer = 0;
      var isLoggedIn = false;
      var suggestValues = {
        serverID:  'Server ID',
        username:  'Username',
        sessionID: 'Session ID'
      };
      
      function updateStatus(classString, messageString) {
        $('#statusBar').removeClass('ui-state-error');
        $('#statusBar').removeClass('ui-state-highlight');
        $('#statusBar').addClass(classString);
        
        var iconString = classString == 'ui-state-error' ? 'ui-icon-alert' : 'ui-icon-info';
        $('#statusBar').html('<p><span class="ui-icon ' + iconString + '" style="float: left; margin-right: .3em;"></span>' + messageString + '</p>');
      }
      
      function login(serverID, username, sessionID) {
        showLoader('Logging in...');
        //unescape Username and sessionID
        loadContent('modPanel.php?command=checkLogin&serverID=' + serverID + '&username=' + username + '&sessionID=' + sessionID, '#content');
      }
      
      function showLoader(message) {
        $('#content').html('<div align=\'center\'><img src=\'Images/Loader.gif\' /><br />' + message + '</div>');
      }
      
      function loadContent(url, container) {
        url = url.split('?');
        data = url[1];
        url = url[0];
        $.ajax({
          url: url,
          data: data,
          success: function(data) {
            $(container).html(data);
          }
        });
      }
      
      function changePage(page) {
        if(isLoggedIn)
          loadContent('Pages/' + page + '.page.php?index', '#content');
        else
          loadContent('Pages/LOGIN_REQUIRED.page.php?index', '#content');
      }
    
      $(function() {
        $('document').ready(function() {
          updateStatus('ui-state-error', '<strong>Warning:</strong> You aren\'t logged in!');
          
          for(var i in suggestValues) $('#' + i).addClass('suggestBox');
          $('.suggestBox').focus(function() {
            if(this.value == suggestValues[this.id]) this.value = '';
            this.style.color = '#000000';
          });
          $('.suggestBox').blur(function() {
            if(this.value == '') this.value = suggestValues[this.id];
            if(this.value == suggestValues[this.id]) this.style.color = '#DADADA';
          });
          $('.suggestBox').trigger('blur');
        });
        $('#menu').accordion({
          header:      'h3',
          autoHeight:  false,
          collapsible: true
        }).sortable({
    			axis:   'y',
    			handle: 'h3',
    			stop: function(event, ui) {
    				stop = true;
    			}
    		});
        $('#loginBox').dialog({
          modal:    true,
          autoOpen: false,
          width:    320,
          beforeclose: function() { updateStatus('ui-state-error', '<strong>Warning:</strong> You are still not logged in!'); },
          buttons: {
            'Submit': function() {
              var serverID  = $('#serverID').val();
              var username  = $('#username').val();
              var sessionID = $('#sessionID').val();
              var error     = '';
              
              if(username == suggestValues['username']) error = 'You have to pass your Username!';
              else if(sessionID == suggestValues['sessionID']) error = 'You have to pass your SessionID!';
              else if(username.length < 5) error = 'Your Username is to short!';
              else if(username.length > 12) error = 'Your Username is to long!';
              else if(sessionID.length != 4 && sessionID.length != 6) error = 'Your SessionID is invalid!';
              
              if(error) alert(error, updateStatus('ui-state-error', '<strong>Could not Login:</strong> ' + error));
              else {
                $(this).dialog('close');
                login(serverID, username, sessionID);
                updateStatus('ui-state-highlight', '<strong>Status:</strong> Logging in...');
              }
            }, 
            'Cancel': function() {
              $(this).dialog('close'); 
            } 
          }
        });
        $('#loginLink').click(function() {
          $('#loginBox').dialog('open');
          return false;
        });
        $('#loginLink, ul#icons li').hover(
          function() { $(this).addClass('ui-state-hover'); }, 
          function() { $(this).removeClass('ui-state-hover'); }
        );        
      });
      
    </script> 
  </head>
  <body>
    <div class='ui-widget'><div id='statusBar' class='ui-corner-all' /></div>
    <div align='right'><a href='#' id='loginLink' class='ui-state-default ui-corner-all'><span class='ui-icon ui-icon-newwin'></span>Login</a></div>
    <div id='loginBox' title='Login'>
      <input type='text' id='serverID'  style='width: 100%;margin:1px -3px;' maxlength='3'  /><br />
      <input type='text' id='username'  style='width: 100%;margin:1px -3px;' maxlength='12' /><br />
      <input type='text' id='sessionID' style='width: 100%;margin:1px -3px;' maxlength='6'  /><br />
    </div>
    <div id='wrapper' class='ui-corner-all'>
		  <div id='menu' class='ui-corner-all'>
		    <div>
  				<h3><a href='#'>Crumbs</a></h3>
  				<div>
  				  <a href='#' onclick="javascript:changePage('ITEM_CRUMBS');">Items</a><br />
  				  <a href='#' onclick="javascript:changePage('FURNITURE_CRUMBS');">Furniture</a><br />
  				  <a href='#' onclick="javascript:changePage('IGLOO_CRUMBS');">Igloos</a><br />
  				  <a href='#' onclick="javascript:changePage('FLOOR_CRUMBS');">Floors</a><br />
  				  <a href='#' onclick="javascript:changePage('CENSORINGS_CRUMBS');">Censorings</a><br />
  				</div>
  			</div>
  			<div>
  				<h3><a href='#'>Database</a></h3>
  				<div>Phasellus mattis tincidunt nibh.</div>
  			</div>
  			<div>
  				<h3><a href='#'>Other</a></h3>
  				<div>Nam dui erat, auctor a, dignissim quis.</div>
  			</div>
  		</div>
  		<div id='content'>
        <strong>Hey there!</strong><br />
        Seems like you are either a Moderator who is not logged in or somebody who discovered this URL!<br />
        Well, it doesnt matter who you are, because this Area is Password protected, <strong>but</strong> - since<br />
        you are already here...<br />
        <i>&raquo;Wanna buy some Waffles?&laquo;</i><br />
        <br />
        This Question is meant serious. Some people would really DIE for Waffles! Well, actually not...<br />
        But anyway. Althought I know this crappy Content makes some useless Traffic, I am just gonna continue<br />
        writing some odd stuff here. Mwhahahahaha :)<br />
        <br />
        Okay I am done...<br />
        <br />
        Yours <small>not</small> Sincerly,<br />
        &nbsp;&nbsp;<i>Alex ;)</i>
      </div>
    </div>
  </body>
</html>