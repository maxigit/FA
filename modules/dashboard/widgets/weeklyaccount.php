<?php
/**********************************************************************
// Creator: Alastair Robertson
// date_:   2013-01-30
// Title:   Daily bank balances widget for dashboard
// Free software under GNU GPL
***********************************************************************/

global $path_to_root;
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/dashboard/includes/dashboard_ui.inc");

class weeklyaccount
{
    var $page_security = 'SA_GLANALYTIC';

    var $weeks_past;
    var $weeks_future;
    var $gl_accounts;
    var $convert;
    var $graph_type;
    var $display_total;
function week_of_month($date) {
	if ($this->by_month) return 1;
	$next_date = add_months($date, 1);
	$days = -date_diff2($date, $next_date, 'd');
	return $days/7.0;
	}

    function weeklyaccount($params='')
    {
	if (isset($params))
	{
	    $data=json_decode(html_entity_decode($params));
	    if ($data != null) {
		$this->gl_accounts = $data->gl_accounts;
		$this->convert = $data->convert;
		$this->graph_type = $data->graph_type;
		if ($data->weeks_past != '')
		    $this->weeks_past = $data->weeks_past;
		if ($data->weeks_future != '')
		    $this->weeks_future = $data->weeks_future;
		$this->by_month = $data->by_month;
		$this->display_total = $data->display_total;
	    }
	}
    }




	function get_account_transaction($from, $to, &$rows, $type, $offset, $table) {
		if(!isset($table)) $table = 'gl_trans';

		if(!isset($offset)) $offset = 0;
		if($this->by_month) {
			$from_sql = date2sql(add_months($from,$offset));
			$to_sql = date2sql(add_months($to,$offset));
		}
		else {
			$from_sql = date2sql(add_days($from,$offset*7));
			$to_sql = date2sql(add_days($to,$offset*7));
		}
				

		$basic_sql = "SELECT sum(amount) AS amount,".
			($this->by_month ?
			  "ADDDATE(SUBDATE(tran_date, DAYOFMONTH(tran_date)-1), INTERVAL  ".(-$offset)." MONTH )"
				
			 : "ADDDATE(SUBDATE(tran_date, weekday(tran_date)), ".-($offset*7)." )")
		." AS trans_date
					FROM ".TB_PREF.$table."
					WHERE account IN (".$this->gl_accounts.")";
		if($this->display_total) {
			$sql = $basic_sql . " AND (tran_date <= '$from_sql')";
			$result = db_query($sql);
			$r = db_fetch_assoc($result);
			$rows[]= array('trans_date' => $from_sql, 'amount' => $this->convert*$r['amount'] , 'type' => $type);
		}

		$sql = $basic_sql." AND (tran_date > '$from_sql' AND tran_date < '$to_sql') GROUP BY trans_date";
	$result = db_query($sql);
	while($r = db_fetch_assoc($result)) {
			$rows[]= array('trans_date' => $r['trans_date'], 'amount' => $this->convert*$r['amount'] , 'type' => $type);
		}

	}


    function render($id, $title)
    {
	global $path_to_root;
	include_once($path_to_root."/reporting/includes/class.graphic.inc");

	$today = Today();
	if (!isset($this->weeks_past))
	    $this->weeks_past = 16;
	if (!isset($this->weeks_future))
	    $this->weeks_future = 4;
	if($this->by_month) {
	$from = add_months($today, -$this->weeks_past);
	$to = add_months($today, $this->weeks_future);
	}
	else {
	$from = add_days($today, -$this->weeks_past*7);
	$to = add_days($today, $this->weeks_future*7);
	}

	if (!isset($this->gl_accounts)) {
		$this->gl_accounts = 4000;
	}
	if (!isset($this->convert)) { $this->convert = -1;
	}

	$transactions = array();
		$this->get_account_transaction($from, $to, $transactions, 'transaction');
		$this->get_account_transaction($from, $to, $transactions, 'previous', -($this->by_month ? 12 : 52));
		$this->get_account_transaction($from, $to, $transactions, 'budget', 0, 'budget_trans');

		usort($transactions, function($a, $b) { return strcmp($a["trans_date"], $b["trans_date"]); } );
		print_r($transactions);

	//flag is not needed
	$flag = true;
	$table = array();

	if($this->display_total) {
		$table['cols'] = array(
			array('label' => 'Date', 'type' => 'string'),
			array('label' => 'Balance', 'type' => 'number'),
			array('label' => 'Previous Year', 'type' => 'number')
			,array('label' => 'Budget', 'type' => 'number')
		);
	}
	else {
		$table['cols'] = array(
			array('label' => 'Date', 'type' => 'string'),
			array('label' => 'Transaction', 'type' => 'number'),
			array('label' => 'Previous Year', 'type' => 'number')
			,array('label' => 'Budget', 'type' => 'number')
		);
	}

	// We group all transactions by type
	$rows = array();
	$total = 0;
	$previous_total;
	$transaction = 0;
	$smooth_transaction = 0;
	$previous = 0;
	$budget = 0;
	$week_budget = 0;
	$last_day = 0;
	//$date = nil;// $from; //add_days(Today(), -$this->weeks_past);
	$balance_date = nil;// $date;
	$i=0;
	while($r = $transactions[$i]) {
		if ($r['trans_date'] == null) {
			$total = $r['amount'];
		} else {

			$balance_date = sql2date($r['trans_date']);
			if(!isset($date)) $date = $balance_date;
			while (date1_greater_date2 ($balance_date, $date) ) {
				$temp = array();
				$temp[] = array('v' => (string) $date, 'f' => $date);
				if($this->display_total)  {
					$temp[] = array('v' => (float) $total, 'f' => number_format2($total, user_price_dec()));
					$temp[] = array('v' => (float) $previous_total, 'f' => number_format2($previous_total, user_price_dec()));
				}
				else {
					$temp[] = array('v' => (float) $transaction, 'f' => number_format2($transaction, user_price_dec()));
					$temp[] = array('v' => (float) $previous, 'f' => number_format2($previous, user_price_dec()));
				}
				$temp[] = array('v' => (float) $week_budget, 'f' => number_format2($week_budget, user_price_dec()));
				$rows[] = array('c' => $temp);
				if($this->by_month)
					$date = add_months($date,1);
				else
					$date = add_days($date,7);

				$transaction = 0;
				$previous = 0;
				$budget -= $week_budget;
				//$week_budget = min($budget, $week_budget);
			}
			$temp = array();
			switch($r['type']) {
			case 'transaction':
				$total += $r['amount'];
				$transaction = $r['amount'];
				break;
			case 'previous':
				$previous_total += $r['amount'];
				$previous = $r['amount'];
				break;
			case 'budget':
				$total += $r['amount'];
				$budget = $r['amount'];
				$week_budget = $budget/$this->week_of_month($date);
				break;
			}
		}
		$i+=1;
	}
	$temp[] = array('v' => (string) $balance_date, 'f' => $balance_date);
				if($this->display_total)  {
					$temp[] = array('v' => (float) $total, 'f' => number_format2($total, user_price_dec()));
					$temp[] = array('v' => (float) $previous_total, 'f' => number_format2($previous_total, user_price_dec()));
				}
				else {
					$temp[] = array('v' => (float) $transaction, 'f' => number_format2($transaction, user_price_dec()));
					$temp[] = array('v' => (float) $previous, 'f' => number_format2($previous, user_price_dec()));
				}
	$temp[] = array('v' => (float) $transaction, 'f' => number_format2($transaction, user_price_dec()));
	$temp[] = array('v' => (float) $previous, 'f' => number_format2($previous, user_price_dec()));
	$temp[] = array('v' => (float) $week_budget, 'f' => number_format2($week_budget, user_price_dec()));
	$budget -= $week_budget;
	//$week_budget = min($budget, $week_budget);
	$rows[] = array('c' => $temp);
	$date = $balance_date;
	if($this->by_month)
		$date = add_months($date,1);
	else
		$date = add_days($date,7);
/*
	$end_date = $to;
	while (date1_greater_date2 ($end_date, $date)) {
	    $temp = array();
	    $temp[] = array('v' => (string) $date, 'f' => $date);
	    $temp[] = array('v' => (float) $total, 'f' => number_format2($total, user_price_dec()));
	    $temp[] = array('v' => (float) $forecast, 'f' => number_format2($forecast, user_price_dec()));
	    $temp[] = array('v' => (float) $transaction, 'f' => number_format2($transaction, user_price_dec()));
	    $temp[] = array('v' => (float) $customer_invoice, 'f' => number_format2($customer_invoice, user_price_dec()));
	    $rows[] = array('c' => $temp);
	    $last_day++;
	    $date = add_days($date,1);
	}
 */

	//$table['rows'] = $rows;
	//$table['rows'] = array_merge(array_slice($rows,3, 71), array_slice($rows, 75, 15));
	//$table['rows'] = array_merge(array_slice($rows,0, 3), array_slice($rows, 6, 150));
	$table['rows'] = array_merge(array_slice($rows,0, 3), array_slice($rows, 6, 84));
	//$table['rows'] = array_merge(array($rows[0], $rows[74]), array_slice($rows, 75, 15));
	//$table['rows'] = array($rows[3], $rows[6], $rows[75], $rows[76], $rows[89]);
	$jsonTable = json_encode($table);

	$js =
		"google.load('visualization', '1', {'packages':['corechart','table']});
	google.setOnLoadCallback(drawChart".$id.");
	function drawChart".$id."() {
		var data = new google.visualization.DataTable(".$jsonTable.");
		var options = {";
		if ($this->graph_type == 'Table') {
			$js .= "sortAscending: false, sortColumn: 0,  ";
		}
		$js .="height: 300, ";
		$js .= "title: '".$title."'
			,seriesType:'bars'
			,series: {1: {type: 'bar', areaOpacity: 0.1}
			, 2: {type: 'steppedArea', areaOpacity: 0.1}
			, 0: {type: 'bar'}}
			, bar: {groupWidth: 20}
			, isStacked: false
    };
    var chart".$id." = new google.visualization.".$this->graph_type."(document.getElementById('widget_div_".$id."'));
    chart".$id.".draw(data, options);
    }";
    add_js_source($js);
    }

    function edit_param()
    {
	    $graph_types = array(
		    'ComboChart' => _("Chart"),
		    'Table' => _("Table")
	    );
	    $by_months = array(
		    0 => _("Week"),
		    1 => _("Month")
	    );
	    $display_totals = array(
		    0 => _("Transaction")
		    ,1 => _("Total")
	    );
	    $_POST['weeks_past'] = $this->weeks_past;
	    $_POST['weeks_future'] = $this->weeks_future;
	    $_POST['gl_accounts'] = $this->gl_accounts;
	    $_POST['convert'] = $this->convert;
	    $_POST['by_month'] = $this->by_month;
	    $_POST['graph_type'] = $this->graph_type;
	    text_row_ex(_("Weeks in past:"), 'weeks_past', 2);
	    text_row_ex(_("Weeks in future:"), 'weeks_future', 2);
	    text_row_ex(_("Accounts"), 'gl_accounts', null);
	    select_row(_("Invert"), 'convert', null, array(-1 => _("Yes"), 1=>_("No")), null);
	    //bank_accounts_list_cells(_("Account:"), 'gl_accounts', null);
	    select_row(_("Mode"), "display_total", null, $display_totals, null);
	    select_row(_("Period"), "by_month", null, $by_months, null);
	    select_row(_("Graph Type"), "graph_type", null, $graph_types, null);
    }

    function validate_param()
    {
	    $input_error = 0;
	    //if (!is_numeric($_POST['top']))
	    //  {
	    //      $input_error = 1;
	    //      display_error( _("The number of weeks must be numeric."));
	    //      set_focus('top');
	    //  }

	    //  if ($_POST['top'] == '')
	    //      $_POST['top'] = 10;

	    return $input_error;
    }

    function save_param()
    {
	    $param = array('weeks_past' => $_POST['weeks_past'],
		    'weeks_future' => $_POST['weeks_future'],
		    'gl_accounts' => $_POST['gl_accounts'],
		    'convert' => $_POST['convert'],
		    'graph_type' => $_POST['graph_type'],
		    'display_total' => $_POST['display_total'],
		    'by_month' => $_POST['by_month']);
	    return json_encode($param);
    }

}
