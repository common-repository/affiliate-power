<?php
if (!defined('ABSPATH')) die; //no direct access

Affiliate_Power_Settings::prepare();


class Affiliate_Power_Settings {

    static $network_data;


    static function prepare() {
        add_action('admin_init', array('Affiliate_Power_Settings', 'addSettings'));
        
        self::$network_data = array(
    
            'adcell' => array(
                'slug' => 'adcell',
                'link_pattern' => 'adcell',
                'label' => 'Adcell',
                'fields' => array(
                    array('slug' => 'username', 'label' => 'Username', 'info' => __('The Adcell Username is the number, you are using to login on the adcell page.', 'affiliate-power'), 'validation' => 'ctype_digit'),
                    array('slug' => 'password', 'label' => 'API Password', 'info' => __('The Adcell API password is a special access to the Adcell API. Please do <strong>not</strong> enter your normal Adcell password here. The API password can be defined in the publisher area, menu item "My Data".', 'affiliate-power')),
                    array('type' => 'checkbox', 'slug' => 'referer-filter', 'label' => 'Website Filter', 'info' => __('Only save sales, which came from this domain. This option makes only sense if you are using your Adcell account for several pages.', 'affiliate-power')),
                ),
            ),

            'awin' => array(
                'slug' => 'awin',
                'link_pattern' => 'awin',
                'label' => 'Awin',
                'fields' => array(
                    array('slug' => 'id', 'label' => 'Publisher ID', 'info' => __('You can find the Awin Publisher ID at the top of the publisher area, next to your company name.', 'affiliate-power'), 'validation' => 'ctype_digit', 'allowed_chars' => ','),
                    array('slug' => 'token', 'label' => 'API Token', 'info' => __('You can find the Awin API Token at the top of the publisher area, where it says Hello... -> API Credentials.', 'affiliate-power'), 'validation' => 'ctype_alnum', 'allowed_chars' => array(',', '-')),
                    array('slug' => 'referer', 'label' => 'Referer Domain', 'info' => __('If you are using your awin account for several pages, you can enter the domain of this page here (without https and www). The plugin will only import sales from this domain. If you are using your awin account only for this page anyway, just leave the field empty. In this case, the plugin will download all sales.', 'affiliate-power')),
                ),
            ),

        
            'belboon' => array(
                'slug' => 'belboon',
                'link_pattern' => '(belboon)|(https://[a-z]+\.r\.[a-z]+\.com/ts/[a-z0-9]+/tsc)',
                'label' => 'Belboon',
                'fields' => array(
                    array('slug' => 'userid', 'label' => 'Partner Id', 'info' => __('You can find the numeric belboon Partner Id at the top of the publisher area, next to your name.', 'affiliate-power'), 'validation' => 'ctype_digit'),
                    array('slug' => 'key', 'label' => 'Magic Key', 'info' => __('You can create the magic key in the publisher area, top menu at your account name, menu item Credentials. Give it a name of your choice and allow access to all APIs.', 'affiliate-power'), 'validation' => 'ctype_alnum', 'allowed_chars' => '-'),
                    array('slug' => 'platform', 'label' => 'Ad Space Name', 'info' => __('If you are using your belboon account for several pages, you can enter the ad space name for this page here. The plugin will only import sales from this ad space. Please do not enter the ad space id but the name. You can find the ad space name in the publisher area, top menu at your account name, menu item Ad space. If you are using several ad spaces for this page you can separate the ad space names with comma. If you are using your belboon account only for this site anyway, just leave the field empty. In this case, the plugin will download all sales.', 'affiliate-power')),
                ),
            ),
        
            'cj' => array(
                'slug' => 'cj',
                'link_pattern' => 'click-[0-9]+-[0-9]+',
                'label' => 'Commission Junction',
                'fields' => array(
                    array('slug' => 'id', 'label' => 'PID', 'info' => __('The PID identifies your website at Commission Junction. You can find it in the publisher area of Commission Junction at the menu item Account -> Website Settings.', 'affiliate-power'), 'validation' => 'ctype_digit'),
                    array('slug' => 'key', 'label' => 'Developer Key', 'info' => __('The Commission Junction Developer Key is a special access to the Commission Junction API. Please do not enter your normal password here. In order to get the key, you have to go to the <a href="https://api.cj.com/sign_up.cj" target="_blank">Webservice-Site of Commission Junction</a>, and login with your normal account data.', 'affiliate-power')),
                ),
            ),
        
            'digistore24' => array(
                'slug' => 'digistore24',
                'link_pattern' => '(digistore24)|(/#aff=)',
                'label' => 'Digistore24',
                'check_login_params' => 1,
                'fields' => array(
                    array('slug' => 'key', 'label' => 'API Key', 'info' => __('The Digistore24 API key is a special access to the Digistore24 API. Please do <strong>not</strong> enter your normal password here. Follow these steps to get the key:<ol><li>Login on the <a href="https://www.digistore24.com" target="_blank">Digistore24 Page</a></li><li>Make sure you are in the vendor view. You can change that in the top left corner</li><li>Click on Settings -> Account access -> Api keys</li><li>Create a new key with the name "Affiliate Power" and Read access</li><li>Copy the created API key into this field. It should look something like 1234-XYZ123xyz...</li></ol>', 'affiliate-power')),
                ),
            ),
        
            'financeads' => array(
                'slug' => 'fads',
                'link_pattern' => 'financeads',
                'label' => 'financeAds',
                'fields' => array(
                    array('slug' => 'id', 'label' => 'Partner ID', 'info' => __('The five digit Partner ID identifies your account at financeAds.', 'affiliate-power')),
                    array('slug' => 'key', 'label' => 'API Key', 'info' => __('The financeAds API Key is a special access to the financeAds API. Please do <strong>not</strong> enter your normal password here. In order to get the key, you have to go to the <a href="https://login.financeads.net" target="_blank">financeAds panel</a> and login with your normal account data. Then you have to go to Auswertung -> Auswertungs API and click the link: API Key anzeigen. If you can\'t find the API Key or the sales are not imported, you might have to contact financeAds first and ask them to activate your access to the API and to the all sales report.', 'affiliate-power')),
                    array('slug' => 'wfid', 'label' => 'Ad Space ID', 'info' => __('If you are using your financeAds account for several pages, enter the Ad Space ID you defined in the login area for this page. The plugin will only import sales from this Ad Space. If you are using your account only for this page anyway, just leave the field empty. In this case, the plugin will download all sales.', 'affiliate-power')),
                ),
            ),

            'tradedoubler' => array(
                'slug' => 'tradedoubler',
                'link_pattern' => 'tradedoubler',
                'label' => 'Tradedoubler',
                'check_login_params' => 3,
                'fields' => array(
                    array('slug' => 'key', 'label' => 'Report Key', 'info' => __('The Tradedoubler Report Key is a special access to the Tradedoubler API. Please do not enter your normal Tradedoubler password here. In order to get the Tradedoubler Report Key, you have to wirte an email with your username to support.uk@tradedoubler.com (you can also change the country code if you are not in the UK) and ask for a Report Key. You will get an email with the key.', 'affiliate-power'), 'validation' => 'ctype_alnum'),
                    array('slug' => 'sitename', 'label' => 'Site name', 'info' => __('If you are using your Tradedoubler account for several pages, enter the site name you defined in the Tradedoubler login area for this page. The plugin will only import sales from this site name. Please do not enter the site id but the name. You can find the site name in the publisher area, menu item Sites -> Sites. If you are using several sites for this page you can separate the site names with comma. If you are using your account only for this page anyway, just leave the field empty. In this case, the plugin will download all sales.', 'affiliate-power')), 
                    array('type' => 'checkbox', 'slug' => 'us-format', 'label' => 'US format', 'info' => __('Activate this checkbox, if your tradedoubler account is using US format. It might be using US format even if you are not in ths US. You can check this by looking at the dates in the Tradedoubler login area. If they list the month first (mm/dd/yy), your account is using US format.', 'affiliate-power')),
                ),
            ),
        );
    }
    

	static function addSettings() {
		register_setting( 'affiliate-power-options', 'affiliate-power-options', array('Affiliate_Power_Settings', 'optionsValidate') );
		
		add_settings_section('affiliate-power-main', __('Basic settings', 'affiliate-power'), array('Affiliate_Power_Settings', 'optionsMainText'), 'affiliate-power-options');
		add_settings_field('affiliate-power-add-sub-ids', __('Activate tracking', 'affiliate-power'), array('Affiliate_Power_Settings', 'addSubIdsField'), 'affiliate-power-options', 'affiliate-power-main');
		add_settings_field('affiliate-power-send-mail-transactions', __('Daily email report', 'affiliate-power'), array('Affiliate_Power_Settings', 'addSendMailTransactionsField'), 'affiliate-power-options', 'affiliate-power-main');
		add_settings_field('affiliate-power-licence-key', __('License key', 'affiliate-power'), array('Affiliate_Power_Settings', 'addLicenceKeyField'), 'affiliate-power-options', 'affiliate-power-main');
		add_settings_field('affiliate-power-landing-params', __('URL-Parameter', 'affiliate-power'), array('Affiliate_Power_Settings', 'addLandingParamsField'), 'affiliate-power-options', 'affiliate-power-main');
				
		add_settings_section('affiliate-power-networks', __('Affiliate-Networks', 'affiliate-power'), array('Affiliate_Power_Settings', 'optionsNetworksText'), 'affiliate-power-options');
		
		$options = get_option('affiliate-power-options');
		
		foreach (self::$network_data as $network) {
		    add_settings_section('affiliate-power-networks-'.$network['slug'], __($network['label'], 'affiliate-power'), array('Affiliate_Power_Settings', 'dummyFunction'), 'affiliate-power-options');
		    
		    foreach ($network['fields'] as $field) {
		        $network_field_slug = $network['slug'].'-'.$field['slug'];
		        
		        add_settings_field('affiliate-power-'.$network_field_slug, $network['label'].' '.$field['label'], function() use ($network_field_slug, $field, $options) {
		        
		            if (!isset($options[$network_field_slug])) $options[$network_field_slug] = '';
		        
		            if (isset($field['type']) && $field['type'] == 'checkbox') echo '<input type="checkbox" id="affiliate-power-'.$network_field_slug.'" name="affiliate-power-options['.$network_field_slug.']" value="1" '. checked($options[$network_field_slug], 1, false).' /> ';
		            else echo '<input type="text" id="affiliate-power-'.$network_field_slug.'" name="affiliate-power-options['.$network_field_slug.']" size="40" value="'.$options[$network_field_slug].'"  /> ';
		            
		            echo '<span style="font-size:1em;"><a href="#" onclick="document.getElementById(\'ap-'.$network_field_slug.'-info\').style.display=\'block\'; return false;">[?]</a></span>';
		            echo '<div id="ap-'.$network_field_slug.'-info" style="display:none;">'.$field['info'].'</div>';
		        
		        }, 'affiliate-power-options', 'affiliate-power-networks-'.$network['slug']);
		        
		    } //foreach ($network['fields'] as $field)
		} //foreach (self::$network_data as $network)
	} //function
	
	
	static function dummyFunction() {
	}
	
	
	static function optionsPage() {
		include_once 'options-head.php'; //we need this to show error messages
		?>
		<div class="wrap">
		<div class="icon32" style="background:url(<?php echo plugins_url('affiliate-power/img/affiliate-power-36.png'); ?>) no-repeat;"><br/></div>
		<h2><?php _e('Affiliate Power Settings', 'affiliate-power'); ?></h2>
		<?php
		$meta_options = get_option('affiliate-power-meta-options');
		
		//Infotext
		if (isset($meta_options['infotext']) && $meta_options['hide-infotext'] == 0) {
			echo '<div class="updated">'.$meta_options['infotext'].'</div>';
		}
		
		_e('<p>Please be patient when saving the settings. The plugin performs a test login at the networks while saving.</p>', 'affiliate-power');
		
		$lite_optin = sprintf(__('<img src="%s" alt="E-Book" style="float:left; width:100px;"><div style="margin-left:120px"><h3>Premium Lite</h3><p>Use Affiliate Power Premium with the permanently free lite version. All you need is your email. The lite version is limited to 30 sales per month. You can always upgrade to an unlimited license.</p><form method="post" target="_blank" action="https://www.affiliatepowerplugin.com/affiliate-power-premium-lite/"><input type="hidden" name="submit_ap_register" value="1"><input type="text" placeholder="email" value="" name="email"> <input class="button-primary" type="submit" value="Use Premium now"></form></div>', 'affiliate-power'), plugins_url('img/affiliate-power-premium.png', __FILE__));
		$lite_optin = apply_filters('affiliate_power_lite_optin_text', $lite_optin);
		echo $lite_optin;
		?>
        <div class="clear"></div>
		<form action="options.php" method="post">
		<?php settings_fields('affiliate-power-options'); ?>
		<?php
			//this is a customized copy of do_settings_sections()
			$page = 'affiliate-power-options';
			global $wp_settings_sections, $wp_settings_fields;

			foreach ( (array) $wp_settings_sections[$page] as $section ) {
				//print_r($section);
				if ( $section['title'] ) echo '<h3>'.$section['title'].'</h3>';
				echo '<div>'; // a div for the accordion content
				if ( $section['callback'] ) call_user_func( $section['callback'], $section );
				if ($section['id'] == 'affiliate-power-networks') echo '<div class="accordion">'; //open an accordion for the following networks
				if ( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) || !isset( $wp_settings_fields[$page][$section['id']] ) ) continue;
				echo '<table class="form-table">';
				do_settings_fields( $page, $section['id'] );
				echo '</table>';
				echo '</div>';
			}
		?>
		</div> <!--accordion-->
		<p><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
		</form>
		
		</div>
		<?php
	}
	
	
	static function optionsMainText() {
		echo '';
	}
	
	
	static function addSubIdsField() {
	    $output = "<input type='checkbox' disabled='disabled' /> <span style='font-size:1em;'><a href='#' onclick='document.getElementById(\"ap_sub_id_info\").style.display=\"block\"; return false;'>[?]</a></span> ".__("<div id='ap_sub_id_info' style='display:none;'>With the activated tracking, you can find out which articles/referer/keywords etc. led to which income. Sales, which occurred before the plugin installation can not be analyzed. This option makes sense for almost all plugin users.</div>", "affiliate-power")."<br>".__('Only in the <a href="https://www.affiliatepowerplugin.com/premium/" target="_blank">premium version</a>', 'affiliate-power');
	    $output = apply_filters('affiliate_power_add_sub_ids_field', $output);
	    echo $output;
	}
	
	
	static function addSendMailTransactionsField() {
		$options = get_option('affiliate-power-options');
		if (!isset($options['send-mail-transactions'])) $options['send-mail-transactions'] = 0;
		$checked = $options['send-mail-transactions'] ? ' checked' : '';
		echo "<input type='checkbox' id='affiliate-power-send-mail-transactions' name='affiliate-power-options[send-mail-transactions]' value='1' ".$checked." /> ";
		echo "<span style='font-size:1em;'><a href='#' onclick='document.getElementById(\"ap_send_mail_transactions_info\").style.display=\"block\"; return false;'>[?]</a></span>";
		_e("<div id='ap_send_mail_transactions_info' style='display:none;'>Receive a daily email with all new or changed sales. If there aren't any sales, no email will be send.</div>", "affiliate-power");
	}
	
	
	static function addLicenceKeyField() {
		$options = get_option('affiliate-power-options');
		if (!isset($options['licence-key'])) $options['licence-key'] = '';
		echo "<input type='text' id='affiliate-power-licence-key' name='affiliate-power-options[licence-key]' size='40' value='".$options['licence-key']."' /> ";
		echo "<span style='font-size:1em;'><a href='#' onclick='document.getElementById(\"ap_licence_key_info\").style.display=\"block\"; return false;'>[?]</a></span>";
		_e("<div id='ap_licence_key_info' style='display:none;'>Enter your premium license key here to use the full power of Affiliate Power. If there are any problems, <a href='http://www.affiliatepowerplugin.com/contact/' target='_blank'>let me know</a>.<br><a href='https://www.affiliatepowerplugin.com/premium/' target='_blank'>More information about the premium version</a></div>", "affiliate-power");
	}
	
	
	static function addLandingParamsField() {
	    $output = "<input type='text' size='80' value='".__('Only in the premium version', 'affiliate-power')."' readonly='readonly' style='color:#888; cursor:pointer;' onclick='window.open(\"".__('https://www.affiliatepowerplugin.com/premium/', 'affiliate-power')."\", \"_blank\")' /> <span style='font-size:1em;'><a href='#' onclick='document.getElementById(\"ap_landing_params_info\").style.display=\"block\"; return false;'>[?]</a></span>".__("<div id='ap_landing_params_info' style='display:none;'>Here you can define URL parameters you want to track. You can separate several parameters with comma. If you are using Google Analytics campaign tracking, this values may be a good start: <em>utm_campaign,utm_source,utm_medium,utm_term,utm_content</em>.</div>", "affiliate-power");
	    $output = apply_filters('affiliate_power_add_landing_params_field', $output);
	    echo $output;
	}
	
	
	//Network Settings
	static function optionsNetworksText() {
		_e('<p>In order to download your sales, you have to enter your data of the affiliate networks you are using. <a href=" https://www.affiliatepowerplugin.com/#data-security" target="_blank">Whats about data security?</a></p>', 'affiliate-power');
	}
	
	
	//Validation
	static function optionsValidate($input) {
	
		//Main Settings
		if (isset($input['send-mail-transactions']) && $input['send-mail-transactions'] == 1) $whitelist['send-mail-transactions'] = 1;
		else $whitelist['send-mail-transactions'] = 0;

		if (isset($input['licence-key']) && ctype_alnum($input['licence-key'])) {
			$check_result = Affiliate_Power::checkLicenceKey($input['licence-key']);
			if ($check_result == false || $check_result == 'database_error' || $check_result == 'database_charset_error') add_settings_error('affiliate-power-options', 'affiliate-power-error-licence-key', __('The licence key could not be checked. Please try again later and <a href="https://www.affiliatepowerplugin.com/contact/" target="_blank">let me know</a> if it is still not working.', 'affiliate-power') );
			elseif ($check_result == 'outdated_key') add_settings_error('affiliate-power-options', 'affiliate-power-error-licence-key', __('The licence key is outdated. Please renew your licence key.', 'affiliate-power') );
			elseif ($check_result == 'invalid_key_format' || $check_result == 'invalid_key') add_settings_error('affiliate-power-options', 'affiliate-power-error-licence-key', __('The licence key is invalid. Please check again. If you are sure you entered the right key, <a href="https://www.affiliatepowerplugin.com/contact/" target="_blank">let me know</a>, and I will check it out.', 'affiliate-power') );
			elseif ($check_result == 'ok') $whitelist['licence-key'] = $input['licence-key'];
		}
		elseif (!empty($input['licence-key'])) add_settings_error('affiliate-power-options', 'affiliate-power-error-licence-key', __('Invalid license key. The key should only contain numbers and letters.', 'affiliate-power'));
		
		foreach (self::$network_data as $network_name => $network) {
		    foreach ($network['fields'] as $field) {
		        $network_field_slug = $network['slug'].'-'.$field['slug'];
		    
		        if (isset($field['validation'])) {
		            
		            if (isset($field['allowed_chars'])) $check_value = str_replace($field['allowed_chars'], '', $input[$network_field_slug]);
		            else $check_value = $input[$network_field_slug];
		            
		            if ($field['validation']($check_value)) $whitelist[$network_field_slug] = esc_html($input[$network_field_slug]);
		            elseif (!empty($input[$network_field_slug])) add_settings_error('affiliate-power-options', 'affiliate-power-error-'.$network_field_slug, __('Invalid', 'affiliate-power').': '.$network['label'].' '.$field['label'], 'error');
		        }
		        elseif (isset($field['type']) && $field['type'] == 'checkbox') {
		            if (isset($input[$network_field_slug]) && $input[$network_field_slug] == 1) $whitelist[$network_field_slug] = 1;
		            else $whitelist[$network_field_slug] = 0;
		        }
		        elseif (!empty($input[$network_field_slug])) {
		            $whitelist[$network_field_slug] = esc_html($input[$network_field_slug]);
		        }
		    }
		    
		    if (!isset($network['check_login_params'])) $network['check_login_params'] = 2;
		    
		    if ($network['check_login_params'] > 0) {
		        
                $testlogin_result = null;
                $checkparams_keys = array();
                
                for ($i=0; $i<$network['check_login_params']; $i++) $checkparams_keys[] = $network['slug'].'-'.$network['fields'][$i]['slug'];
		    
		        if ($network['check_login_params'] == 1 && isset($whitelist[$checkparams_keys[0]])) $testlogin_result = Affiliate_Power_Apis::checkNetworkLogin($network_name, $whitelist[$checkparams_keys[0]]);
		        elseif ($network['check_login_params'] == 2 && isset($whitelist[$checkparams_keys[0]]) && isset($whitelist[$checkparams_keys[1]])) $testlogin_result = Affiliate_Power_Apis::checkNetworkLogin($network_name, $whitelist[$checkparams_keys[0]], $whitelist[$checkparams_keys[1]]);
		        elseif ($network['check_login_params'] == 3 && isset($whitelist[$checkparams_keys[0]]) && isset($whitelist[$checkparams_keys[1]]) && isset($whitelist[$checkparams_keys[2]])) $testlogin_result = Affiliate_Power_Apis::checkNetworkLogin($network_name, $whitelist[$checkparams_keys[0]], $whitelist[$checkparams_keys[1]], $whitelist[$checkparams_keys[2]]);

                if ($testlogin_result === false) add_settings_error('affiliate-power-options', 'affiliate-power-error-'.$network['slug'].'-login', $network['label'].' '.__('test login failed. Please check your data.', 'affiliate-power'), 'error');
                
            }
		}
		
		$whitelist = apply_filters('affiliate_power_options_validate', $whitelist, $input);
		
		return $whitelist;
	}

}
