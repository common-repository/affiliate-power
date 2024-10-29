<?php
if (!defined('ABSPATH')) die; //no direct access

class Affiliate_Power_Api_Digistore24 {
    
    
    static public function addSubId($link, $subid) {
    
        if (strpos($link, 'digistore24.com/redir') !== false) {
            $link = preg_replace('@(digistore24.com/redir/[0-9]+/[0-9a-z_\-]+)/?[0-9a-z_\-]*/?@i', '$1/'.$subid, $link);
        }
        elseif (strpos($link, 'digistore24.com/content') !== false) {
            $link = preg_replace('@(digistore24.com/content/[0-9]+/[0-9]+/[0-9a-z_\-]+)/?[0-9a-z_\-]*/?@i', '$1/'.$subid, $link);
        }
        elseif (strpos($link, 'http://go.') !== false || strpos($link, 'http://promo.') !== false) {
            $link = preg_replace('@digistore24.com/?[0-9a-z_\-]*@i', 'digistore24.com/'.$subid, $link);
        }
        elseif (strpos($link, '/#aff=') !== false) {
            if (strpos($link, 'cam=') !== false) $link = preg_replace('@cam=[0-9a-z_\-]*@i', 'cam='.$subid, $link);
            elseif (strpos($link, '?') !== false) $link .= '&cam='.$subid;
            else $link .= '?cam='.$subid;
        }
		
		return $link;
    }			
    

	static public function checkLogin($apikey) {
		require_once 'ds24_api.php';
		
		$api = DigistoreApi::connect($apikey);
		try { $data = $api->ping(); }
		catch (Exception $e) { return false; }
		return true;
	}
	
	
	static public function downloadTransactions($fromTS, $tillTS) {
		$options = get_option('affiliate-power-options');
	    if (!isset($options['digistore24-key'])) return array();
	    $apikey = $options['digistore24-key'];
		
		require_once 'ds24_api.php';
		$output_transactions = array();
		
		$StartDate = date('Y-m-d H:i:s', $fromTS);
		$EndDate = date('Y-m-d H:i:s', $tillTS);
		
		$api = DigistoreApi::connect($apikey);
		try { $data = $api->listPurchases( $StartDate, $EndDate ); }
		catch (Exception $e) {
			//todo: error handling, mail to admin etc.
			return array();
		}
		
		//print_r($data);
		
		foreach ($data->purchase_list as $transaction) {
		
			$number = $transaction->id;
			$datetime_db = $transaction->transaction_created_at;
			$sub_id = $transaction->campaignkey;
			$shop_id = $transaction->vendor_id;
			$shop_name = $transaction->merchant_name;
			$price = $transaction->transaction_amount;
			$commission = $transaction->total_affiliate_amount;
			$confirmed = $commission;
			$checkdatetime_db = $datetime_db;
			
		
			$output_transactions[] = array(
				'network' => 'digistore24', 
				'number' => $number,
				'datetime_db' => $datetime_db,
				'sub_id' => $sub_id,
				'shop_id' => $shop_id,
				'shop_name' => $shop_name,
				'transaction_type' => 'S',
				'price' => $price,
				'commission' => $commission,
				'confirmed' => $confirmed,
				'checkdatetime_db' => $checkdatetime_db,
				'status' => 'Confirmed'
			);
		
		} //foreach
		return $output_transactions;
	} //function


}