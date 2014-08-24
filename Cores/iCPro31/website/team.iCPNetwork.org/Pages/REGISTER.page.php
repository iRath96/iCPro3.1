<?php

  function updateStatus($func_classString, $func_messageString) {
    ?><script type="text/javascript">
      updateStatus("<?= $func_classString ?>", "<?= $func_messageString ?>");
    </script><?php
  }

  $password = strtoupper($_GET['password']);
  $username = trim($_GET['username']);
  $email    = trim($_GET['email']);
  $color    = (integer) $_GET['color'];
  if($color < 1 || $color > 15) $color = rand(1, 15);
  if(strlen($username) < SettingsManager::GetSetting(Settings::PLAYER_MINLEN)) die('Error 0x00000000');
  
  $uppername = strtoupper($username);
  if(str_replace(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), '', $uppername) == $uppername) die('Error 0x00000004');
  
  $ip = $_SERVER['REMOTE_ADDR'];
  $time = time();
  if(!Utils::CheckString('0123456789ABCDEF', 32, 32, $password)) die('Error 0x00000001');
  if(!Utils::CheckString(SettingsManager::GetSetting(Settings::PLAYER_CHARS),
                         SettingsManager::GetSetting(Settings::PLAYER_MINLEN),
                         SettingsManager::GetSetting(Settings::PLAYER_MAXLEN), $username)) die('Error 0x00000002');
  if(!Utils::CheckString(SettingsManager::GetSetting(Settings::EMAIL_CHARS),
                         SettingsManager::GetSetting(Settings::EMAIL_MINLEN),
                         SettingsManager::GetSetting(Settings::EMAIL_MAXLEN), $email)) die('Error 0x00000003');
$query = <<<MYSQL
INSERT INTO  `Users` (
`playerID`,
`playerLogin`,
`playerName`,
`playerPassword`,
`playerAge`,
`playerBan`,
`playerDressing`,
`playerCoins`,
`playerInventory`,
`playerRedemptions`,
`playerBooks`,
`playerFlags`,
`playerLastLogin`,
`playerEMail`,
`playerRegristrationIP`,
`playerMood`
)
VALUES (
NULL, '{$username}', '{$username}', '{$password}', '{$time}', '0', '{$color}|0|0|0|0|0|0|0|0', '500', '{$color}%', '', '', '0', '0', '{$email}', '{$ip}', 'I am new to iCPro3'
);
MYSQL;
mysql_query($query);

$playerID = mysql_insert_id();
$query = <<<MYSQL
INSERT INTO  `iCP`.`Igloos` (
`playerID` ,
`iglooType` ,
`iglooFloor` ,
`iglooMusic` ,
`iglooFurniture` ,
`iglooInventory`
)
VALUES (
'{$playerID}',  '1',  '0',  '0',  '',  ''
);
MYSQL;
mysql_query($query);

$query = <<<MYSQL
INSERT INTO `Postcards` (
`postcardSender` ,
`postcardRecipient` ,
`postcardID` ,
`postcardRead` ,
`postcardAddition` ,
`postcardTimestamp`
)
VALUES (
'0', '{$playerID}', '125',  '0',  '',  '{$time}'
);
MYSQL;
mysql_query($query);

?>
<strong>You've been registred succesfully</strong><br />
Thank you for signing up at iCPv3 :)<br />
You can now log in... or let it be... or make Jokes about Penguins.<br />
<br />
<small>BTW: I don't know if you care, but your PlayerID is <strong><?= $playerID ?></strong>... just in Case ;)</small>

<?php updateStatus('ui-state-highlight', '<strong>Regristration Done:</strong> Successful'); ?>