<?php

// Security as recommended for the bot

// Set the lower and upper limit of valid Telegram IPs.
// https://core.telegram.org/bots/webhooks#the-short-version
$telegram_ip_lower = '149.154.167.197';
$telegram_ip_upper = '149.154.167.233';

// Get the real IP.
$ip = $_SERVER['REMOTE_ADDR'];
foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR'] as $key) {
    $addr = @$_SERVER[$key];
    if (filter_var($addr, FILTER_VALIDATE_IP)) {
        $ip = $addr;
    }
}

// Make sure the IP is valid.
$lower_dec = (float) sprintf("%u", ip2long($telegram_ip_lower));
$upper_dec = (float) sprintf("%u", ip2long($telegram_ip_upper));
$ip_dec    = (float) sprintf("%u", ip2long($ip));
if ($ip_dec < $lower_dec || $ip_dec > $upper_dec) {
    die("Acceso restringido para la IP [{$ip}]");
}


// Load composer
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/credentials/global.php';


// I think there is a way to pass this variable
// using setCommandConfig?
define("OUTPUTLINES", 4);
	
// Define all paths for your custom commands in this array (leave as empty array if not used)
$commands_paths = [
	__DIR__ . '/Commands/',
];

try {
	// Create Telegram API object
	$telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

	// Add commands paths containing your custom commands
	$telegram->addCommandsPaths($commands_paths);
	
	// Enable admin users
	//$telegram->enableAdmins($admin_users);
	
	// Enable MySQL
	//$telegram->enableMySql($mysql_credentials);

	// Logging (Error, Debug and Raw Updates)
	Longman\TelegramBot\TelegramLog::initErrorLog(__DIR__ . "/{$bot_username}_error.log");
	//Longman\TelegramBot\TelegramLog::initDebugLog(__DIR__ . "/{$bot_username}_debug.log");
	Longman\TelegramBot\TelegramLog::initUpdateLog(__DIR__ . "/{$bot_username}_update.log");
	
	// Set custom Upload and Download paths
	//$telegram->setDownloadPath(__DIR__ . '/Download');
	//$telegram->setUploadPath(__DIR__ . '/Upload');

	// Here you can set some command specific parameters
	// e.g. Google geocode/timezone api key for /date command
	$telegram->setCommandConfig('quever', [
		'yt_api_key' => $yt_api_key,
		'channels' => $yt_channels
	]);


	// This bot is private, check the allowed groups:

	$POST = file_get_contents("php://input");
	$POST_DATA = json_decode($POST, true);

	$user_id = null;
	$type = $POST_DATA['message']['chat']['type'];
	if (isset($POST_DATA['message']['chat']['id'])) {
		$user_id = $POST_DATA['message']['chat']['id'];
	}

	if ($type == "group" || $type == "supergroup" || $type == "channel") {
		if (!in_array($user_id, $allowed_chans)) {
			$data = [
				'chat_id' => $user_id,
				'text'    => "Acceso restringido para el ID ".$user_id
			];
			Longman\TelegramBot\Request::sendMessage($data);
			exit();
		}
	}


	// Requests Limiter (tries to prevent reaching Telegram API limits)
	$telegram->enableLimiter();

	// Handle telegram webhook request
	$telegram->handle();

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
	// Silence is golden!
	//echo $e;
	// Log telegram errors
	Longman\TelegramBot\TelegramLog::error($e);

} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
	// Silence is golden!
	// Uncomment this to catch log initialisation errors
	//echo $e;
}


/**
 * VERY handy debug tool
 *
 * @param [mixed] $data
 * @return string
 */
function msg_dump($data) {
	ob_start();
	print_r($data);
	return ob_get_contents();
	ob_end_clean;
}