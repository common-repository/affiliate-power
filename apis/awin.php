<?php
if (!defined('ABSPATH')) die; //no direct access

class Affiliate_Power_Api_Awin {
    
    
    static public function addSubId($link, $subid) {
        
        if (strpos($link, 'clickref')) $link = preg_replace('@clickref=?[0-9a-z\-_]*@i', 'clickref='.$subid, $link);
        else {
            if (strpos($link, '&p=') !== false) $link = str_replace('&p=', '&clickref='.$subid.'&p=', $link);         
            elseif (strpos($link, '?') !== false) $link .= '&clickref='.$subid;
            else $link .= '?clickref='.$subid;
        }
		
		return $link;
    }


	static public function checkLogin($publisher_id, $token) {
	
	    $report_url = 'https://api.awin.com/accounts?accessToken='.$token;
	    $http_answer = wp_remote_get($report_url);

		if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) return false;
		
		$str_report = $http_answer['body'];
		$obj_report = json_decode($str_report);
		
		if (empty($obj_report) || !is_object($obj_report) ) return false;
		
		return true;
		
		//check for exact account, caused too many false positives, so we are fine if it's a valid token now
		/*
		foreach ($obj_report->accounts as $account) {
		    if ($account->accountId == $publisher_id) return true;
		}
		
		return false;
		*/
	}
	
	
	static public function downloadTransactions($fromTS, $tillTS) {
	
	    $options = get_option('affiliate-power-options');
	    if (!isset($options['awin-id']) || !isset($options['awin-token'])) return array();
	    $publisher_id = $options['awin-id'];
	    $token = $options['awin-token'];
	    $filter_referer = isset($options['awin-referer']) ? $options['awin-referer'] : null;
	
	    $output_transactions = array();
	    $fromTS_temp = $fromTS;
		$tillTS_temp = min($tillTS, $fromTS + 3600*24*20);
		
		//get shop names in a nice array, so we only need one API call
		$shop_names = array();
		$report_url = 'https://api.awin.com/publishers/'.$publisher_id.'/programmes?relationship=joined&accessToken='.$token;
	    $http_answer = wp_remote_get($report_url);
	    
	    if (!is_wp_error($http_answer) && $http_answer['response']['code'] == 200) {
		    $arr_report = json_decode($http_answer['body']);
		    if (!empty($arr_report) && is_array($arr_report)) {
		        foreach($arr_report as $shop) {
		            $shop_names[$shop->id] = $shop->name;
		        }
		    }
		}
	    
	    while ($tillTS_temp <= $tillTS) {
	    
            $StartDate = date('Y-m-d', $fromTS_temp) . 'T00%3A00%3A00';
            $EndDate   = date('Y-m-d', $tillTS_temp) . 'T00%3A00%3A00';
    
            $report_url = 'https://api.awin.com/publishers/'.$publisher_id.'/transactions/?startDate='.$StartDate.'&endDate='.$EndDate.'&timezone=Europe/Berlin&dateType=transaction&accessToken='.$token;
        
            $http_answer = wp_remote_get($report_url);
        
            if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) {
                return $output_transactions;
            }
        
            $str_report = $http_answer['body'];
            $arr_report = json_decode($str_report);
            //print_r($arr_report);
        
            if (!is_array($arr_report)) {
                 return $output_transactions;
            }
        
            foreach($arr_report as $transaction) {
            
                if (!empty($filter_referer) && strpos($transaction->publisherUrl, $filter_referer) === false) continue;
            
                $shop_name = isset($shop_names[$transaction->advertiserId]) ? $shop_names[$transaction->advertiserId] : $transaction->advertiserId;
                
                $datetime_db = str_replace('T', ' ', $transaction->transactionDate);
            
                if (isset($transaction->clickRefs->clickRef)) $sub_id = $transaction->clickRefs->clickRef;
                else $sub_id = 0;
            
                if (isset($transaction->saleAmount->amount) && $transaction->saleAmount->amount > 0) {
                    $transaction_type = 'S';
                    $price = $transaction->saleAmount->amount;
                }
                else {
                    $transaction_type = 'L';
                    $price = 0;
                }
            
                if ($transaction->commissionStatus == 'approved') {
                    $status = 'Confirmed';
                    $confirmed = $transaction->commissionAmount->amount;
                }
                else {
                    if ($transaction->commissionStatus == 'pending') $status = 'Open';
                    else $status = 'Cancelled';
                    $confirmed = 0;
                }
            
                if(!empty($transaction->validationDate)) $checkdatetime_db = str_replace('T', ' ', $transaction->validationDate);
                else $checkdatetime_db = $datetime_db;

        
                $output_transactions[] = array(
                    'network' => 'awin', 
                    'number' => $transaction->id,
                    'datetime_db' => $datetime_db,
                    'sub_id' => $sub_id,
                    'shop_id' => $transaction->advertiserId,
                    'shop_name' => $shop_name,
                    'transaction_type' => $transaction_type,
                    'price' => $price,
                    'commission' => $transaction->commissionAmount->amount,
                    'confirmed' => $confirmed,
                    'checkdatetime_db' => $checkdatetime_db,
                    'status' => $status
                );
            
            }
            
            if ($tillTS_temp == $tillTS) break;
            $fromTS_temp = $tillTS_temp;
			$tillTS_temp = min($tillTS, $tillTS_temp + 3600*24*20);
        }
        
        
        return $output_transactions;
        
    }

}


?>