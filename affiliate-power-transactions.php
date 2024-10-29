<?php
if (!defined('ABSPATH')) die; //no direct access

Affiliate_Power_Transactions::prepare();



class Affiliate_Power_Transactions {

    static function prepare() {
        add_action('wp_ajax_ap_export_csv', array('Affiliate_Power_Transactions', 'exportTransactions')); //for ap_download_transactions see affiliate-power.php
    }

	static function transactionsPage() {
	
		include_once('affiliate-power-transactions-list.php');
		$transactionsList = new Affiliate_Power_Transactions_List();
		
		if (isset($_GET['s'])) $transactionsList->prepare_items($_GET['s']);
        else $transactionsList->prepare_items();
		
		$options = get_option('affiliate-power-options');
		
		//Infotext
		$meta_options = get_option('affiliate-power-meta-options');
		if (isset($meta_options['infotext']) && $meta_options['hide-infotext'] == 0) {
			echo '<div class="updated">'.$meta_options['infotext'].'</div>';
		}
		
		?>
		<div class="wrap">
			
			<div class="icon32" style="background:url(<?php echo plugins_url('affiliate-power/img/affiliate-power-36.png'); ?>) no-repeat;"><br/></div>
			<?php _e('<h2>Affiliate Power Sales</h2>', 'affiliate-power'); ?>
			
			<form id="sales-filter" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
				<?php $transactionsList->search_box('Suche', 'sales'); ?>
				<?php $transactionsList->display() ?>
			</form>
			
			<input type="button" class="button-primary" style="float:left; width:170px;" id="button_download_transactions" value="<?php _e('Update Sales', 'affiliate-power'); ?>" /><span class="spinner" id="spinner1" style="float:left;"></span>
			<br /><br />
			<input type="button" class="button" style="float:left; width:170px; clear:left;" id="button_export_csv" value="<?php _e('CSV/Excel Download', 'affiliate-power'); ?>" /><span class="spinner" id="spinner2" style="float:left;"></span>
			
			<script type="text/javascript">
				jQuery(document).ready(function($) {
				
					$("#button_download_transactions").bind("click", function(e){
						$(this).val('<?php _e('Please wait', 'affiliate-power'); ?>...');
						$('#spinner1').css('display', 'block');
						$.post(ajaxurl, { action: 'ap_download_transactions', nonce: '<?php echo wp_create_nonce( 'affiliate-power-download-transactions' ) ?>' }, function(response) {
							location.reload();
						});
					});
					
					$("#button_export_csv").bind("click", function(e){
						$(this).val('<?php _e('Please wait', 'affiliate-power'); ?>...');
						$('#spinner2').css('display', 'block');
						$.post(ajaxurl, { action: 'ap_export_csv', nonce: '<?php echo wp_create_nonce( 'affiliate-power-export-csv' ) ?>'}, function(response) {
							$("#button_export_csv").val('<?php _e('CSV/Excel Download', 'affiliate-power'); ?>');
							$("body").append("<iframe src='<?php echo plugins_url( "affiliate-power/csv-download.php", dirname(__FILE__ )); ?>' style='display: none;' ></iframe>")
							$('#spinner2').css('display', 'none');
						});
					});
					
				});
			</script>
			
		</div>
		<?php
	}
	
	
	static function exportTransactions() {
	
		check_ajax_referer( 'affiliate-power-export-csv', 'nonce' );
		
		global $wpdb;
		
		$csv_header = __('Id;Network;Date;Merchant;SubId;Type;Price;Commission;Status;Check Date', 'affiliate-power') . "\r\n";
		$csv_header = apply_filters('affiliate_power_transaction_export_header', $csv_header);
		
		$csv_content = $csv_header;
		
		$sql = ' 
            SELECT ap_transaction.ap_transactionID,
                   ap_transaction.network,
                   ap_transaction.Date,
                   date_format(ap_transaction.Date, "%d.%m.%Y - %T") AS germanDate,
                   ap_transaction.ProgramTitle,
                   ap_transaction.SubId,
                   ap_transaction.Transaction,
                   ap_transaction.Price,
                   ap_transaction.Commission,
                   ap_transaction.TransactionStatus,
                   date_format(ap_transaction.CheckDate, "%d.%m.%Y") AS germanCheckDate
            FROM '.$wpdb->prefix.'ap_transaction ap_transaction
            ORDER BY Date DESC
        ';
		
		$sql = apply_filters('affiliate_power_transaction_export_sql', $sql);
		
		$transactions = $wpdb->get_results($sql, ARRAY_A);
		
		foreach ($transactions as $transaction) {
			$transaction['Price'] = str_replace('.', ',', $transaction['Price']);
			$transaction['Commission'] = str_replace('.', ',', $transaction['Commission']);
			$transaction['ProgramTitle'] = '"'.$transaction['ProgramTitle'].'"';
			unset($transaction['Date']); //this was just for order by
			$csv_content .= implode(';', $transaction) . "\r\n";
		}
		
		if (!session_id()) session_start();
		$_SESSION['affiliate-power-csv'] = $csv_content;
		
		die();
	}
	
}
