<?php
if (!defined('ABSPATH')) die; //no direct access


Affiliate_Power_Menu::prepare();


class Affiliate_Power_Menu {

    static function prepare() {
    
        //include menu pages
        include_once("affiliate-power-settings.php");
        include_once('affiliate-power-transactions.php');
        include_once("affiliate-power-statistics.php");
        
        add_action('admin_menu', array('Affiliate_Power_Menu', 'adminMenu'));
        add_action('admin_enqueue_scripts', array('Affiliate_Power_Menu', 'addJs'));

        add_filter('plugin_action_links_affiliate-power/affiliate-power.php', array('Affiliate_Power_Menu', 'addPluginLinks'), 10, 2);
    }
	

	static function adminMenu() {
		add_menu_page('Affiliate Power', 'Affiliate Power', 'manage_options', 'affiliate-power', array('Affiliate_Power_Menu', 'dummyFunction'), plugins_url( 'affiliate-power/img/affiliate-power-16.png' ));
		add_submenu_page('affiliate-power', __('Affiliate Power Sales', 'affiliate-power'), __('Leads / Sales', 'affiliate-power'), 'manage_options', 'affiliate-power', array('Affiliate_Power_Transactions', 'transactionsPage') );
		add_submenu_page('affiliate-power', __('Affiliate Power Statistics', 'affiliate-power'), __('Statistics', 'affiliate-power'), 'manage_options', 'affiliate-power-statistics', array('Affiliate_Power_Statistics', 'statisticsPage') );
		add_submenu_page('affiliate-power', __('Affiliate Power Settings', 'affiliate-power'), __('Settings', 'affiliate-power'), 'manage_options', 'affiliate-power-settings', array('Affiliate_Power_Settings', 'optionsPage') );
	}
	
	
	
	static function dummyFunction() {
	}
	
	
	static function addJs() {
	
		wp_enqueue_script('affiliate-power-menu', plugins_url('affiliate-power-menu.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'), AFFILIATE_POWER_VERSION, true);
		
		wp_localize_script( 'affiliate-power-menu', 'objL10n', array(
			'month1'  => __('January', 'affiliate-power'),
			'month2'  => __('February', 'affiliate-power'),
			'month3'  => __('March', 'affiliate-power'),
			'month4'  => __('April', 'affiliate-power'),
			'month5'  => __('May', 'affiliate-power'),
			'month6'  => __('June', 'affiliate-power'),
			'month7'  => __('July', 'affiliate-power'),
			'month8'  => __('August', 'affiliate-power'),
			'month9'  => __('September', 'affiliate-power'),
			'month10'  => __('October', 'affiliate-power'),
			'month11'  => __('November', 'affiliate-power'),
			'month12'  => __('December', 'affiliate-power'),
			'day1'  => __('Sun', 'affiliate-power'),
			'day2'  => __('Mon', 'affiliate-power'),
			'day3'  => __('Tue', 'affiliate-power'),
			'day4'  => __('Wed', 'affiliate-power'),
			'day5'  => __('Thu', 'affiliate-power'),
			'day6'  => __('Fri', 'affiliate-power'),
			'day7'  => __('Sat', 'affiliate-power')
		) );
		
		wp_register_style('jquery-ui-style-smoothness', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.min.css');
		
		if (isset($_GET['page']) && $_GET['page'] == 'affiliate-power-settings') {
	        wp_enqueue_style('jquery-ui-style-smoothness');
		    wp_enqueue_script('jquery-ui-accordion');
		}
		
		if (isset($_GET['page']) && $_GET['page'] == 'affiliate-power-statistics') {
		    wp_enqueue_style('jquery-ui-style-smoothness');
		    wp_enqueue_script('affiliate-power-flot', plugins_url('flot/jquery.flot.min.js', __FILE__), array('jquery'), AFFILIATE_POWER_VERSION, true);
		    wp_enqueue_script('affiliate-power-flot-time', plugins_url('flot/jquery.flot.time.min.js', __FILE__), array('jquery', 'affiliate-power-flot'), AFFILIATE_POWER_VERSION, true);	
		}
		
	}
	

	static function addPluginLinks($links, $file) {
		if (is_array($links)) {
		    $links[] = '<a href="'.admin_url('admin.php?page=affiliate-power-settings').'">'.__('Settings').'</a>';
		    $links[] = '<a href="https://wordpress.org/support/plugin/affiliate-power/reviews/#new-post" target="_blank">'.__('Review').'</a>';
		    if (!AFFILIATE_POWER_PREMIUM) $links[] = '<a href="https://www.affiliatepowerplugin.com/premium/" target="_blank">Premium</a>';
		}
		return $links;
	}

}