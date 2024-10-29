<?php
if (!defined('ABSPATH')) die; //no direct access

class Affiliate_Power_Api_Belboon {

    static public function addSubId($link, $subid) {
    
        if (strpos($link, 'belboon') !== false) {
            if (strpos($link, 'subid')) $link = preg_replace('@subid=[0-9a-z\-_]+@i', 'subid='.$subid, $link);
            else {
                if (strpos($link, '/&deeplink=') !== false) $link = str_replace('/&deeplink=', '/subid='.$subid.'&deeplink=', $link);
                else $link .= '/subid='.$subid;
            }
        }
        
        else {
            if (strpos($link, 'smc1')) $link = preg_replace('@smc1=[0-9a-z\-_]+@i', 'smc1='.$subid, $link);
            else {
                if (strpos($link, '&tst=') !== false) $link = str_replace('&tst=', '&smc1='.$subid.'&tst=', $link);
                elseif (strpos($link, '&trg=') !== false) $link = str_replace('&trg=', '&smc1='.$subid.'&trg=', $link);
                else $link .= '&smc1='.$subid;
            }
        }
		
		return $link;
    }


	static public function checkLogin($userid, $key) {
	    
	    $start_date = date('d.m.Y', time()-86400*3);
		$end_date = date('d.m.Y', time());
	    
        $report_url = 'https://export.service.belboon.com/'.$key.'/reporttransactions_'.$userid.'.xml?filter[currencycode]=EUR&filter[zeitraumvon]='.$start_date.'&filter[zeitraumbis]='.$end_date.'&filter[zeitraumAuswahl]=absolute';
        
        $http_answer = wp_remote_get($report_url, array('timeout' => 10));
        
        if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) {
             return false;
        }
		
		return true;
	}
	
	
	
	public static function downloadTransactions($fromTS, $tillTS) {
	
	    $options = get_option('affiliate-power-options');
	    
	    if (!isset($options['belboon-userid']) || !isset($options['belboon-key'])) return array();
	    $userid = $options['belboon-userid'];
	    $key = $options['belboon-key'];
	    
	    $filter_adspace = isset($options['belboon-platform']) ? $options['belboon-platform'] : '';
	    $arr_filter_adspace = explode(',', $filter_adspace);
		$arr_filter_adspace = array_map('trim', $arr_filter_adspace);
		$arr_filter_adspace = array_map('strtolower', $arr_filter_adspace);
	    
	    $fromTS = max($fromTS, strtotime('2020-05-18')); //don't use new API for sales prior switch date
	    
	    $start_date = date('d.m.Y', $fromTS+86400); //belboon API is limited to <100 days, so we cut one off
		$end_date = date('d.m.Y', $tillTS);
	    
        $report_url = 'https://export.service.belboon.com/'.$key.'/reporttransactions_'.$userid.'.xml?filter[currencycode]=EUR&filter[zeitraumvon]='.$start_date.'&filter[zeitraumbis]='.$end_date.'&filter[zeitraumAuswahl]=absolute';
        
        $http_answer = wp_remote_get($report_url, array('timeout' => 20));
        
        if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) {
             //todo: error handling, mail to admin etc.
             return array();
        }
        
        //print_r($http_answer['body']);
        
        $output_transactions = array();
        $dom = new DOMDocument();
		$dom->loadXML($http_answer['body']);
		
		$arr_transactions = $dom->getElementsByTagName('transaction');
		foreach ($arr_transactions as $transaction) {
		
            $number = $transaction->getElementsByTagName('conversion_uniqid')->item(0)->nodeValue;
            $datetime_db = $transaction->getElementsByTagName('conversion_tracking_time')->item(0)->nodeValue;
            $sub_id = $transaction->getElementsByTagName('click_subid')->item(0)->nodeValue;
            $shop_id = $transaction->getElementsByTagName('advertiser_id')->item(0)->nodeValue;
            $shop_name = $transaction->getElementsByTagName('advertiser_label')->item(0)->nodeValue;
            $price = $transaction->getElementsByTagName('conversion_order_value_eur')->item(0)->nodeValue;
            $commission = $transaction->getElementsByTagName('conversion_commission_total_eur')->item(0)->nodeValue;
            $checkdatetime_db = $transaction->getElementsByTagName('conversion_last_modified_time')->item(0)->nodeValue;
            $status = $transaction->getElementsByTagName('status')->item(0)->nodeValue;
            $transaction_type = $transaction->getElementsByTagName('conversion_target_type')->item(0)->nodeValue;
            $adspace_label = $transaction->getElementsByTagName('partner_adspace_label')->item(0)->nodeValue;
            
            $adspace_label = strtolower($adspace_label);
            if (!empty($filter_adspace) && !in_array($adspace_label, $arr_filter_adspace)) continue;
            
            if ($status == 'approved') $status = 'confirmed';
            if ($status == 'rejected' || $status == 'canceled') $status = 'cancelled';
            $status = ucwords($status);
              
            if ($status == 'Confirmed') $confirmed = $commission;
            else $confirmed = 0;
            
            if (empty($checkdatetime_db)) $checkdatetime_db = $datetime_db;
            
            $transaction_type = strtoupper(substr($transaction_type, 0, 1));    

            $output_transactions[] = array(
                'network' => 'belboon', 
                'number' => $number,
                'datetime_db' => $datetime_db,
                'sub_id' => $sub_id,
                'shop_id' => $shop_id,
                'shop_name' => $shop_name,
                'transaction_type' => $transaction_type,
                'price' => $price,
                'commission' => $commission,
                'confirmed' => $confirmed,
                'checkdatetime_db' => $checkdatetime_db,
                'status' => $status
            );
		}
        
		return $output_transactions;
		
	} //function


}

