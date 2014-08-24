<?php

/*if($_SERVER['REMOTE_ADDR'] != '::1') {
    $inRegister = true;
    include 'index.php';
    die();
  }*/
  
  if(isset($_GET['username'])) {
    function sendBack($func_value) {
      $func_data = array('false' => 'REGISTER', 'fail' => 'DATABASE_ERROR', 'true' => 'USERNAME_TAKEN');
      include "Pages/{$func_data[$func_value]}.page.php";
    }
    
    include 'checkName.php';
    die();
  }
  
  from ;include '../../Network.php' ;uses ;{
    $pMin = SettingsManager::GetSetting(Settings::PLAYER_MINLEN);
    $pMax = SettingsManager::GetSetting(Settings::PLAYER_MAXLEN);
    $pChr = SettingsManager::GetSetting(Settings::PLAYER_MAXLEN);
    
    $aMin = SettingsManager::GetSetting(Settings::PASSWORD_MINLEN);
    $aMax = SettingsManager::GetSetting(Settings::PASSWORD_MAXLEN);
    
    $eMin = SettingsManager::GetSetting(Settings::EMAIL_MINLEN);
    $eMax = SettingsManager::GetSetting(Settings::EMAIL_MAXLEN);
  };

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
    
    <title>iCPv3 :: Registration</title>
    
    <link type='text/css' href='CSS/ui-lightness/jquery-ui-1.8.2.custom.css' rel='stylesheet' />  
    <link type='text/css' href='CSS/register.css' rel='stylesheet' />  
    
    <script type='text/javascript' src='JS/MD5.js'></script>
    <script type='text/javascript' src='JS/jquery-1.4.2.min.js'></script>
    <script type='text/javascript' src='JS/jquery-ui-1.8.2.custom.min.js'></script>
    
    <script type='text/javascript'>
    
      function LTrim(value) {
      	var re = /\s*((\S+\s*)*)/;
      	return value.replace(re, "$1");
      }
      
      function RTrim(value) {
      	var re = /((\s*\S+)*)\s*/;
      	return value.replace(re, "$1");
      }
      
      function trim(value) {
      	return LTrim(RTrim(value));
      }
    
      var moderatorTimer = 0;
      var isLoggedIn = false;
      var suggestValues = {
        playerName:   'Playername',
        passwordA:    '',
        passwordB:    '',
        emailAddress: 'EMail@Address.com',
        recommended:  'Who told you about this?'
      };
      
      var states = {
        noticePasswords:  0,
        noticePlayerName: 0,
        noticeEMail:      0
      };
      
      function updateStatus(classString, messageString) {
        $('#statusBar').removeClass('ui-state-error');
        $('#statusBar').removeClass('ui-state-highlight');
        $('#statusBar').addClass(classString);
        
        var iconString = classString == 'ui-state-error' ? 'ui-icon-alert' : 'ui-icon-info';
        $('#statusBar').html('<p><span class="ui-icon ' + iconString + '" style="float: left; margin-right: .3em;"></span>' + messageString + '</p>');
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
      
      function updateNotice(fieldID, fieldData, fieldMessage) {
        states[fieldID] = Number(fieldData == 'fieldNoticeFail');
        fieldID = '#' + fieldID;
        
        $(fieldID).removeClass('fieldNoticeOkay');
        $(fieldID).removeClass('fieldNoticeFail');
        
        $(fieldID).addClass(fieldData);
        
        $(fieldID).html(fieldMessage);
      }
      
      $(function() {
        $('#playerName, #recommended').keyup(function() {
          var playerName = this.value;
          var noticeID = this.id == 'playerName' ? 'noticePlayerName' : 'noticeEMail';
          
          if(playerName.length == 0)
           if(this.id == 'recommended') return updateNotice(noticeID, 'fieldNoticeOkay', 'You don\'t have to edit that Field, but it\'s recommended!');
           else return updateNotice(noticeID, 'fieldNoticeFail', 'Please enter a Username!');
          
          if(playerName.length < <?= $pMin ?>) return updateNotice(noticeID, 'fieldNoticeFail', 'The PlayerName is too short! <?= $pMin ?> Chars at Minimum!');
          if(playerName.length > <?= $pMax ?>) return updateNotice(noticeID, 'fieldNoticeFail', 'The PlayerName is too long! <?= $pMax ?> Chars at Maximum!');
          
          var count = 0;
          for(var i = 0; i < playerName.length; ++i) if((chr = playerName.charCodeAt(i)) && (chr > 64 && chr < 91 || chr > 96 && chr < 123)) ++count;
          if(!count) return updateNotice(noticeID, 'fieldNoticeFail', 'The PlayerName has to contain atleast one letter!');
          
          return updateNotice(noticeID, 'fieldNoticeOkay', 'The PlayerName is okay :)');
        }).trigger('keyup').blur(function() {
          if(states.noticePlayerName) return;
          $.ajax({
            url:  'checkName.php',
            data: 'username=' + this.value,
            success: function(data) {
              if(data == 'true')  return updateNotice('noticePlayerName', 'fieldNoticeFail', 'Sorry, that PlayerName is already taken!');
              if(data == 'fail')  return updateNotice('noticePlayerName', 'fieldNoticeFail', 'Sorry, we have no Database Connection currently!');
              if(data == 'false') return updateNotice('noticePlayerName', 'fieldNoticeOkay', 'The PlayerName is okay and not taken yet :)');
              alert(
               ['Debug TraceBack',
                ' at iCPv3',
                '  at Register.php',
                '   at AJAX.success Callback',
                '    called with Parameter',
                '    #0: [' + typeof(data) + '] ' + data,
                '     at checkName.php?username=...',
                '',
                'Yes, we claim that it\'s Microsuck\'s Fault!'].join("\n"));
              return updateNotice('noticePlayerName', 'fieldNoticeFail', 'Something is wrong!');
            }
          });
        });
        $('#passwordA, #passwordB').keyup(function() {
          
          this.value = trim(this.value);
          if(this.value.length == 0)
           if(this.id == 'passwordB' && $('#passwordA').val().length != 0) return updateNotice('noticePasswords', 'fieldNoticeFail', 'You have to repeat the Password!');
           else return updateNotice('noticePasswords', 'fieldNoticeFail', 'You have to enter a Password!');
          
          if(this.id == 'passwordB' && $('#passwordA').val() != $('#passwordB').val())
           return updateNotice('noticePasswords', 'fieldNoticeFail', 'The Passwords don\'t match!');
          
          if(this.value.length < <?= $aMin ?>) return updateNotice('noticePasswords', 'fieldNoticeFail', 'The Password is too short! <?= $aMin ?> Chars at Minimum!');
          if(this.value.length > <?= $aMax ?>) return updateNotice('noticePasswords', 'fieldNoticeFail', 'The Password is too long! <?= $aMax ?> Chars at Maximum!');
          
          if($('#passwordB').val().length == 0) return updateNotice('noticePasswords', 'fieldNoticeFail', 'You have to repeat the Password!');
          if(this.id == 'passwordA' && $('#passwordA').val() != $('#passwordB').val())
           return updateNotice('noticePasswords', 'fieldNoticeFail', 'The Passwords don\'t match!');
            
          return updateNotice('noticePasswords', 'fieldNoticeOkay', 'The Passwords are okay :)');
        }).trigger('keyup');
        $('#emailAddress').keyup(function() {
          this.value = trim(this.value);
          
          var email = this.value;
          
          if(email.length < <?= $eMin ?>) return updateNotice('noticeEMail', 'fieldNoticeFail', 'The EMail Address is too short! <?= $eMin ?> Chars at Minimum!');
          if(email.length > <?= $eMax ?>) return updateNotice('noticeEMail', 'fieldNoticeFail', 'The EMail Address is too long! <?= $eMax ?> Chars at Maximum!');
          
          if(email.split('@').length != 2) return updateNotice('noticeEMail', 'fieldNoticeFail', 'The EMail Address is invalid! It has to contain exactly <b>one</b> @!');
          if(email.split('@')[1].split('.').length < 2) return updateNotice('noticeEMail', 'fieldNoticeFail', 'The EMail Address is invalid! The Domain is wrong!');
          
          var emailName   = email.split('@')[0];
          var emailDomain = email.split('@')[1].split('.');
          var emailTLD    = emailDomain.pop();
          emailDomain = emailDomain.join('.');
          
          if(emailName.length < 1)   return updateNotice('noticeEMail', 'fieldNoticeFail', 'You have to specify a Username in the EMail Address!');
          if(emailDomain.length < 1) return updateNotice('noticeEMail', 'fieldNoticeFail', 'You have to specify a Domain in the EMail Address!');
          if(emailTLD.length < 2)    return updateNotice('noticeEMail', 'fieldNoticeFail', 'You have to specify a valid TLD in the EMail Address!');
          
          return updateNotice('noticeEMail', 'fieldNoticeOkay', 'The EMail is okay :)');
        }).trigger('keyup');
        $('document').ready(function() {
          updateStatus('ui-state-highlight', '<strong>Welcome!</strong> To Register for iCP click the "Register" Button!');
          
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
        $('#registerBox').dialog({
          modal:    true,
          autoOpen: false,
          width:    320,
          beforeclose: function() { updateStatus('ui-state-highlight', '<strong>Welcome!</strong> Registration aborted!'); },
          buttons: {
            'Submit': function() {
              var sum = 0;
              for(var i in states) sum += states[i];
              
              if(sum) {
                var s = sum == 1 ? '' : 's';
                var is = sum == 1 ? 'is' : 'are';
                var error = 'There ' + is + ' still ' + sum + ' Mistake' + s + ' in the Regristration Form!';
                
                return (updateStatus('ui-state-error', '<strong>Regristration failed:</strong> ' + error) | alert(error)) && false;
              } else {
                $(this).dialog('close');
                updateStatus('ui-state-highlight', '<strong>Status:</strong> Sending Regristration...');
                loadContent('register.php?' +
                'username=' + $('#playerName').val() +
                '&password=' + MD5($('#passwordA').val()) +
                '&email=' + $('#emailAddress').val() +
                '&color=' + $('#color').val(), '#content');
              }
            }, 
            'Cancel': function() {
              $(this).dialog('close'); 
            } 
          }
        });
        $('#registerLink').click(function() {
          $('#registerBox').dialog('open');
          return false;
        });
        $('#registerLink, ul#icons li').hover(
          function() { $(this).addClass('ui-state-hover'); }, 
          function() { $(this).removeClass('ui-state-hover'); }
        );        
      });
      
    </script> 
  </head>
  <body>
    <div class='ui-widget'><div id='statusBar' class='ui-corner-all'></div></div>
    <div align='right'><a href='#' id='registerLink' class='ui-state-default ui-corner-all'><span class='ui-icon ui-icon-newwin'></span>Register</a></div>
    <div id='registerBox' title='Register for iCPv3'>
      <div id='noticePlayerName' class='fieldNotice'></div>
      <input type='text' id='playerName' maxlength='<?= $pMax ?>' /><br />
      <div id='noticePasswords' class='fieldNotice'></div>
      <input type='password' id='passwordA' maxlength='<?= $aMax ?>' /><br />
      <input type='password' id='passwordB' maxlength='<?= $aMax ?>' /><br />
      <div id='noticeEMail' class='fieldNotice'></div>
      <input type='text' id='emailAddress' maxlength='<?= $eMax ?>' /><br />
      <input type='text' id='recommended'  maxlength='<?= $pMax ?>'  /><br />
      <div class='fieldNotice'>If you don't pick a Color, we will surprise you by picking one randomly!</div>
      <select id='color'>
        <option value='0'>Pick a Color</option>
        <option value='1'>Blue</option>
        <option value='2'>Green</option>
        <option value='3'>Pink</option>
        <option value='4'>Black</option>
        <option value='5'>Red</option>
        <option value='6'>Orange</option>
        <option value='7'>Yellow</option>
        <option value='8'>Dark Purple</option>
        <option value='9'>Brown</option>
        <option value='10'>Peach</option>
        <option value='11'>Dark Green</option>
        <option value='12'>Light Blue</option>
        <option value='13'>Lime Green</option>
        <option value='14'>Gray *exclusive*</option>
        <option value='15'>Aqua</option>
      </select>
    </div>
    <div id='content' class='ui-corner-all'>
      <strong>Hey there!</strong><br />
      <br />
      Cool you've heared of iCPv3! Before your register, you *might* want to know what iCP actually is?<br />
      Well, iCP is a Project started by Alexander Rath (Who is now HeadDeveloper of iCP) and a friend in<br />
      a Computer Camp around 4 years ago.<br />
      <br />
      Both were playing CP and then - in the Computer Camp - they heared of Decompilers. So they had the Idea:<br />
      &raquo;Hey, if we can Decompile SWFs, why don't we decompile CP? So we could run it locally! Just for us!&laquo;<br />
      <br />
      After they had finally noticed, that they are unable to, Alexander Rath, after his Arrivial back home,<br />
      started focusing on other Things again - most important was PHP.<br />
      <br />
      He had always loved PHP for his simlicity but dynamicility. He begun learning what Sockets were when he first<br />
      used Pickle. He wanted to learn more about Sockets and slowly he started focusing more on that Server than<br />
      on the Client and so he decided to make his own CP Server.<br />
      <br />
      To that time, it didn't have a Name, it wasn't even public! You might ask why, but the Answer is easy:<br />
      <strong>It just sucked!</strong><br />
      It had no real Features, you could only be in one Room and it was DAMN Buggy.<br />
      <small>Today this failed Attempt is called iCPv0</small><br />
      <br />
      After he noticed, that it just cant work this way, he decided to leave his Private Server alone and focus<br />
      on other Things. After focusing more on OOP and writing Servers for small Projects getting bigger and bigger<br />
      he remembered the Past and his good old failed Project.<br />
      <br />
      He then had the Idea to try again - with iCPv1!<br />
      At this point - there were a lot of Owners:<br />
      <ul>
        <li>iNuX <i>(Paul)</i></li>
        <li>iChris <i>(Chris)</i></li>
        <li>iCrack :P <i>(Alex)</i></li>
        <li>Cody <i>(Cody)</i></li>
        <li>James <i>(James)</i></li>
        <li>Prkr5885 <i>(Parker)</i></li>
        <li>Tatertot27 <i>(Dominick)</i></li>
        <li>SoBaked <i>(Javier)</i></li>
      </ul>
      Today most of them quit iCP and a few others just "disappeared".<br />
      <br />
      After iCPv1, Alex decided to make a new iCP with cleaner Code and less Bugs.<br />
      He claimed his old Program Structure to be <i>unuseful</i> and begun working on a new Concept.<br />
      <br />
      iCPv2 was already better and there were a lot of Bug Fixes in iCPv2.1, but Alex was still unconvinced.<br />
      So he begun a new complete Rewrite, which is know as<br />
      <br />
      <big><strong>iCPv3</strong></big><br />
      iCPv3 is the latest and most advanced Release of iCPv3.<br />
      It has been released on <i>Put in Date here</i> and had major Bugfixes like:<br />
      <br />
      <strong>GameManager</strong><br />
      The GameManager is one of the coolest Parts of iCPv3. It currently supports Games like FindFour, Mancala and TreasureHunt.<br />
      It gives a nice Enviroment for dynamically loading Games and Game Types. Games are now like Plugins,<br />
      they are not Hardcoded into iCPv3, which makes Modifications easier and allows adding Games on the fly (without Server Restart)<br />
      <br />
      <strong>Moderation Panel</strong><br />
      We now listen to what you say - report a Player and Moderators will notice and come to help you!<br />
      Moderators now have a nice Panel with a 3-Stage-Security Algorithm. Only real Moderators can log in - others can't!<br />
      It checks for IP, Password and other Stuff!<br />
      <br />
      <strong>Server Reconnection</strong><br />
      Whenever in iCPv2 the Login Server crashed, the whole Network had to be restarted!<br />
      Which means - every single Server - which means <i>EVERYBODY</i> in iCP had to reconnect.<br />
      In iCPv3, Nobody cares if the Login Server crashes! Every single Server will just reconnect when the Server is back on!<br />
      <br />
      <strong>Stability and Reliability</strong><br />
      Less Bugs and a faster and stronger Code make iCPv3 a Burner!<br />
      You will enjoy playing iCPv3 a lot.<br />
      <br />
      <br />
      That's basicly it, and don't forget:<br />
      The only real Owners are <i>iCrack :P / Alex</i> and <i>Co Co / Lofhy</i>!
    </div>
  </body>
</html>