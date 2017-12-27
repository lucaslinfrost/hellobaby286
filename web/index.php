<?php
// Line

require_once('./LINEBotTiny.php');

// Token in Heroku
$channelAccessToken = getenv('LINE_CHANNEL_ACCESSTOKEN');
$channelSecret = getenv('LINE_CHANNEL_SECRET');

$client = new LINEBotTiny($channelAccessToken, $channelSecret);
foreach ($client->parseEvents() as $event) {
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':
                	$m_message = $message['text'];
                	if($m_message != "")
                	{						
						if(preg_match("/^\抽/i", $m_message))	// 抽開頭的字樣
						{			
							$image_link = GetImgurIamge($m_message);	// 取得圖片網址
							if($image_link == "")	// 無法取得則給 DialogFlow 回應
							{
								$m_message = "聽不懂在抽什麼啦！";	// 給下方 DialogFlow 使用											
							}
							else
							{
								$client->replyMessage(array(
									'replyToken' => $event['replyToken'],
									'messages' => array(
									array(								
									'type' => 'image', 						// 訊息類型 (圖片)
									'originalContentUrl' => $image_link, 	// 回復圖片
									'previewImageUrl' => $image_link 		// 回復的預覽圖片									
									))));										
									
									return;	
							}																					
						}
						
						// DialogFlow
						{							
							$client->replyMessage(array(
							'replyToken' => $event['replyToken'],
							'messages' => array(
								array(
									'type' => 'text',
									'text' => GetDialogFlowWord($m_message)
								)
							)
							));									
						}													
                	}
                    break;                
            }
            break;
        default:
            error_log("Unsupporeted event type: " . $event['type']);
            break;
    }
};

function GetImgurIamge($m_message)
{
	// imgur
	// 取得相本 ID & Key 
	// Token in Heroku
	$imgur_client_id = getenv('imgur_client_id');
	$imgur_client_secret = getenv('imgur_client_secret');
	
	if(preg_match("/^\抽\$/i", $m_message) || preg_match("/^\抽咩/i", $m_message) || preg_match("/^\抽正咩/i", $m_message) || preg_match("/^\抽妹/i", $m_message) || preg_match("/^\抽正妹/i", $m_message) || preg_match("/^\抽女/i", $m_message) || preg_match("/^\抽美/i", $m_message))
		$imgur_client_album_girl = getenv('imgur_client_album_girl');
	else if(preg_match("/^\抽男/i", $m_message) ||preg_match("/^\抽帥/i", $m_message))
		$imgur_client_album_girl = getenv('imgur_client_album_boy');
	else
		return "";
	 
	// Init imgur
	require_once('./Imgur_Src/Imgur.php');
	$Imgur = new Imgur($imgur_client_id, $imgur_client_secret);

	// get images
	$json_string = $Imgur->album($imgur_client_album_girl)->images();
	$image_array = $json_string["data"];

	// get igamges link array
	$img_link_array = array();
	foreach ($image_array as $key => $value) 
	{
		array_push($img_link_array, $value['link']);
	}

	// random pick 
	if(sizeof($img_link_array) - 1 > 0)
	{
		mt_srand((double)microtime() * 1000000);
		$randval = mt_rand(0, sizeof($img_link_array) - 1);
		return $img_link_array[$randval];
	}
}	

function GetDialogFlowWord($Word)
{
	require_once __DIR__.'/vendor/autoload.php';

	require_once('./DialogFlow_Src/DialogFlow_Client.php');	

	// Token in Heroku
	$DialogFlow_Secret = getenv('DialogFlow_Secret');	

	try {
			$client = new DialogFlow\DialogFlow_Client($DialogFlow_Secret);
		
		    $query = $client->get('query', [
			'query' => $Word,
			'sessionId' => '1234567890',
			'lang' => 'zh-TW',
		]);

		$response = json_decode((string) $query->getBody(), true);
		return $response['result']['fulfillment']['speech'];		
	} 
	catch (\Exception $error)
	{
		echo $error->getMessage();
	}	
}