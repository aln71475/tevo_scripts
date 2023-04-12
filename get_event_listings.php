<?php

require_once '../config.php';
//Here we are getting all the events for which we need listings
//this limit is temporary and will be removed
$sql = 'SELECT * FROM tevo_events limit 1';
$result = $conn->query($sql);

if ($result->num_rows > 0) {
	
	while($row = $result->fetch_assoc()) {
		
		$url = 'https://api.sandbox.ticketevolution.com/v9/listings?event_id='.$row['tevo_id'];
		$signature = base64_encode(hash_hmac('sha256', 'GET api.sandbox.ticketevolution.com/v9/listings?event_id='.$row['tevo_id'], 'fLvqQB3XL06+oSktNLlrmqsYheV0CjwKi/cxmHg6', true));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X-Token:d9f567481378940eceecf0d6dd8930f2',
			'X-Signature:'.$signature
		));
		//here making the curl get request
		$res = curl_exec($ch);
		$someArray = json_decode($res, true);
		
		//only run code if there is some data returned from the API
		if(isset($someArray) && $someArray['total_entries'] > 0){
			
			for($i=0;$i<$someArray['total_entries'];$i++){
				
				//if eticket data field is not true we don't wanna run code after it.
				if($someArray['ticket_groups'][$i]['eticket'] != true){
					continue;
				}
				
				$values = "'".$conn->real_escape_string($someArray['ticket_groups'][$i]['id'])."','".
				$conn->real_escape_string($row['tevo_id'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['section'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['row'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['retail_price'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['retail_price_inclusive'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['wholesale_price'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['public_notes'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['face_value'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['quantity'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['seller_cost'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['instant_delivery'])."','".
				$conn->real_escape_string($someArray['ticket_groups'][$i]['splits'])."'";
				$sql = "INSERT INTO `tevo_events_listing` (`listing_id`, `tevo_id`, `section`, `row_no`, `retail_price`,
				`retail_price_inclusive`, `wholesale_price`, `notes`, `face_value`, `quantity`, 
				`seller_cost`,`instant_delivery`,`splits`) VALUES ($values) 
				ON DUPLICATE KEY UPDATE
					section = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['section'])."', 
					row_no = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['row'])."', 
					retail_price = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['retail_price'])."', 
					retail_price_inclusive = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['retail_price_inclusive'])."', 
					wholesale_price = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['wholesale_price'])."', 
					notes = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['public_notes'])."', 
					face_value = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['face_value'])."', 
					quantity = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['quantity'])."', 
					seller_cost = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['seller_cost'])."', 
					instant_delivery = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['instant_delivery'])."', 
					splits = '" .$conn->real_escape_string($someArray['ticket_groups'][$i]['splits'])."' ";

				if(!$conn->query($sql)){
					die('data wasn\'t saved due to error.'.$conn->error.' '.$sql);
				}
			}
		}
		sleep(5);
	}
	
}

$conn->close();
?>