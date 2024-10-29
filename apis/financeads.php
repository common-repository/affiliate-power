<?php
if (!defined('ABSPATH')) die; //no direct access

class Affiliate_Power_Api_Financeads {
    
    
    static public function addSubId($link, $subid) {
        
        if (strpos($link, "subid=") !== false) $link = preg_replace('@subid=[0-9a-z_\-]*@i', 'subid='.$subid, $link);
		elseif (strpos($link, 'url=') !== false) $link = str_replace('url=', 'subid='.$subid.'&url=', $link);
		elseif (strpos($link, "?") !== false) $link .= '&subid='.$subid;
		else $link .= '?subid='.$subid;
		
		return $link;
    }


	static public function checkLogin($user_id, $auth_key) {
		
		$StartDate = date('Y-m-d', time()-3600*24);
		$EndDate = date('Y-m-d');
		
		$report_url = 'https://data.financeads.net/api/statistics.php';
		$report_url .= '?site=l_all';
		$report_url .= '&time_from='.$StartDate; //inclusive
		$report_url .= '&time_to='.$EndDate; //exclusive
        $report_url .= '&format=xml';
        //if(is_numeric($wf_id)) $report_url .= '&w='.$wf_id; //Werbefläche ID
        $report_url .= '&user='.$user_id; // User ID
        $report_url .= '&key='.$auth_key; // key
                
		$http_answer = wp_remote_get($report_url);
		
	    if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) return false;
                
        $str_access = stripos(trim($http_answer['body']), 'Zugriff verweigert!');
        $str_wrongparam = stripos(trim($http_answer['body']), 'Wrong Parameter:');
                
        if (!is_bool($str_access) && $str_access == 0) return false;
        if (!is_bool($str_wrongparam) && $str_wrongparam == 0) return false;
		
		return true;
	}
	
	
	static public function downloadTransactions($fromTS, $tillTS) {
	    $options = get_option('affiliate-power-options');
	    if (!isset($options['fads-id']) || !isset($options['fads-key'])) return array();
	    $user_id = $options['fads-id'];
	    $auth_key = $options['fads-key'];
	    $wf_id = isset($options['fads-wfid']) ? $options['fads-wfid'] : null; 
	
		$fromTS_temp = $fromTS;
		$tillTS_temp = $tillTS; //get all in one call for now, change this in case of timeouts
		$output_transactions = array();
		
		while ($tillTS_temp <= $tillTS) {
		
			$StartDate = date('Y-m-d', $fromTS_temp);
			$EndDate = date('Y-m-d', $tillTS_temp);
			
			$report_url = 'https://data.financeads.net/api/statistics.php';
            $report_url .= '?site=l_all';
            $report_url .= '&time_from='.$StartDate; //inclusive
            $report_url .= '&time_to='.$EndDate; //exclusive
            $report_url .= '&format=xml';
            $report_url .= '&user='.$user_id; // User ID
            $report_url .= '&key='.$auth_key; // API key
            if (is_numeric($wf_id)) $report_url .= '&w='.$wf_id; //Werbefläche ID

            /*
			$http_params = array (
				'timeout' => 90,
				//'headers' => array('Content-Type' => 'text/html; charset=utf-8')
			);
			
			$http_answer = wp_remote_get($report_url, $http_params);
	
			if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) {
				//todo: error handling, mail to admin etc.
				return $output_transactions;
			}
			*/
			
			if(!class_exists("DOMDocument") || !class_exists("XMLReader")) {
				//todo: error handling, mail to admin etc.
				return $output_transactions;
			}
			
			$reader = new XMLReader();
            $read_result = $reader->open($report_url, null, LIBXML_PARSEHUGE);
            
            if (!$read_result) {
                //todo: error handling, mail to admin etc.
				return $output_transactions;
            }
            
            while ($reader->read()) {
                if ($reader->nodeType != XMLREADER::ELEMENT || $reader->localName != 'lead') continue;
                
                $node = $reader->expand();
                if ($node === false) continue;
                        
                $dom = new DomDocument();
                $n = $dom->importNode($node, true);
                $dom->appendChild($n);
                $xp = new DomXpath($dom);
                
                $number = substr($xp->query('/lead/l_oid')->item(0)->nodeValue, 0, 126); //some partners (smartbroker) have oversized numbers
                $datetime = $xp->query('/lead/l_datum')->item(0)->nodeValue;
                $sub_id = $xp->query('/lead/l_wfsubid')->item(0)->nodeValue;
                $shop_id = $xp->query('/lead/l_prid')->item(0)->nodeValue;
                $shop_name = $xp->query('/lead/bezeichnung')->item(0)->nodeValue;
                $price = $xp->query('/lead/l_value')->item(0)->nodeValue;
                $commission = $xp->query('/lead/l_provision')->item(0)->nodeValue;
                $checkdatetime_db = $xp->query('/lead/l_datum_eintrag')->item(0)->nodeValue;
                $status = $xp->query('/lead/l_status')->item(0)->nodeValue;
                $transaction_type = $xp->query('/lead/l_provision_art')->item(0)->nodeValue;
                
                //unique order_id
				$number .= "_".$transaction_type;

				
                if($transaction_type == 'f') 
                    $transaction_type='s';
                                
				if ($status == '2') {
					$status = 'Confirmed';
					$confirmed = $commission;
				}
				elseif ($status == '1') {
					$status = 'Open';
					$confirmed = 0;
				}
                else {     
					$status = 'Cancelled';
					$confirmed = 0;             
				}

				$output_transactions[] = array(
                    'network' => 'financeads', 
                    'number' => $number,
                    'datetime_db' => $datetime,
                    'sub_id' => $sub_id,
                    'shop_id' => $shop_id,
                    'shop_name' => $shop_name,
                    'transaction_type' => strtoupper($transaction_type),
                    'price' => $price,
                    'commission' => $commission,
                    'confirmed' => $confirmed,
                    'checkdatetime_db' => $checkdatetime_db,
                    'status' => $status
                );
            }
                        
			
			/*
			$dom = new DOMDocument();
			$dom->loadXML($http_answer['body']); //posted XML
			$arrTransactions = $dom->getElementsByTagName('lead');    
                        
			foreach ($arrTransactions as $transaction) {
			
				$number = $transaction->getElementsByTagName('l_oid')->item(0)->nodeValue;
				$datetime = $transaction->getElementsByTagName('l_datum')->item(0)->nodeValue.' '.$transaction->getElementsByTagName('l_zeit')->item(0)->nodeValue;
				$sub_id = $transaction->getElementsByTagName('l_wfsubid')->item(0)->nodeValue;
				$shop_id = $transaction->getElementsByTagName('l_prid')->item(0)->nodeValue;
				$shop_name = $transaction->getElementsByTagName('bezeichnung')->item(0)->nodeValue;
				$price = $transaction->getElementsByTagName('l_value')->item(0)->nodeValue;
				$commission = $transaction->getElementsByTagName('l_provision')->item(0)->nodeValue;
				$checkdatetime_db = $transaction->getElementsByTagName('l_datum_eintrag')->item(0)->nodeValue;
				$status = $transaction->getElementsByTagName('l_status')->item(0)->nodeValue;
                $transaction_type = $transaction->getElementsByTagName('l_provision_art')->item(0)->nodeValue;
				
				//unique order_id
				$number .= "_".$transaction_type;

				
                if($transaction_type == 'f') 
                    $transaction_type='s';
                                
				if ($status == '2') {
					$status = 'Confirmed';
					$confirmed = $commission;
				}
				elseif ($status == '1') {
					$status = 'Open';
					$confirmed = 0;
				}
                else {     
					$status = 'Cancelled';
					$confirmed = 0;             
				}

				$output_transactions[] = array(
                    'network' => 'financeads', 
                    'number' => $number,
                    'datetime_db' => $datetime,
                    'sub_id' => $sub_id,
                    'shop_id' => $shop_id,
                    'shop_name' => $shop_name,
                    'transaction_type' => strtoupper($transaction_type),
                    'price' => $price,
                    'commission' => $commission,
                    'confirmed' => $confirmed,
                    'checkdatetime_db' => $checkdatetime_db,
                    'status' => $status
                );
					
			} //foreach
			
			*/
			
			//prepare next request
			if ($tillTS_temp == $tillTS) break;
			$fromTS_temp = $tillTS_temp;
			$tillTS_temp += 3600*24*25;
		}
		
		return $output_transactions;
		
	} //function


}