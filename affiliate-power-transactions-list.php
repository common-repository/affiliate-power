<?php
if (!defined('ABSPATH')) die; //no direct access


if (!class_exists('WP_List_Table')) require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Affiliate_Power_Transactions_List extends WP_List_Table {

	function __construct(){
		global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => __('Sale', 'affiliate-power'),     //singular name of the listed records
            'plural'    => __('Sales', 'affiliate-power'),    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) ); 
    }
	
	
	function column_default($item, $column_name){
	
		switch ($column_name) {
		
			case 'Price' :
				if ($item['Price'] == 0) $value = '---';
				else {
					$value = number_format($item['Price'], 2, ',', '.');
					$value .= ' €';
				}
				break;
				
				
			case 'Commission' :
				$value = number_format($item['Commission'], 2, ',', '.');
				$value .= ' €';
				break;
				
			case 'TransactionStatus' :
				if ($item['TransactionStatus'] == 'Cancelled') $value = _x('Cancelled', 'single', 'affiliate-power');
				elseif ($item['TransactionStatus'] == 'Confirmed') $value = _x('Confirmed', 'single', 'affiliate-power');
				else $value = _x('Open', 'single', 'affiliate-power');
				break;
				
			case 'germanCheckDate' :
				if ($item['TransactionStatus'] == 'Confirmed' || $item['TransactionStatus'] == 'Cancelled') $value = $item['germanCheckDate'];
				else $value = '---';
				break;
				
			case 'post_title' :
			    $value = __('<a href="https://www.affiliatepowerplugin.com/premium/" target="_blank">Premium Version Only</a>', 'affiliate-power');
				break;
				
			case 'referer' :
			case 'landing_page' :
			case 'device' :
			    $value = '';
			    break;
				
			default :
				$value = $item[$column_name];
				
		}
		
		$value = apply_filters('affiliate_power_transactions_list_column_default', $value, $item, $column_name, $this);
	
		if ($item['TransactionStatus'] == 'Cancelled') $color = '#FF0000';
		elseif ($item['TransactionStatus'] == 'Confirmed') $color = 'green';
		else $color = '#666666';
		
		$output = '<span style="color:'.$color.';">'.$value.'</span>';
		return $output;
	
    }
    
	
	function get_columns(){
		
        $columns = array(
            'germanDate'     => __('Date', 'affiliate-power'),
            'network'    => __('Network', 'affiliate-power'),
            'ProgramTitle'  => __('Merchant', 'affiliate-power'),
			'Price' => __('Price', 'affiliate-power'),
			'Commission'  => __('Commission', 'affiliate-power'),
			'TransactionStatus' => __('Status', 'affiliate-power'),
			'germanCheckDate' => __('Check Date', 'affiliate-power'),
			'post_title' => 'Post',
			'referer' => __('Referer', 'affiliate-power'),
			'landing_page' => __('Landing Page', 'affiliate-power'),
			'device' => __('Device', 'affiliate-power')
        );
        
        $columns = apply_filters('affiliate_power_transaction_columns', $columns);
        
        return $columns;
    }
	
	
	function get_sortable_columns() {
	
        $sortable_columns = array(
            'germanDate'     => array('Date',true),     //true means its already sorted
            'network'    => array('network',false),
            'ProgramTitle'  => array('ProgramTitle',false),
			'Price'  => array('Price',false),
			'Commission'  => array('Commission',false),
			'TransactionStatus'  => array('TransactionStatus',false),
			'germanCheckDate'  => array('CheckDate',false),
			'post_title'  => array('post_title',false),
			'referer' => array('referer',false),
			'landing_page' => array('landing_page',false),
			'device' => array('device',false)
        );
        
        $sortable_columns = apply_filters('affiliate_power_transaction_sortable_columns', $sortable_columns);
        
        return $sortable_columns;
    }
	
	
	function prepare_items($search = NULL) {
        
		global $wpdb;
        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $current_page = $this->get_pagenum();

		$orderby = (!empty($_REQUEST['orderby']) && ctype_alnum($_REQUEST['orderby'] )) ? $_REQUEST['orderby'] : 'Date'; //If no sort, default to date
        $order = (isset($_REQUEST['order']) && $_REQUEST['order'] == 'asc') ?  'asc' : 'desc'; //If no order, default to asc
		
		if( $search != NULL ){
			$additional_where = $wpdb->prepare(' AND (network like "%%%s%%" OR ProgramTitle like "%%%s%%" OR TransactionStatus like "%%%s%%" ) ', $search, $search, $search);
		}
		else $additional_where = '';
	
		$sql = ' 
		SELECT ap_transaction.ap_transactionID,
			   ap_transaction.network,
			   ap_transaction.Date,
			   date_format(ap_transaction.Date, "%d.%m.%Y - %T") AS germanDate,
			   ap_transaction.SubId,
			   ap_transaction.ProgramTitle,
			   ap_transaction.Price,
			   ap_transaction.Commission,
			   ap_transaction.Confirmed,
			   ap_transaction.TransactionStatus,
			   if(ap_transaction.TransactionStatus = "Open", "1970-0-0", ap_transaction.CheckDate) as CheckDate,
			   date_format(ap_transaction.CheckDate, "%d.%m.%Y") AS germanCheckDate
		FROM '.$wpdb->prefix.'ap_transaction ap_transaction
		WHERE 1
		'.$additional_where.'
		ORDER BY '.$orderby.' '.$order.'
		LIMIT '.(($current_page-1)*$per_page).', 20';
		
		$sql = apply_filters('affiliate_power_transaction_sql', $sql);
		
		$transactionData = $wpdb->get_results($sql, ARRAY_A);
        $total_items = $wpdb->get_var('select count(*) from '.$wpdb->prefix.'ap_transaction');
        //$transactionData = array_slice($transactionData,(($current_page-1)*$per_page),$per_page);
        $this->items = $transactionData;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }	

}