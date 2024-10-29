<?php
/*
PLUGIN NAME: Affiliate Power
PLUGIN URI: https://www.j-breuer.de/wordpress-plugins/affiliate-power/
DESCRIPTION: Import all your affiliate sales into your WordPress Backend. Analyze which posts, merchants and campaigns are creating your income.
AUTHOR: Jonas Breuer
AUTHOR URI: https://www.j-breuer.de
VERSION: 2.4.0
Text Domain: affiliate-power
Min WP Version: 4.6
Max WP Version: 6.3.1
*/
if (!defined('ABSPATH')) die; //no direct access

define('AFFILIATE_POWER_VERSION', '2.4.0');

define('AFFILIATE_POWER_DIR', dirname(__FILE__).'/');  
define('AFFILIATE_POWER_API_URL', 'https://ap-api.banana-content.de/api.php');

Affiliate_Power::prepare();


class Affiliate_Power {

    static function prepare() {
        
        $options = get_option('affiliate-power-options');
        
        if (is_dir(plugin_dir_path(__FILE__).'premium') && isset($options['licence-key'])) define('AFFILIATE_POWER_PREMIUM', true);
        else define('AFFILIATE_POWER_PREMIUM', false);
        
        if (AFFILIATE_POWER_PREMIUM) include_once('premium/affiliate-power-premium.php');

        include_once("affiliate-power-menu.php"); //admin menu
        include_once("affiliate-power-apis.php"); //APIs for transaction download
        include_once("affiliate-power-widget.php"); //dashboard widget, requires apis
        include_once("affiliate-power-cron.php");

        register_activation_hook(__FILE__, array('Affiliate_Power', 'activation'));
        register_deactivation_hook(__FILE__, array('Affiliate_Power', 'deactivation'));
        register_uninstall_hook(__FILE__, array('Affiliate_Power', 'uninstall'));
        
        add_action('init', array('Affiliate_Power', 'init'));
        
        add_action('wp_ajax_ap_download_transactions', array('Affiliate_Power_Apis', 'downloadTransactionsQuick'));

        add_filter('pre_set_site_transient_update_plugins', array('Affiliate_Power', 'checkVersion')); 
        add_filter('plugins_api', array('Affiliate_Power', 'getNewVersionInfo'), 10, 3); 
    }
    

	static function activation() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		//daily import at 03:00
		if (!wp_next_scheduled('affiliate_power_daily_event')) wp_schedule_event( strtotime('tomorrow')+3600*3, 'daily', 'affiliate_power_daily_event');
		
		//create options if this is a new installation
		$options = get_option('affiliate-power-options');
		if (!$options) add_option('affiliate-power-options', array());
		
		$sql = 'CREATE TABLE '.$wpdb->prefix.'ap_transaction (
				ap_transactionID bigint(20) unsigned AUTO_INCREMENT,
				network varchar(32),
				TransactionId_network varchar(128),
				Date datetime,
				SubId varchar(64) DEFAULT NULL,
				ProgramId int(10) unsigned,
				ProgramTitle varchar(1024),
				Transaction char(1),
				Price float unsigned,
				Commission float,
				Confirmed float,
				CheckDate datetime,
				TransactionStatus varchar(64),
				postID bigint(20) signed,
				source_url varchar(512),
				referer varchar(512) DEFAULT NULL,
				landing_page varchar(512),
				screen_width int(10),
				PRIMARY KEY  (ap_transactionID),
				UNIQUE KEY uniIdNetwork (TransactionId_network,network),
				KEY SubId (SubId),
				KEY TransactionStatus (TransactionStatus),
				KEY ProgramId (ProgramId),
				KEY network (network),
				KEY Date (Date),
				KEY postID (postID)
			);';
		dbDelta($sql);
	
		
		//meta options for infotext etc.
		$meta_options = get_option('affiliate-power-meta-options');
		if (!$meta_options) $meta_options = array();
		if (!isset($meta_options['installstamp']) || $meta_options['installstamp'] == '') $meta_options['installstamp'] = date('U');
		if (!isset($meta_options['hide-infotext'])) $meta_options['hide-infotext'] = 1;
		if (!isset($meta_options['show-infotext-timestamp'])) $meta_options['show-infotext-timestamp'] = time()+86400*6;
		
		$user = wp_get_current_user();
		$first_name = ($user->user_firstname != '') ? $user->user_firstname : $user->user_login;
		
		$meta_options['infotext'] = sprintf( __('<h3>Awesome %s, you made more than €1000 since installing Affiliate Power</h3><p>Congratulations to your thriving affiliate business. I hope Affiliate Power is helping you to keep track of all your money. If yes, could you please do me a big favor and give it a 5-star rating on WordPress.org? This would go a long way in spreading the word and boost my motivation.<br><em>~ Jonas Breuer</em></p><ul><li><a href="https://wordpress.org/support/plugin/affiliate-power/reviews/#new-post" target="_blank">Ok, you deserve it</a></li><li><a href="#" class="affiliate-power-postpone-infotext">No, maybe later</a></li><li><a href="#" class="affiliate-power-hide-infotext">I already did</a></li></ul>', 'affiliate-power'), $first_name );
		
		update_option('affiliate-power-meta-options', $meta_options);
		
		//welcome message when first install
		$version = get_option('affiliate-power-version', '0.0.0');
		if ($version == '0.0.0') add_action('admin_notices', array('Affiliate_Power', 'activationMessage'));
		else add_action('admin_notices', array('Affiliate_Power', 'updateMessage'));
		
		do_action('affiliate_power_activation');
		
	}
	
	
	static function deactivation() {
		wp_clear_scheduled_hook('affiliate_power_daily_event');
	}
	
	
	static function uninstall() {
		global $wpdb;
		$sql = 'DROP TABLE IF EXISTS '.$wpdb->prefix.'ap_transaction;';
		$wpdb->query($sql);
		delete_option('affiliate-power-meta-options');
		delete_option('affiliate-power-options');
		delete_option('affiliate-power-version');
		delete_option('affiliate-power-premium');
		
		do_action('affiliate_power_uninstall');
	}
	
	
	static function init() {
		
		//run activation if user updated the plugin
		$version = get_option('affiliate-power-version', '0.0.0');
		$premium = get_option('affiliate-power-premium', false);
		if ($version != AFFILIATE_POWER_VERSION || $premium != AFFILIATE_POWER_PREMIUM) {
			self::activation();
			update_option('affiliate-power-version', AFFILIATE_POWER_VERSION);
			update_option('affiliate-power-premium', AFFILIATE_POWER_PREMIUM);
		}
		
		//deactivate infotext
		if (isset($_GET['action']) && ($_GET['action'] == 'affiliate-power-hide-infotext' || $_GET['action'] == 'affiliate-power-postpone-infotext')) {
			$meta_options = get_option('affiliate-power-meta-options');
			$meta_options['hide-infotext'] = 1;
            if ($_GET['action'] == 'affiliate-power-postpone-infotext') $meta_options['show-infotext-timestamp'] = time()+86400*6;
            else $meta_options['show-infotext-timestamp'] = -1;
			update_option('affiliate-power-meta-options', $meta_options);
		}
		
		$options = get_option('affiliate-power-options');
		if (isset($options['licence-key']) && !empty($options['licence-key']) && !AFFILIATE_POWER_PREMIUM) {
		    add_action('admin_notices', array('Affiliate_Power', 'updatePremiumMessage'));
		}
		
		//debugging
		if (current_user_can('manage_options') && isset($_GET['ap_test'])) {
		    //Affiliate_Power::activation();
		    //Affiliate_Power_Apis::downloadTransactions();
            //Affiliate_Power_Cron::sendAdminMail();
            //Affiliate_Power_Cron::checkInfotext();
            //include_once('apis/tradedoubler.php');
            //print_r(Affiliate_Power_Api_Tradedoubler::downloadTransactions(time()-86400*20, time()));
		}
	}
	
	
	static function activationMessage() {
		ob_start();
		sprintf(__('<div id="message" class="updated"><img src="%s" alt="Affiliate Power" style="float:left; width:36px; margin:6px;" /><h2>Welcome to Affiliate Power!</h2><p>Whats next? First, you should enter your Affiliate network data on the <a href="%s">Settings Page</a>. Then, you can download your old sales and the plugin will create the first statistics.</p></div>', 'affiliate-power'), plugins_url('img/affiliate-power-36.png', __FILE__), admin_url('admin.php?page=affiliate-power-settings'));
		echo ob_get_clean();
	}
	
	
	static function updateMessage() {
	    $user = wp_get_current_user();
		$first_name = ($user->user_firstname != '') ? $user->user_firstname : $user->user_login;
		ob_start();
		sprintf(__('<div id="message" class="updated"><img src="%s" alt="Affiliate Power" style="float:left; width:36px; margin:6px;" /><h3>Affiliate Power Update</h3><p>Hey %s, thank you for updating to the new version of Affiliate Power. For security and compatibility reasons (and for all the cool new features) you should always use the newest version of the plugin.</p></div>', 'affiliate-power'), plugins_url('img/affiliate-power-36.png', __FILE__), $first_name);
		echo ob_get_clean();
	}
	
	
	static function updatePremiumMessage() {
        ob_start();
		echo '<div class="updated">'.__('<h2>Update to Affiliate Power Premium</h2><p>You entered a valid license key, but you didn\'t download the premium version yet. Please go to the <a href="update-core.php">update page</a> and update to the premium version. It might take up to 5 minutes, until WordPress informs you about the new version.</p>', 'affiliate-power').'</div>';
		echo ob_get_clean();
	}
	
	
	static function checkLicenceKey ($licence_key, $type='normal') {
	    $action = ($type == 'max_sales') ? 'max_sales' : 'check';
		$licence_key_hash = md5($licence_key);
		$http_answer = wp_remote_post(AFFILIATE_POWER_API_URL, array(
			'headers' => array('referer' => $_SERVER['HTTP_HOST']),
			'body' => array('action' => $action, 'key' => $licence_key_hash, 'plugin_version' => AFFILIATE_POWER_VERSION)
		));
		if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) return false;
		
		return $http_answer['body'];
	}
	
	
	static function checkVersion($transient) {
	
		if (empty($transient->checked)) {  
			return $transient;  
		}
		
		$options = get_option('affiliate-power-options');
		$premium_valid = false;
		if (isset($options['licence-key']) && !empty($options['licence-key'])) {
			$licence_status = self::checkLicenceKey($options['licence-key']);
			if ($licence_status == 'ok') $premium_valid = true;
		}
		
		if (!AFFILIATE_POWER_PREMIUM && $premium_valid) $updating_to_premium = true;
		else $updating_to_premium = false;
	
		$http_answer = wp_remote_post(AFFILIATE_POWER_API_URL, array('body' => array('action' => 'version')));
		if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) $new_version = AFFILIATE_POWER_VERSION;
		else $new_version = $http_answer['body'];
		
	    if ( (version_compare(AFFILIATE_POWER_VERSION, $new_version, '<') && AFFILIATE_POWER_PREMIUM) || $updating_to_premium ) {  
            $obj = new stdClass();  
            $obj->slug = 'affiliate-power';
            $obj->new_version = $new_version;  
            $obj->url = AFFILIATE_POWER_API_URL;  
            $obj->package = AFFILIATE_POWER_API_URL . '?key=' . md5($options['licence-key']);
            $transient->response['affiliate-power/affiliate-power.php'] = $obj;
        }  
        
        return $transient;
    }  


	static function getNewVersionInfo($false, $action, $arg) {
		
		if (isset($arg->slug) && $arg->slug == 'affiliate-power' && AFFILIATE_POWER_PREMIUM ) {  
			
			$http_answer = wp_remote_post(AFFILIATE_POWER_API_URL, array('body' => array('action' => 'info')));  
			if (is_wp_error($http_answer) || $http_answer['response']['code'] != 200) return $false;
			
			$information = unserialize($http_answer['body']);
			return $information;  
		}  
		return $false;  
	}
	
	
}