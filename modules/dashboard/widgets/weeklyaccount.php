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

    var $days_past;
    var $days_future;
    var $bank_act;
    var $graph_type;

    function weeklyaccount($params='')
    {
        if (isset($params))
        {
            $data=json_decode(html_entity_decode($params));
            if ($data != null) {
                $this->bank_act = $data->bank_act;
                $this->graph_type = $data->graph_type;
                if ($data->days_past != '')
                    $this->days_past = $data->days_past;
                if ($data->days_future != '')
                    $this->days_future = $data->days_future;
            }
        }
    }

	


	function get_account_transaction($from, $to, &$rows) {
		$to_sql = date2sql($to);
		$from_sql = date2sql($from);
		$basic_sql = "SELECT sum(amount) AS amount, SUBDATE(tran_date, weekday(tran_date)) AS trans_date
					FROM ".TB_PREF."gl_trans
					WHERE account IN (4000)";
		$sql = $basic_sql . " AND (tran_date <= '$from_sql')";
		$result = db_query($sql);
		$r = db_fetch_assoc($result);
		$rows[]= array('trans_date' => $to_sql, 'amount' => $r['amount'] , 'type' => 'transaction');

		$sql = $basic_sql." AND (tran_date > '$from_sql' AND tran_date < '$to_sql') GROUP BY trans_date";
	$result = db_query($sql);
	while($r = db_fetch_assoc($result)) {
			$rows[]= array('trans_date' => $r['trans_date'], 'amount' => $r['amount'] , 'type' => 'transaction');
		}

	}
	



    function render($id, $title)
    {
	global $path_to_root;
	include_once($path_to_root."/reporting/includes/class.graphic.inc");

	$today = Today();
	if (!isset($data->days_past))
	    $this->days_past = 60;
	if (!isset($data->days_future))
	    $this->days_future = 60;
		$from = add_days($today, -$this->days_past);
		$to = add_days($today, $this->days_future);

	$transactions = array();
		$this->get_account_transaction($from, $to, $transactions);

		usort($transactions, function($a, $b) { return strcmp($a["trans_date"], $b["trans_date"]); } );
		print_r($transactions);

	//flag is not needed
	$flag = true;
	$table = array();
	$table['cols'] = array(
	    array('label' => 'Date', 'type' => 'string'),
	    array('label' => 'Balance', 'type' => 'number')
	);

	// We group all transactions by type
	$rows = array();
	$total = 0;
	$last_day = 0;
	$date = add_days(Today(), -$this->days_past);
	$balance_date = $date;
		$i=0;
	while($r = $transactions[$i]) {
	    if ($r['trans_date'] == null) {
		$total = $r['amount'];
	    } else {

		$balance_date = sql2date($r['trans_date']);
		while (date1_greater_date2 ($balance_date, $date) ) {
		    $temp = array();
		    $temp[] = array('v' => (string) $date, 'f' => $date);
		    $temp[] = array('v' => (float) $total, 'f' => number_format2($total, user_price_dec()));
		    $rows[] = array('c' => $temp);
		    $date = add_days($date,1);
			$transaction = 0;
		}
		$temp = array();
		switch($r['type']) {
		case 'transaction':
			$total += $r['amount'];
			$transaction = $r['amount'];
			break;
		}
	    }
			$i+=1;
	}
	$temp[] = array('v' => (string) $balance_date, 'f' => $balance_date);
	$temp[] = array('v' => (float) $total, 'f' => number_format2($total, user_price_dec()));
	$rows[] = array('c' => $temp);
	$date = $balance_date;
	$date = add_days($date,1);
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
		if ($this->graph_type != 'Table')
			$js .="height: 300, ";
		$js .= "title: '".$title."'
			,seriesType:'bars'
			,series: {0: {type: 'steppedArea'}
			, 1: {type: 'steppedArea'}
			, bar: {groupWidth: 100}
			, isStacked: true
    }
    };
	var chart".$id." = new google.visualization.ComboChart(document.getElementById('widget_div_".$id."'));
	chart".$id.".draw(data, options);
    }";
	add_js_source($js);
    }

	function edit_param()
	{
		$graph_types = array(
			'LineChart' => _("Line Chart"),
			'ColumnChart' => _("Column Chart"),
			'Table' => _("Table")
		);
		$_POST['days_past'] = $this->days_past;
		$_POST['days_future'] = $this->days_future;
		$_POST['bank_act'] = $this->bank_act;
		$_POST['graph_type'] = $this->graph_type;
		text_row_ex(_("Days in past:"), 'days_past', 2);
		text_row_ex(_("Days in future:"), 'days_future', 2);
		bank_accounts_list_cells(_("Account:"), 'bank_act', null);
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
		$param = array('days_past' => $_POST['days_past'],
			'days_future' => $_POST['days_future'],
			'bank_act' => $_POST['bank_act'],
			'graph_type' => $_POST['graph_type']);
		return json_encode($param);
	}

}
