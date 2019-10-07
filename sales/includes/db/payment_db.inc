<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
// Find the tax group of the first branch, as we have no choice
function get_customer_tax_group_for_ppd($customer_id) {
    $sql = "SELECT tax_group_id FROM ".TB_PREF."cust_branch 
            WHERE debtor_no=".db_escape($customer_id)." AND (inactive = 0 OR distable_trans = 1)  LIMIT 1";

    $result = db_query($sql, "No branch found for given customer");
    $row = db_fetch_row($result);
    return $row[0]; // first field
    
}
function get_ppd_tax($customer_id, $branch_id, $discount) {
    $tax_group_id = get_cust_branch($customer_id, $branch_id)["tax_group_id"];
    $taxes =  get_tax_for_items(array("EPD"), array($discount),0 , $tax_group_id, 1);
    $ret_array = array();
    foreach($taxes as $tax) {
        if ($tax['Net'] == 0) continue;
        $ret_array[]= $tax;
    }

    return $ret_array;
    
}
/*
  Write/update customer payment.

  Warning: $rate is leaved here for extensions compatibility reasons, will be removed in 2.4
	since 2.3.17 is not used: use $bank_amount instead.

	$amount - in customer currency (ex. discount)
	$discount - in customer currency
	$bank_amount - in bank currency (before charge)
	$charge - in bank currency
*/
function write_customer_payment($trans_no, $customer_id, $branch_id, $bank_account,
                                $date_, $ref, $amount, $discount, $vat_discount_unused, $memo_, $rate=0, $charge=0, $bank_amount=0)
{
	global $Refs, $vat_ppd_account;

	begin_transaction();
	$args = func_get_args(); while (count($args) < 12) $args[] = 0;
	$args = (object)array_combine(array('trans_no', 'customer_id', 'branch_id', 'bank_account', 
		'date_', 'ref', 'amount', 'discount', 'memo_','rate','charge', 'bank_amount'), $args);
	hook_db_prewrite($args, ST_CUSTPAYMENT);

	$company_record = get_company_prefs();
    $vat_discount=0;
    $ppd_tax_array = array();
	if ($discount != 0)	{
        // TODO everything is hardcoded, until we can do better
        $pdd_tax_array = get_ppd_tax($customer_id, $branch_id, $discount);
        foreach($pdd_tax_array as $ppd_tax) {
            $value = $ppd_tax['Value'];
            $vat_discount += $value;
        }
    }

	if ($trans_no != 0) {
	  delete_comments(ST_CUSTPAYMENT, $trans_no);
	  void_bank_trans(ST_CUSTPAYMENT, $trans_no, true);
	  void_gl_trans(ST_CUSTPAYMENT, $trans_no, true);
	  void_cust_allocations(ST_CUSTPAYMENT, $trans_no, $date_);
	}

	$bank = get_bank_account($bank_account);

    if(!$rate)
        $rate = get_exchange_rate_from_to(get_customer_currency($customer_id),
                                          $bank['bank_curr_code'], $date_ );
	if (!$bank_amount) 	// backward compatibility workaround
	{

		$bank_amount = $amount/$rate;
	}

	// do not use $rate here: global rate stored in exrate table is always used
	$payment_no = write_customer_trans(ST_CUSTPAYMENT, $trans_no, $customer_id, $branch_id, 
                                       $date_, $ref, $amount, 0, $discount, 0, $vat_discount,
                                       0,0,0,0,0,"",0,0,0,0,0,0,0);

	$bank_gl_account = get_bank_gl_account($bank_account);

	$total = 0;

	/* Bank account entry first */
	$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,
		$bank_gl_account, 0, 0, '', ($bank_amount - $charge),  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id);

	if ($branch_id != ANY_NUMERIC) {

		$branch_data = get_branch_accounts($branch_id);

		$debtors_account = $branch_data["receivables_account"];
		$discount_account = $branch_data["payment_discount_account"];

	} else {
		$debtors_account = $company_record["debtors_act"];
		$discount_account = $company_record["default_prompt_payment_act"];
	}

	if (($discount + $amount) != 0)	{
	/* Now Credit Debtors account with receipts + discounts */
	$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
		$debtors_account, 0, 0, -($discount + $amount), $customer_id,
		"Cannot insert a GL transaction for the debtors account credit");
	}
	if ($discount != 0)	{
        // TODO everything is hardcoded, until we can do better
        $r = print_r($pdd_tax_array, TRUE);
        foreach($pdd_tax_array as $ppd_tax) {
            $net = $ppd_tax['Net'];
            if($net == 0 ) continue;
            $vat_discount_account = $ppd_tax['sales_gl_code'];
            $tax_id = $ppd_tax['tax_type_id'];
            $tax_rate = $ppd_tax['rate'];
            $value = $ppd_tax['Value'];
            
            $total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
                                            $vat_discount_account, 0, 0, $value, $customer_id,
                                            "Cannot insert a GL transaction for the payment discount debit");
            add_trans_tax_details( ST_CUSTPAYMENT, $payment_no, $tax_id, $tax_rate, false
                                   , $value, $net, $rate, $date_, "PPD");
            //                                     ^ ex rate TODO get real one
        }
		/* Now Debit discount account with discounts allowed*/
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
                                        $discount_account, 0, 0, $discount-$vat_discount, $customer_id,
                                        "Cannot insert a GL transaction for the payment discount debit");
	}

	if ($charge != 0)	{
		/* Now Debit bank charge account with charges */
		$charge_act = get_company_pref('bank_charge_act');
		$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$charge_act, 0, 0, '', 
			$charge, $bank['bank_curr_code'], PT_CUSTOMER,  $customer_id);
	}


	/*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
	if ($total != 0)
	{
		$variance_act = get_company_pref('exchange_diff_act');
		add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$variance_act, 0, 0, '',
			-$total, null, PT_CUSTOMER,  $customer_id);
	}

	/*now enter the bank_trans entry */
	add_bank_trans(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref,
		$date_, $bank_amount - $charge, PT_CUSTOMER, $customer_id);

	add_comments(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);

	$Refs->save(ST_CUSTPAYMENT, $payment_no, $ref);

	$args->trans_no = $payment_no;
	hook_db_postwrite($args, ST_CUSTPAYMENT);
	commit_transaction();

	return $payment_no;
}

//-------------------------------------------------------------------------------------------------

function void_customer_payment($type, $type_no)
{
	begin_transaction();

	hook_db_prevoid($type, $type_no);
	void_bank_trans($type, $type_no, true);
	void_gl_trans($type, $type_no, true);
	void_cust_allocations($type, $type_no);
	void_customer_trans($type, $type_no);

	commit_transaction();
}

/*
	Retrieve bank charge amount from GL postings for customer payment.
	. Bank charge is not stored explicitly in database as of 2.3.xx
	. Due to roundings the retrieved charge can differ from the original amount when bank_curr!=home_curr && bank_curr!=cust_curr
*/
function get_cust_bank_charge($type, $trans_no)
{

	// restore charge amount from amounts in bank currency if possible, otherwise calculate from GL posting with exchange rate used for amount posting
	$sql = "SELECT	IF(act.bank_curr_code=home_curr.value, charge.amount,
					IF(act.bank_curr_code=debtor.curr_code, -(trans.amount-ar.ov_amount+ar.ov_discount),
					IFNULL(charge.amount*trans.amount/pmt.amount, 0)))
			FROM ".TB_PREF."bank_trans trans
				LEFT JOIN ".TB_PREF."bank_accounts act ON trans.bank_act=act.id
				LEFT JOIN ".TB_PREF."sys_prefs charge_act ON charge_act.name='bank_charge_act'
				LEFT JOIN ".TB_PREF."sys_prefs home_curr ON home_curr.name='curr_default'
				LEFT JOIN ".TB_PREF."gl_trans charge ON charge.type=trans.type AND charge.type_no=trans.trans_no AND charge.account=charge_act.value
				LEFT JOIN ".TB_PREF."gl_trans pmt ON pmt.type=trans.type AND pmt.type_no=trans.trans_no AND pmt.account=act.account_code
				LEFT JOIN ".TB_PREF."debtors_master debtor ON trans.person_id=debtor.debtor_no AND trans.person_type_id=".PT_CUSTOMER."
				LEFT JOIN ".TB_PREF."debtor_trans ar ON trans.type=ar.type AND trans.trans_no=ar.trans_no
			WHERE pmt.amount!=0 AND charge.amount!=0 AND trans.amount!=0
				AND trans.type=".db_escape($type)." AND trans.trans_no=".db_escape($trans_no);

	$result = db_query($sql, "cannot retrieve bank charge");

	if (!db_num_rows($result))
		return 0;

	$myrow = db_fetch($result);
	return $myrow['0'];
}

?>
