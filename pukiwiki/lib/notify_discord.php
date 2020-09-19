<?php

function pkwk_discord_notify($message,$webhook_url, $footer = array()){

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
	            "title" => substr($footer['PAGE'] != null ? $footer['PAGE'] : 'undefined' ,0,50),

	            // Embed Type
	            "type" => "rich",

	            // Embed Description
	            "description" => substr($message != null ? $message : 'undefined',0,300),

	            // URL of title link
	            "url" => substr($footer['URI'] != null ? $footer['URI'] : 'undefined',0,300),

	            // Timestamp of embed must be formatted as ISO8601
	            "timestamp" => $timestamp,

	            // Embed left border color in HEX
	            "color" => hexdec( "3366ff" ),

	            // Additional Fields array
	            "fields" => [
	                [
	                    "name" => "USER_AGENT",
	                    "value" => $_SERVER['HTTP_USER_AGENT'] != null ? $_SERVER['HTTP_USER_AGENT'] : 'undefined',
	                    "inline" => false
	                ],
	                [
	                    "name" => "REMOTE_ADDR",
	                    "value" => $_SERVER['REMOTE_ADDR'] != null ? $_SERVER['REMOTE_ADDR'] : 'undefined',
	                    "inline" => false
					],
					[
	                    "name" => "X-Real-IP",
	                    "value" => $_SERVER['HTTP_X_REAL_IP'] != null ? $_SERVER['HTTP_X_REAL_IP'] : 'undefined',
	                    "inline" => false
					],
					[
	                    "name" => "X-Forwarded-For",
	                    "value" => $_SERVER['HTTP_X_FORWARDED_FOR'] != null ? $_SERVER['HTTP_X_FORWARDED_FOR'] : 'undefined',
	                    "inline" => false
	                ]
	            ]
	        ]
	    ]

	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );


	$ch = curl_init( $webhook_url );
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