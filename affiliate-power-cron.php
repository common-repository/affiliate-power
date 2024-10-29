<?php
if (!defined('ABSPATH')) die; //no direct access

Affiliate_Power_Cron::prepare();


class Affiliate_Power_Cron {

    static function prepare() {
        add_action('affiliate_power_daily_event', array('Affiliate_Power_Apis', 'downloadTransactions'));
        add_action('affiliate_power_daily_event', array('Affiliate_Power_Cron', 'sendAdminMail'), 20);
        add_action('affiliate_power_daily_event', array('Affiliate_Power_Cron', 'checkInfotext'), 40);
    }

    
    static function sendAdminMail() {
        global $wpdb;
        $options = get_option('affiliate-power-options');
        if ($options['send-mail-transactions'] != 1) return;

        $transaction_changes['new'] = $wpdb->get_results('SELECT Date, ProgramTitle, Commission FROM '.$wpdb->prefix.'ap_transaction WHERE date(Date) = date(now() - INTERVAL 1 DAY) AND TransactionStatus <> "Cancelled"', ARRAY_A);
        $transaction_changes['confirmed'] = $wpdb->get_results('SELECT Date, ProgramTitle, Commission FROM '.$wpdb->prefix.'ap_transaction WHERE date(CheckDate) = date(now() - INTERVAL 1 DAY) AND TransactionStatus = "Confirmed"', ARRAY_A);
        $transaction_changes['cancelled'] = $wpdb->get_results('SELECT Date, ProgramTitle, Commission FROM '.$wpdb->prefix.'ap_transaction WHERE date(CheckDate) = date(now() - INTERVAL 1 DAY) AND TransactionStatus = "Cancelled"', ARRAY_A);
        
        $new_transactions_total = $wpdb->get_row('SELECT sum(Commission) as commission, count(*) as cnt FROM '.$wpdb->prefix.'ap_transaction WHERE date(Date) = date(now() - INTERVAL 1 DAY) AND TransactionStatus <> "Cancelled"', ARRAY_A);
        $confirmed_transactions_total = $wpdb->get_row('SELECT sum(Commission) as commission, count(*) as cnt FROM '.$wpdb->prefix.'ap_transaction WHERE date(CheckDate) = date(now() - INTERVAL 1 DAY) AND TransactionStatus = "Confirmed"', ARRAY_A);
        $cancelled_transactions_total = $wpdb->get_row('SELECT sum(Commission) as commission, count(*) as cnt FROM '.$wpdb->prefix.'ap_transaction WHERE date(CheckDate) = date(now() - INTERVAL 1 DAY) AND TransactionStatus = "Cancelled"', ARRAY_A);
        
        $transactions_programm['new'] = $wpdb->get_results('SELECT ProgramTitle, sum(Commission) as Commission FROM '.$wpdb->prefix.'ap_transaction WHERE date(Date) = date(now() - INTERVAL 1 DAY) AND TransactionStatus <> "Cancelled" GROUP BY ProgramTitle ORDER BY Commission DESC', ARRAY_A);
        $transactions_programm['confirmed'] = $wpdb->get_results('SELECT ProgramTitle, sum(Commission) as Commission FROM '.$wpdb->prefix.'ap_transaction WHERE date(CheckDate) = date(now() - INTERVAL 1 DAY) AND TransactionStatus = "Confirmed" GROUP BY ProgramTitle ORDER BY Commission DESC', ARRAY_A);
        $transactions_programm['cancelled'] = $wpdb->get_results('SELECT ProgramTitle, sum(Commission) as Commission FROM '.$wpdb->prefix.'ap_transaction WHERE date(CheckDate) = date(now() - INTERVAL 1 DAY) AND TransactionStatus = "Cancelled" GROUP BY ProgramTitle ORDER BY Commission DESC', ARRAY_A);

        $programm_transactions = '';
        $list_transactions = '';
        $type_mapper = array(
            'new' => _x('New', 'multiple', 'affiliate-power'),
            'confirmed' => _x('Confirmed', 'multiple', 'affiliate-power'),
            'cancelled' => _x('Cancelled', 'multiple', 'affiliate-power')
        );
        
        foreach ($transactions_programm as $type => $transactions) {

            $list_items = '';

            foreach ($transactions as $transaction) {
                $list_items .= '<li>'.$transaction['ProgramTitle'].': '.number_format($transaction['Commission'], 2, ',', '.').' &euro;</li>';
            }

            if ($list_items != '') {
                if ($type == 'new') $total = $new_transactions_total;
                elseif ($type == 'confirmed') $total = $confirmed_transactions_total;
                else $total = $cancelled_transactions_total;
                $programm_transactions .= '
                <p><strong>'.$type_mapper[$type].' '.__('transactions per merchant', 'affiliate-power').'</strong></p>
                <ul>'.$list_items.'</ul>'.sprintf( __('Total: %d Transactions, %s', 'affiliate-power'), $total['cnt'], number_format($total['commission'], 2, ',', '.').' &euro;' );
            }
        }
        
        foreach ($transaction_changes as $type => $transactions) {
        
            $list_items = '';
            
            foreach ($transactions as $transaction) {
                $datetime_de = date('d.m.Y H:i:s', strtotime($transaction['Date']));
                $list_items .= '<li>'.$datetime_de.': '.$transaction['ProgramTitle'].': '.number_format($transaction['Commission'], 2, ',', '.').' &euro;</li>';
            }
            
            if ($list_items != '') {
                if ($type == 'new') $total = $new_transactions_total;
                elseif ($type == 'confirmed') $total = $confirmed_transactions_total;
                else $total = $cancelled_transactions_total;
                $list_transactions .= '
                <p><strong>'.$type_mapper[$type].' '.__('transactions', 'affiliate-power').'</strong></p>
                <ul>'.$list_items.'</ul>'
                .sprintf( __('Total: %d Transactions, %s', 'affiliate-power'), $total['cnt'], number_format($total['commission'], 2, ',', '.').' &euro;' );
            }
        }
            
        //only send if there is any transaction
        if ($list_transactions != '') {
        
            $admin_email = get_option('admin_email');
            $blogname = get_option('blogname');
            
            $mailtext = sprintf (__('<p>Hello,</p><p>This is your daily income report from Affiliate Power for your page <strong>%s</strong>. You can always deactivate this report in the Affiliate Power settings, if you don\'t find it to be valuable.</p>', 'affiliate-power'), $blogname);
            
            $mailtext .= $programm_transactions;	
            $mailtext .= $list_transactions;
            
            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: <' . $admin_email . '>');
            wp_mail($admin_email, sprintf(__('Affiliate Power Report for %s', 'affiliate-power'), $blogname), $mailtext, $headers);
        }
    }
    
    
    static function checkInfotext() {
        global $wpdb;
        $meta_options = get_option('affiliate-power-meta-options');
        
        if ($meta_options['hide-infotext'] == 0) return; //already visible
        if ($meta_options['show-infotext-timestamp'] == -1) return; //user wants to permanently hide it
        if ($meta_options['show-infotext-timestamp'] > time()) return; //to early

        $sql = $wpdb->prepare('select sum(Commission) from '.$wpdb->prefix.'ap_transaction where unix_timestamp(Date) > %d ', $meta_options['installstamp']);
        $commission_since_install = $wpdb->get_var($sql);

        if ($commission_since_install >= 1000) {
            $meta_options['hide-infotext'] = 0;
            update_option('affiliate-power-meta-options', $meta_options);
        }
    }
    
}