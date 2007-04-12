<?php

$page_security = 2;

// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Balances
// ----------------------------------------------------------------
$path_to_root="../";

include_once($path_to_root . "includes/session.inc");
include_once($path_to_root . "includes/date_functions.inc");
include_once($path_to_root . "includes/data_checks.inc");
include_once($path_to_root . "gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

// trial_inquiry_controls();
print_customer_balances();

function get_transactions($debtorno, $date)
{
	$date = date2sql($date);
	
    $sql = "SELECT ".TB_PREF."debtor_trans.*, ".TB_PREF."sys_types.type_name, 
		(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_discount)
		AS TotalAmount, ".TB_PREF."debtor_trans.alloc AS Allocated,
		((".TB_PREF."debtor_trans.type = 10)
		AND ".TB_PREF."debtor_trans.due_date < '$date') AS OverDue
    	FROM ".TB_PREF."debtor_trans, ".TB_PREF."sys_types
    	WHERE ".TB_PREF."debtor_trans.tran_date <= '$date' AND ".TB_PREF."debtor_trans.debtor_no = '$debtorno'
    	AND ".TB_PREF."debtor_trans.type = ".TB_PREF."sys_types.type_id
    	ORDER BY ".TB_PREF."debtor_trans.tran_date";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_customer_balances()
{
    global $path_to_root;

	include_once($path_to_root . "reporting/includes/pdf_report.inc");

    $to = $_POST['PARAM_0'];
    $fromcust = $_POST['PARAM_1'];
    $tocust = $_POST['PARAM_2'];
    $currency = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];

	if ($fromcust == null)
		$fromcust = 0;
	if ($tocust == null)
		$tocust = 0;
    $dec = user_price_dec();

	if ($currency == reserved_words::get_all())
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;

	$cols = array(0, 100, 130, 190,	250, 320, 385, 450,	515);

	$headers = array(_('Trans Type'), _('#'), _('Date'), _('Due Date'), _('Charges'), _('Credits'),
		_('Allocated'), 	_('Outstanding'));

	$aligns = array('left',	'left',	'left',	'left',	'right', 'right', 'right', 'right');
    
    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('End Date'), 'from' => $to, 		'to' => ''),
    				    2 => array('text' => _('Customer'), 'from' => get_customer_name($fromcust),
                            	'to' => get_customer_name($tocust)),
    				    3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''));

    $rep = new FrontReport(_('Customer Balances'), "CustomerBalances.pdf", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	$total = array();
	$grandtotal = array();

	$sql = "SELECT debtor_no, name, curr_code FROM ".TB_PREF."debtors_master
		WHERE debtor_no>=$fromcust AND debtor_no<=$tocust ORDER BY name";
	$result = db_query($sql, "The customers could not be retrieved");

	while ($myrow = db_fetch($result)) 
	{
		if (!$convert && $currency != $myrow['curr_code'])
			continue;
		$rep->fontSize += 2;
		$rep->TextCol(0, 3, $myrow['name']);
		if ($convert)
		{
			$rate = get_exchange_rate_from_home_currency($myrow['curr_code'], $to);
			$rep->TextCol(3, 4,	$myrow['curr_code']);
		}
		else
			$rate = 1.0;
		$rep->fontSize -= 2;
		$rep->NewLine(1, 2);
		$res = get_transactions($myrow['debtor_no'], $to);
		if (db_num_rows($res)==0)
			continue;
		$rep->Line($rep->row + 4);
		$total[0] = $total[1] = $total[2] = $total[3] = 0.0; 
		while ($trans = db_fetch($res))
		{
			$rep->NewLine(1, 2);
			$rep->TextCol(0, 1, $trans['type_name']);
			$rep->TextCol(1, 2,	$trans['reference']);
			$rep->TextCol(2, 3,	sql2date($trans['tran_date']));
			if ($trans['type'] == 10)	
				$rep->TextCol(3, 4,	sql2date($trans['due_date']));
			$item[0] = $item[1] = 0.0;		
			if ($trans['TotalAmount'] > 0.0)	
			{
				$item[0] = abs($trans['TotalAmount']) * $rate; 		
				$rep->TextCol(4, 5,	number_format2($item[0], $dec));
			}		
			else	
			{
				$item[1] = Abs($trans['TotalAmount']) * $rate; 		
				$rep->TextCol(5, 6,	number_format2($item[1], $dec));
			}		
			$item[2] = $trans['Allocated'] * $rate;
			$rep->TextCol(6, 7,	number_format2($item[2], $dec));
			if ($trans['type'] == 10)
				$item[3] = ($trans['TotalAmount'] - $trans['Allocated']) * $rate; 		
			else	
				$item[3] = ($trans['TotalAmount'] + $trans['Allocated']) * $rate; 		
			$rep->TextCol(7, 8, number_format2($item[3], $dec));
			for ($i = 0; $i < 4; $i++)
			{
				$total[$i] += $item[$i];
				$grandtotal[$i] += $item[$i];
			}	
		}
		$rep->Line($rep->row - 8);
		$rep->NewLine(2);
		$rep->TextCol(0, 3, _('Total'));
		for ($i = 0; $i < 4; $i++)
			$rep->TextCol($i + 4, $i + 5, number_format2($total[$i], $dec));
    	$rep->Line($rep->row  - 4);
    	$rep->NewLine(2);
	}
	$rep->fontSize += 2;
	$rep->TextCol(0, 3, _('Grand Total'));
	$rep->fontSize -= 2;
	for ($i = 0; $i < 4; $i++)
		$rep->TextCol($i + 4, $i + 5, number_format2($grandtotal[$i], $dec));
	$rep->Line($rep->row  - 4);
    $rep->End();
}

?>