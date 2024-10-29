<?php
if (!defined('ABSPATH')) die; //no direct access


class Affiliate_Power_Statistics {


	static function statisticsPage() {
		global $wpdb;
		$options = get_option('affiliate-power-options');
		

		//Datepicker
		$dates_predefined = array (
			'custom' => __('Custom', 'affiliate-power'),
			'today' => __('Today', 'affiliate-power'),
			'yesterday' => __('Yesterday', 'affiliate-power'),
			'last_7_days' => __('Last 7 days', 'affiliate-power'),
			'last_30_days' => __('Last 30 days', 'affiliate-power'),
			'all' => __('All', 'affiliate-power')
		);
		$dates_predefined_options = '';
		foreach ($dates_predefined as $value => $text) {
			$dates_predefined_options .= '<option value="'.$value.'"';
			if (
				!isset($_POST['datepicker_predefined']) && $value == 'last_30_days' || 
				isset($_POST['datepicker_predefined']) && $value == $_POST['datepicker_predefined']) {
					$dates_predefined_options .= ' selected="selected"';
			}
			$dates_predefined_options .= '>'.$text.'</option>';
		}
		$first_transaction = $wpdb->get_var('SELECT unix_timestamp(Date) FROM '.$wpdb->prefix.'ap_transaction ORDER BY Date ASC LIMIT 1');		
		
		
		//convert dates for db
		$date_from = isset($_POST['date_from']) && preg_match("/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/", $_POST['date_from']) ? $_POST['date_from'] : date('d.m.Y', time()-86400*30);
		$date_to = isset($_POST['date_to']) && preg_match("/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/", $_POST['date_to']) ? $_POST['date_to'] : date('d.m.Y', time()-86400);
		$arr_date_from = explode('.', $date_from);
		$arr_date_to = explode('.', $date_to);
		$date_from_db = $arr_date_from[2].'-'.$arr_date_from[1].'-'.$arr_date_from[0];
		$date_to_db = $arr_date_to[2].'-'.$arr_date_to[1].'-'.$arr_date_to[0] . ' 23:59:59';
		
			
		//get data
		$statistics_data = null;
		if (!isset($_GET['view'])) $_GET['view'] = 'overview';
		if ($_GET['view'] == 'partner') $statistics_data = self::partner($date_from_db, $date_to_db);
		elseif ($_GET['view'] == 'network') $statistics_data = self::networks($date_from_db, $date_to_db);
		$statistics_data = apply_filters('affiliate_power_statistics_data', $statistics_data, $date_from_db, $date_to_db);
		if ($statistics_data == null) $statistics_data = self::overview($date_from_db, $date_to_db);
		list($arrPlotData, $statisticHtml) = $statistics_data;
		
		
		//prepare plot
		$datasets = '';
		foreach($arrPlotData as $plotLine) {
			switch ($plotLine[0]) {
				case 'total_income' : $label = __('Total Income', 'affiliate-power'); break;
				default: $label = $plotLine[0];
			}
			$datasets .= '"'.$plotLine[0].'": { label: "'.$label.'", data: ';
			array_shift($plotLine); //remove label
			array_shift($plotLine); //remove sum
			$datasets .= json_encode($plotLine);
			$datasets .= '},';
		}

		
		//start output
		echo '<div class="wrap">';
		echo '<div class="icon32" style="background:url('.plugins_url('affiliate-power/img/affiliate-power-36.png').') no-repeat;"><br/></div>';
		_e ('<h2>Affiliate Power Statistics</h2>', 'affiliate-power');
		
		
		//Infotext
		$meta_options = get_option('affiliate-power-meta-options');
		if (isset($meta_options['infotext']) && $meta_options['hide-infotext'] == 0) {
			echo '<div class="updated">'.$meta_options['infotext'].'</div>';
		}
		
		
		echo '
			<script type="text/javascript">
			var first_transaction_ts = "'.$first_transaction.'";
			var datasets = {'.$datasets.'};
			var data = datasets;
		
			jQuery(document).ready(function($) {
			
				var i = 0;
				$.each(datasets, function(key, val) {
					val.color = i;
					++i;
				});
			
				plotAccordingToChoices();
				$("#detailStatisticTable").find("input").click(plotAccordingToChoices);
				
				function plotAccordingToChoices() {

					var data = [];
					if(datasets["total_income"]) data.push(datasets["total_income"]);
					
					$("#detailStatisticTable").find("input:checked").each(function () {
						var key = $(this).attr("name");
						if (key && datasets[key]) {
							data.push(datasets[key]);
						}
					});
			
					$.plot($("#plot"), data, 
						{ xaxis: { mode: "time",  timeformat: "%d.%m", minTickSize: [1, "day"] },
						  yaxis: {ticks: 4, min: 0, tickFormatter: function (v) { return Math.round(v)+" €"; } },
						  series: { lines: { show: true }, points: { show: true } },
						  grid: { hoverable: true },
						  legend: { container: $("#plot_legend"), noColumns: 8 }
						}
					);
				}
				
				$("#plot").bind("plothover", function (event, pos, item) {
					$("#tooltip").remove();
					if (item == null) return;
					var obj_date = new Date(item.datapoint[0]);
					var weekdays = new Array("'.__('Sunday', 'affiliate-power').'", "'.__('Monday', 'affiliate-power').'", "'.__('Tuesday', 'affiliate-power').'", "'.__('Wednesday', 'affiliate-power').'", "'.__('Thursday', 'affiliate-power').'", "'.__('Friday', 'affiliate-power').'", "'.__('Saturday', 'affiliate-power').'");
					var weekday = weekdays[obj_date.getDay()];
					var day = obj_date.getDate();
					if (day < 10) day = "0" + day + "";
					var month = obj_date.getMonth() + 1;
					if (month < 10) month = "0" + month + "";
					var year = obj_date.getFullYear();
					var str_date = weekday + ", " + day + "." + month + "." + year;
					var earnings = item.datapoint[1].toFixed(2);
					earnings = (earnings + "").replace(/\./, ",");
					earnings += " €";
					var content = item.series.label + "<br />" + str_date + "<br />" + earnings;
					showTooltip(item.pageX, item.pageY, content);
				});
				
				function showTooltip(x, y, contents) {
					$("<div id=\'tooltip\'>" + contents + "</div>").css({
						position: "absolute",
						display: "none",
						top: y + 10,
						left: x + 10,
						border: "1px solid #fdd",
						padding: "2px",
						"font-weight": "bold",
						"background-color": "#fee",
						opacity: 0.80
					}).appendTo("body").fadeIn(200);
				}
				
			});
			</script>
			
			<form method="post" action="" name="formDate"><p>
				'.__('From', 'affiliate-power').': <input type="text" name="date_from" id="datepicker_from" value="'.esc_attr($date_from).'" /> '.__('Till', 'affiliate-power').':  <input type="text" name="date_to" id="datepicker_to" value="'.esc_attr($date_to).'" /> <input type="submit" class="button" value="OK" /></p><p>
				 '.__('Period', 'affiliate-power').': <select id="datepicker_predefined" name="datepicker_predefined">
					'.$dates_predefined_options.'
				</select>
			</p></form>
			<div id="plot" style="width:90%; height:200px;"></div><div id="plot_legend"></div>';
		echo $statisticHtml;
		echo '</div>';
	
	}
	
	
	
	static function overview($date_from_db, $date_to_db) {
	
		global $wpdb;
	
		//Total Plot
		$sql = $wpdb->prepare('
		SELECT "total_income" as name,
		unix_timestamp(date(Date))*1000+3600000*12 as day_stamp,
		round(sum(Commission),2) as commission
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled"
		AND date BETWEEN %s and %s
		GROUP BY date(Date)
		ORDER BY date ASC',
		$date_from_db, $date_to_db);
		
		$plotData = $wpdb->get_results($sql, ARRAY_A);
		$arrPlotData = self::structurePlotdata($plotData);
		
		
		//Top Partner
		$sql = $wpdb->prepare('
		SELECT concat (ProgramTitle, " (", network, ")") as name,
			   round(sum(Commission),2) as commission,
			   round(sum(Confirmed), 2) as confirmed
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY ProgramId, network
		ORDER BY sum(Commission) DESC
		LIMIT 12', $date_from_db, $date_to_db);
		$topPartnerData = $wpdb->get_results($sql, ARRAY_A);
		
		
		//Networks
		$sql = $wpdb->prepare('
		SELECT network as name,
			   round(sum(Commission),2) as commission,
			   round(sum(Confirmed), 2) as confirmed
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY network
		ORDER BY sum(Commission) DESC
		LIMIT 12', $date_from_db, $date_to_db);
		$networkData = $wpdb->get_results($sql, ARRAY_A);
		
		
		//Days
		$sql = $wpdb->prepare('
		SELECT date_format(date, "%%d.%%m.%%Y") as name,
			   round(sum(Commission),2) as commission,
			   round(sum(Confirmed), 2) as confirmed
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY date(date)
		ORDER BY date DESC',
		$date_from_db, $date_to_db);
		$dayData = $wpdb->get_results($sql, ARRAY_A);
		
		
		//Weeks
		$sql = $wpdb->prepare('
		SELECT concat ("KW ", weekofyear(date), ", ", year(date)) as name,
			   round(sum(Commission),2) as commission,
			   round(sum(Confirmed), 2) as confirmed
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY weekofyear(date)
		ORDER BY date DESC',
		$date_from_db, $date_to_db);
		$weekData = $wpdb->get_results($sql, ARRAY_A);
		
		
		//Months
		$sql = $wpdb->prepare('
		SELECT date_format(date, "%%m.%%Y") as name,
			   round(sum(Commission),2) as commission,
			   round(sum(Confirmed), 2) as confirmed
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY month(date), year(date)
		ORDER BY year(date) DESC,
				 month(date) DESC',
		$date_from_db, $date_to_db);;
		$monthData = $wpdb->get_results($sql, ARRAY_A);
		

		//statistics to create
		$arr_statistics = array(
		    'partner' => $topPartnerData,
			'network' => $networkData
		);
		
		$arr_statistics = apply_filters('affiliate_power_quick_statistics_data', $arr_statistics, $date_from_db, $date_to_db);
		
		//time based statistics always at the end
		$arr_statistics = array_merge($arr_statistics,
		    array(
				'day' => $dayData,
				'week' => $weekData,
				'month' => $monthData
			)
		);
		
		$statisticHtml = '';
		$i = 1;
		foreach ($arr_statistics as $type => $statistic) {
			if ($type == 'day') { $statisticHtml .= '<div style="clear:both;">&nbsp;</div>'; $i=1; }
			$statisticHtml .= self::getQuickStatisticHtml($type, $statistic);
			if ($i % 3 == 0) $statisticHtml .= '<div style="clear:both;">&nbsp;</div>';
			$i += 1;
		}
		
		return array($arrPlotData, $statisticHtml);
	}
	
	
	static function getQuickStatisticHtml($type, $statistic) {
		switch ($type) {
			case 'partner' : $headline = __('Partner', 'affiliate-power'); break;
			case 'network' : $headline = __('Network', 'affiliate-power'); break;
			case 'day'     : $headline = __('Day', 'affiliate-power'); break;
			case 'week'    : $headline = __('Week', 'affiliate-power'); break;
			case 'month'   : $headline = __('Month', 'affiliate-power'); break;
			default: $headline = $type;
		}
		
		if ($type == 'day' || $type == 'week' || $type == 'month') $headlineHtml = '<h3>'.$headline.'</h3>';
		else $headlineHtml = '<h3><a href="'.admin_url('admin.php?page=affiliate-power-statistics&view='.strtolower($type)).'">'.$headline.'</a></h3>';
		
		list($headline, $headlineHtml) = apply_filters('affiliate_power_quick_statistics_headline', array($headline, $headlineHtml), $type);
		
		$html = ' 
			<div style="width:30%; float:left; margin-right:20px;">
				'.$headlineHtml.'
				<table class="widefat" style="border-color:#666">
					<thead>
						<tr>
							<th>'.$headline.'</th>
							<th>'.__('Income', 'affiliate-power').'</th>      
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th>'.$headline.'</th>
							<th>'.__('Income', 'affiliate-power').'</th>
						</tr>
					</tfoot>
					<tbody>';
		
		if (is_array($statistic)) {
			foreach ($statistic as $row) {
				$row['name'] = apply_filters('affiliate_power_quick_statistics_row_name', $row['name']);
				$total_earning = number_format($row['commission'], 2, ',', '.');
				$confirmed_earning = number_format($row['confirmed'], 2, ',', '.');
				$output_earnings = $total_earning . ' € (<span style="color:green;">'.$confirmed_earning.' €</span>)';
				$html .= '<tr><td>'.$row['name'].'</td><td>'.$output_earnings.'</td></tr>';
			}
		}
			
		$html .= '
					</tbody>
				</table>
			</div>';
			
		return $html;
	}
	
	
	static function partner($date_from_db, $date_to_db) {
		global $wpdb;
		
		//Plot
		$sql = $wpdb->prepare('
		SELECT concat (ProgramTitle, " (", network, ")") as name,
			   unix_timestamp(date(Date))*1000+3600000*12 as day_stamp, 
			   round(sum(Commission),2) as commission
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY date(Date), ProgramId, network
		ORDER BY name DESC, day_stamp ASC',
		$date_from_db, $date_to_db);
		$plotData = $wpdb->get_results($sql, ARRAY_A);
		$structuredPlotData = self::structurePlotData($plotData);

		
		//Table
		$sql = $wpdb->prepare('
		SELECT concat (ProgramTitle, " (", network, ")") as name,
			   count(*) as cnt,
			   round(sum(Commission),2) as commission,
			   round(sum(Confirmed), 2) as confirmed
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY ProgramId, network
		ORDER BY sum(Commission) DESC',
		$date_from_db, $date_to_db);
		$tableData = $wpdb->get_results($sql, ARRAY_A);
		$statisticHtml = self::getDetailStatisticHtml('partner', $tableData);
	
	
		return array($structuredPlotData, $statisticHtml);
	}
	
	
	static function networks ($date_from_db, $date_to_db) {
		global $wpdb;

		//plot
		$sql = $wpdb->prepare('
		SELECT network as name,
			   unix_timestamp(date(Date))*1000+3600000*12 as day_stamp,
			   round(sum(Commission),2) as commission
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY date(Date), network
		ORDER BY name DESC, day_stamp ASC', $date_from_db, $date_to_db);
		$plotData = $wpdb->get_results($sql, ARRAY_A);
		$structuredPlotData = self::structurePlotData($plotData);
		
		
		//table
		$sql = $wpdb->prepare('
		SELECT network as name,
			   count(*) as cnt,
			   round(sum(Commission),2) as commission,
			   round(sum(Confirmed), 2) as confirmed
		FROM '.$wpdb->prefix.'ap_transaction 
		WHERE TransactionStatus <> "Cancelled" 
		AND date BETWEEN %s and %s
		GROUP BY network
		ORDER BY sum(Commission) DESC', $date_from_db, $date_to_db);
		$tableData = $wpdb->get_results($sql, ARRAY_A);
		$statisticHtml = self::getDetailStatisticHtml('network', $tableData);
		
		return array($structuredPlotData, $statisticHtml);
	}
	
	
	static function structurePlotData($plotData) {
		
		$lastName = '';
		$lastTimestamp = 0;
		$structuredPlotData = array();
		foreach ($plotData as $plotDate) {
		
			if ($lastName != $plotDate['name']) {
				$structuredPlotData[][0] = $plotDate['name'];
				$highest_index = count($structuredPlotData)-1;
				$structuredPlotData[$highest_index][1] = 0;
			}
			else {
				$highest_index = count($structuredPlotData)-1;
				//fill zeros for days without earnings
				while ($plotDate['day_stamp'] - $lastTimestamp > 86400*1000) {
					$lastTimestamp += 86400*1000;
					$structuredPlotData[$highest_index][] = array($lastTimestamp, 0);
				}
			}
			
			$structuredPlotData[$highest_index][1] += $plotDate['commission'];
			$structuredPlotData[$highest_index][] = array($plotDate['day_stamp'], $plotDate['commission']);
			$lastName = $plotDate['name'];
			$lastTimestamp = $plotDate['day_stamp'];
			
		}
		
		$sortArray = array();
		foreach($structuredPlotData as $key => $array) $sortArray[$key] = $array[1];
		array_multisort($sortArray, SORT_DESC, SORT_NUMERIC, $structuredPlotData); 
		
		return $structuredPlotData;
	}
	
	
	
	static function getDetailStatisticHtml($type, $statistic) {
	
		switch ($type) {
			case 'posts'   : $headline = __('Post', 'affiliate-power'); break;
			case 'partner' : $headline = __('Partner', 'affiliate-power'); break;
			case 'network' : $headline = __('Network', 'affiliate-power'); break;
			case 'landing' : $headline = __('Landing Page', 'affiliate-power'); break;
			case 'referer' : $headline = __('Referer', 'affiliate-power'); break;
			case 'device'  : $headline = __('Device', 'affiliate-power'); break;
			case 'keyword' : $headline = __('Keyword', 'affiliate-power'); break;
			case 'day'     : $headline = __('Day', 'affiliate-power'); break;
			case 'week'    : $headline = __('Week', 'affiliate-power'); break;
			case 'month'   : $headline = __('Month', 'affiliate-power'); break;
			default: $headline = $type;
		}
		
		$html = ' 
			<div style="width:90%; float:left;">
				<br><a href="'.admin_url('admin.php?page=affiliate-power-statistics').'">« '.__('Back to overview', 'affiliate-power').'</a>
				<h3>'.$headline.'</h3>
				<table class="widefat" id="detailStatisticTable" style="border-color:#666">
					<thead>
						<tr>
							<th>'.__('Graph', 'affiliate-power').'</th>
							<th>'.$headline.'</th>
							<th>'.__('Sales', 'affiliate-power').'</th>
							<th>'.__('Total Income', 'affiliate-power').'</th>
							<th>'.__('Confirmed Income', 'affiliate-power').'</th>      							
							<th>'.__('Income per Sale', 'affiliate-power').'</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th>'.__('Graph', 'affiliate-power').'</th>
							<th>'.$headline.'</th>
							<th>'.__('Sales', 'affiliate-power').'</th>
							<th>'.__('Total Income', 'affiliate-power').'</th>
							<th>'.__('Confirmed Income', 'affiliate-power').'</th>      							
							<th>'.__('Income per Sale', 'affiliate-power').'</th>
						</tr>
					</tfoot>
					<tbody>';
		   
		$i=1;
		foreach ($statistic as $row) {
		    $total_earning = number_format($row['commission'], 2, ',', '.') . ' €';
			$confirmed_earning = number_format($row['confirmed'], 2, ',', '.') . ' €';
			$earning_per_sale = number_format( ($row['commission'] / $row['cnt']), 2, ',', '.') . ' €';
			if ($i <= 3) $checked = ' checked="checked"'; else $checked = '';
			$i += 1;
		    $html .= '<tr><td><input type="checkbox" name="'.$row['name'].'"'.$checked.' /></td><td>'.$row['name'].'</td><td>'.$row['cnt'].'</td><td>'.$total_earning.'</td><td>'.$confirmed_earning.'</td><td>'.$earning_per_sale.'</td></tr>';
		}
			
		$html .= '
					</tbody>
				</table>
			</div>';
			
		return $html;
	}
	
	
}
