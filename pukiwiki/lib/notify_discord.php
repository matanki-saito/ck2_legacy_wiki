<?php

function pkwk_discord_notify($message, $footer = array()){

	global $notify_discord_channel_url;

	$timestamp = date("c", strtotime("now"));

	$json_data = json_encode([
	    // Username
	    "username" => "Notify wiki bot",

	    // Avatar URL.
	    // Uncoment to replace image set in webhook
	    //"avatar_url" => "https://ru.gravatar.com/userimage/28503754/1168e2bddca84fec2a63addb348c571d.jpg?size=512",

	    // Text-to-speech
	    "tts" => false,

	    // Embeds Array
	    "embeds" => [
	        [
	            // Embed Title
	            "title" => $footer['PAGE'],

	            // Embed Type
	            "type" => "rich",

	            // Embed Description
	            "description" => substr($message,0,150),

	            // URL of title link
	            "url" => $footer['URI'],

	            // Timestamp of embed must be formatted as ISO8601
	            "timestamp" => $timestamp,

	            // Embed left border color in HEX
	            "color" => hexdec( "b51414" ),

	            // Additional Fields array
	            "fields" => [
	                [
	                    "name" => "USER_AGENT",
	                    "value" => $_SERVER['HTTP_USER_AGENT'],
	                    "inline" => false
	                ],
	                [
	                    "name" => "REMOTE_ADDR",
	                    "value" => $_SERVER['REMOTE_ADDR'],
	                    "inline" => false
	                ]
	            ]
	        ]
	    ]

	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );


	$ch = curl_init( $notify_discord_channel_url );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_HEADER, 0);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

	$response = curl_exec( $ch );
	// If you need to debug, or find out why you can't send message uncomment line below, and execute script.
	curl_close( $ch );

	return true;
}