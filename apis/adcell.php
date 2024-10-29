<?php
if (!defined('ABSPATH')) die; //no direct access

class Affiliate_Power_Api_Adcell {
    
    
    static public function addSubId($link, $subid) {
    
        if (strpos($link, 'bid=') !== false) {
            if (preg_match('/bid=[0-9]+\-[0-9]+\-[0-9]/', $link)) $link = preg_replace('/bid=([0-9]+)\-([0-9]+)\-[0-9]+/', 'bid=${1}-${2}-'.$subid, $link);
            else $link = preg_replace('/bid=([0-9]+)\-([0-9]+)/', 'bid=${1}-${2}-'.$subid, $link);
        }
        elseif (strpos($link, 't.adcell.com') !== false) {
            if (stripos($link, 'subId')) $link = preg_replace('@subId=?[0-9a-z\-_]*@i', 'subId='.$subid, $link);
            else $link = str_replace('&fp=', '&subId='.$subid.'&fp=', $link);
        }
        else {
            if (strpos($link, 'subid') !== false) $link = preg_replace('@subid/[0-9a-z_\-]+@i', 'subid/'.$subid, $link);
            else {
                if (strpos($link, 'encodingId') !== false) $link = preg_replace('@encodingId/([0-9a-z_\-]+)@', 'encodingId/${1}/subid/'.$subid, $link);
                else $link = preg_replace('@slotId/([0-9]+)@', 'slotId/${1}/subid/'.$subid, $link);
            }
        }
        
        return $link;
    }


	static public function checkLogin($username, $password) {
	
	    $token = self::getToken($username, $password);
	    if (!$token) return false;
		
		$StartDate = time()-3600*24;
		$EndDate = time();

		$report_url = 'http://www.adcell.de/csv_affilistats.php?sarts=x&pid=a&status=a&subid=&eventid=a';
		$report_url .= '&timestart='.$StartDate;
		$report_url .= '&timeend='.$EndDate;
		$report_url .= '&uname='.$username;
		$report_url .= '&pass='.$password;

		$http_answer = wp_remote_get($report_url);
		
		if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) return false;
		$str_report = $http_answer['body'];
		if (strpos($str_report, 'Fehler - keine gültigen Rechte!') !== false) return false;
		
		
		return true;
	}
	


	static public function downloadTransactions($fromTS, $tillTS) {
	
		$options = get_option('affiliate-power-options');
	    if (!isset($options['adcell-username']) || !isset($options['adcell-password'])) return array();
	    $username = $options['adcell-username'];
	    $password = $options['adcell-password'];
	    $referer_filter = $options['adcell-referer-filter'];
		
		$output_transactions = array();
		
		$token = self::getToken($username, $password);
	    if (!$token) return array();
			
		$StartDate = date('Y-m-d', $fromTS);
		$EndDate = date('Y-m-d', $tillTS);

		$json_url = 'https://www.adcell.de/api/v2/affiliate/statistic/byCommission?token='.$token.'&startDate='.$StartDate.'&endDate='.$EndDate.'&rows=1000&page=1';

		$http_answer = wp_remote_get($json_url);
		
		if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) {
			//todo: error handling, mail to admin etc.
			return array();
		}
		
		$obj_transactions = json_decode($http_answer['body']);
		
		if (empty($obj_transactions) || !isset($obj_transactions->status) || $obj_transactions->status != 200 ) {
			//todo: error handling, mail to admin etc.
			return array();
		}
		
		foreach($obj_transactions->data->items as $transaction) {
		
		    if ($referer_filter) {
				$arr_referer = parse_url($transaction->referer);
				$arr_page = parse_url(home_url('/'));
				if ($arr_referer['host'] != $arr_page['host']) continue;
			}
		
		    $transaction_type = substr($transaction->eventType, 0, 1);
		    
		    if (!empty($transaction->changeTime)) $checkdatetime_db = $transaction->changeTime;
		    else $checkdatetime_db = $transaction->createTime;
		    
		    if ($transaction->status == 'accepted') {
		        $status = 'Confirmed';
		        $confirmed = $transaction->totalCommission;
		    }
		    else {
		        $status = ucwords($transaction->status);
		        $confirmed = 0;
		    }
		
		    $output_transactions[] = array(
                'network' => 'adcell', 
                'number' => $transaction->commissionId,
                'datetime_db' => $transaction->createTime,
                'sub_id' => $transaction->subId,
                'shop_id' => $transaction->programId,
                'shop_name' => $transaction->programName,
                'transaction_type' => $transaction_type,
                'price' => $transaction->totalShoppingCart,
                'commission' => $transaction->totalCommission,
                'confirmed' => $confirmed,
                'checkdatetime_db' => $checkdatetime_db,
                'status' => $status
			);
		}

		return $output_transactions;
		
	} //function


    static private function getToken($username, $password) {
        $json_url = 'https://www.adcell.de/api/v2/user/getToken?userName='.$username.'&password='.$password;
        
        $http_answer = wp_remote_get($json_url);
        if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) {
            return false;
        }
        
        $obj_token = json_decode($http_answer['body']);
        if ($obj_token->status != 200) {
            return false;
        }
        
        $token = $obj_token->data->token;
        if (empty($token) || !ctype_alnum($token)) {
            return false;
        }
        
        return $token;
    }

}