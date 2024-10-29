<?php
if (!defined('ABSPATH')) die; //no direct access


class Affiliate_Power_Apis {


    static public function checkNetworkLogin($network_name, $param1, $param2=false, $param3=false) {
	    $result = false;
	    include_once('apis/'.$network_name.'.php');
	    $class_name = 'Affiliate_Power_Api_'.ucwords($network_name);
		if (method_exists($class_name, 'checkLogin')) {
		    if ($param3) $result = $class_name::checkLogin($param1, $param2, $param3);
		    elseif ($param2) $result = $class_name::checkLogin($param1, $param2);
		    else $result = $class_name::checkLogin($param1);
		}
		return $result;
	}

	
	static public function downloadTransactionsQuick() {
		check_ajax_referer( 'affiliate-power-download-transactions', 'nonce' );
		
		global $wpdb;
		$transaction_count = $wpdb->get_var('SELECT count(*) FROM '.$wpdb->prefix.'ap_transaction');
		if ($transaction_count == 0) $downloadDays = 99;
		else $downloadDays = 3;
	
		self::downloadTransactions($downloadDays);
		
		die();
	}


	static public function downloadTransactions($days = 100) {
	
		global $wpdb;
		
		$fromTS = time()-3600*2-3600*24*$days; //$days in the psat
		$tillTS = time()-3600*2; //now in UTC
		
		$options = get_option('affiliate-power-options');
		$meta_options = get_option('affiliate-power-meta-options');
		
		do_action('affiliate-power-pre-download-transactions');
		
		foreach (Affiliate_Power_Settings::$network_data as $network_name => $network_data) {
		    include_once('apis/'.$network_name.'.php');
		    $class_name = 'Affiliate_Power_Api_'.ucwords($network_name);
		    if (method_exists($class_name, 'downloadTransactions')) {
		        $transactions = $class_name::downloadTransactions($fromTS, $tillTS);
		        foreach ($transactions as $transaction) self::handleTransaction($transaction);
		    }
		}
		
		do_action('affiliate-power-post-download-transactions');
	}
	
	
	static public function handleTransaction($transaction) {
		global $wpdb;
		
		$transaction['number'] = (string)$transaction['number'];
		$transaction['price'] = (float)$transaction['price'];
		$transaction['commission'] = (float)$transaction['commission'];
		$transaction['confirmed'] = (float)$transaction['confirmed'];
		
		if(empty($transaction['sub_id'])) $transaction['sub_id'] = 0;
		
		$sql = $wpdb->prepare('SELECT ap_transactionID, 
			TransactionId_network, 
			Commission, 
			TransactionStatus 
			FROM '.$wpdb->prefix.'ap_transaction
			WHERE TransactionId_network = %s
			AND network = %s
			LIMIT 1',
			$transaction['number'], $transaction['network']);
		
		$existing_transaction = $wpdb->get_row( $sql );
		
		//Transaktion existiert noch nicht => INSERT
		if ($existing_transaction == null) {
		
			$wpdb->insert( 
					$wpdb->prefix.'ap_transaction', 
					array( 
						'network' => $transaction['network'],
						'TransactionId_network' => $transaction['number'],
						'Date' => $transaction['datetime_db'],
						'SubId' => $transaction['sub_id'],
						'ProgramId' => $transaction['shop_id'],
						'ProgramTitle' => $transaction['shop_name'],
						'Transaction' => $transaction['transaction_type'],
						'Price' => $transaction['price'],
						'Commission' => $transaction['commission'],	
						'Confirmed' => $transaction['confirmed'],
						'CheckDate' => $transaction['checkdatetime_db'],
						'TransactionStatus' => $transaction['status']
					), 
					array( 
						'%s', //network
						'%s', //number	
						'%s', //datetime_db
						'%s', //sub_id
						'%d', //shop_id
						'%s', //shop_name
						'%s', //transaction_type
						'%f', //price
						'%f', //commission
						'%f', //confirmed
						'%s', //checkdatetime_db
						'%s' //status
					) 
				);
				
			do_action('affiliate_power_transaction_insert', $wpdb->insert_id, $transaction);
		
		}


		//Transaktion existiert bereits, aber der Status hat sich geändert => UPDATE
		elseif ($existing_transaction != null && $transaction['status'] != $existing_transaction->TransactionStatus) {
		
			$wpdb->update( 
				$wpdb->prefix.'ap_transaction', 
				array( 
					'CheckDate'  => $transaction['checkdatetime_db'],
					'Commission' => $transaction['commission'],	
					'Confirmed' => $transaction['confirmed'],
					'TransactionStatus' => $transaction['status']
				), 
				array( 'ap_transactionID' => $existing_transaction->ap_transactionID ), 
				array( 
					'%s',   //checkdatetime_db
					'%f',	// Commission
					'%f',	// Confirmed
					'%s',	// Status
				), 
				array( '%d' ) //ap_transactionID
			);
		}
	}

}
