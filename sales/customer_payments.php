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
$path_to_root="..";
$page_security = 3;
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}
if ($use_date_picker) {
	$js .= get_js_date_picker();
}
page(_("Customer Payment Entry"), false, false, "", $js);

//----------------------------------------------------------------------------------------------

check_db_has_customers(_("There are no customers defined in the system."));

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
if ($ret = context_restore()) {
	if(isset($ret['customer_id']))
		$_POST['customer_id'] = $ret['customer_id'];
	if(isset($ret['branch_id']))
		$_POST['BranchID'] = $ret['branch_id'];
}
if (isset($_POST['_customer_id_editor'])) {
	context_call($path_to_root.'/sales/manage/customers.php?debtor_no='.$_POST['customer_id'], 
		array( 'customer_id', 'BranchID', 'bank_account', 'DateBanked', 
			'ref', 'amount', 'discount', 'memo_') );
}

if (isset($_GET['AddedID'])) {
	$payment_no = $_GET['AddedID'];

	display_notification_centered(_("The customer payment has been successfully entered."));

	display_note(get_gl_view_str(12, $payment_no, _("&View the GL Journal Entries for this Customer Payment")));

	hyperlink_params($path_to_root . "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");

	hyperlink_no_params($path_to_root . "/sales/customer_payments.php", _("Enter Another &Customer Payment"));
	br(1);
	end_page();
	exit;
}

//----------------------------------------------------------------------------------------------

function can_process()
{
	if (!isset($_POST['DateBanked']) || !is_date($_POST['DateBanked'])) {
		display_error(_("The entered date is invalid. Please enter a valid date for the payment."));
		set_focus('DateBanked');
		return false;
	} elseif (!is_date_in_fiscalyear($_POST['DateBanked'])) {
		display_error(_("The entered date is not in fiscal year."));
		set_focus('DateBanked');
		return false;
	}

	if (!references::is_valid($_POST['ref'])) {
		display_error(_("You must enter a reference."));
		set_focus('ref');
		return false;
	}

	if (!is_new_reference($_POST['ref'], 12)) {
		display_error(_("The entered reference is already in use."));
		set_focus('ref');
		return false;
	}

	if (!check_num('amount', 0)) {
		display_error(_("The entered amount is invalid or negative and cannot be processed."));
		set_focus('amount');
		return false;
	}

	if (isset($_POST['_ex_rate']) && !check_num('_ex_rate', 0.000001))
	{
		display_error(_("The exchange rate must be numeric and greater than zero."));
		set_focus('_ex_rate');
		return false;
	}

	if ($_POST['discount'] == "") 
	{
		$_POST['discount'] = 0;
	}

	if (!check_num('discount')) {
		display_error(_("The entered discount is not a valid number."));
		set_focus('discount');
		return false;
	}

	if ((input_num('amount') - input_num('discount') <= 0)) {
		display_error(_("The balance of the amount and discout is zero or negative. Please enter valid amounts."));
		set_focus('discount');
		return false;
	}

	return true;
}

//----------------------------------------------------------------------------------------------

// validate inputs
if (isset($_POST['AddPaymentItem'])) {

	if (!can_process()) {
		unset($_POST['AddPaymentItem']);
	}
}
if (isset($_POST['_customer_id_button'])) {
//	unset($_POST['branch_id']);
	$Ajax->activate('BranchID');
}
if (isset($_POST['_DateBanked_changed'])) {
  $Ajax->activate('_ex_rate');
}
//----------------------------------------------------------------------------------------------

if (isset($_POST['AddPaymentItem'])) {
	
	$cust_currency = get_customer_currency($_POST['customer_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);
	$comp_currency = get_company_currency();
	if ($comp_currency != $bank_currency && $bank_currency != $cust_currency)
		$rate = 0;
	else
		$rate = input_num('_ex_rate');

	$payment_no = write_customer_payment(0, $_POST['customer_id'], $_POST['BranchID'],
		$_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'],
		input_num('amount'), input_num('discount'), $_POST['memo_'], $rate);
	meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_no");
}

//----------------------------------------------------------------------------------------------

function read_customer_data()
{
	$sql = "SELECT ".TB_PREF."debtors_master.pymt_discount,
		".TB_PREF."credit_status.dissallow_invoices
		FROM ".TB_PREF."debtors_master, ".TB_PREF."credit_status
		WHERE ".TB_PREF."debtors_master.credit_status = ".TB_PREF."credit_status.id
			AND ".TB_PREF."debtors_master.debtor_no = ".db_escape($_POST['customer_id']);

	$result = db_query($sql, "could not query customers");

	$myrow = db_fetch($result);

	$_POST['HoldAccount'] = $myrow["dissallow_invoices"];
	$_POST['pymt_discount'] = $myrow["pymt_discount"];
	$_POST['ref'] = references::get_next(12);
}

//-------------------------------------------------------------------------------------------------

function display_item_form()
{
	global $table_style2;

	start_outer_table($table_style2, 5);
	table_section(1);

	if (!isset($_POST['customer_id']))
		$_POST['customer_id'] = get_global_customer(false);
	if (!isset($_POST['DateBanked'])) {
		$_POST['DateBanked'] = Today();
		if (!is_date_in_fiscalyear($_POST['DateBanked'])) {
			$_POST['DateBanked'] = end_fiscalyear();
		}
	}
	customer_list_row(_("From Customer:"), 'customer_id', null, false, true);
	if (db_customer_has_branches($_POST['customer_id'])) {
		customer_branches_list_row(_("Branch:"), $_POST['customer_id'], 'BranchID', null, false, true, true);
	} else {
		hidden('BranchID', reserved_words::get_any_numeric());
	}

	read_customer_data();

	set_global_customer($_POST['customer_id']);
	if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0)	{
		end_outer_table();
		display_error(_("This customer account is on hold."));
	} else {
		$display_discount_percent = percent_format($_POST['pymt_discount']*100) . "%";

		amount_row(_("Amount:"), 'amount');

		amount_row(_("Amount of Discount:"), 'discount');

		label_row(_("Customer prompt payment discount :"), $display_discount_percent);

		date_row(_("Date of Deposit:"), 'DateBanked','',null, 0, 0, 0, null, true);

		table_section(2);

		bank_accounts_list_row(_("Into Bank Account:"), 'bank_account', null, true);

		$comp_currency = get_company_currency();
		$cust_currency = get_customer_currency($_POST['customer_id']);
		$bank_currency = get_bank_account_currency($_POST['bank_account']);

		if ($cust_currency != $bank_currency) {
			exchange_rate_display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
		}

		text_row(_("Reference:"), 'ref', null, 20, 40);

		textarea_row(_("Memo:"), 'memo_', null, 22, 4);

		end_outer_table(1);

		if ($cust_currency != $bank_currency)
			display_note(_("Amount and discount are in customer's currency."));

		echo"<br>";

		submit_center('AddPaymentItem', _("Add Payment"), true, '', true);
	}

	echo "<br>";
}

//----------------------------------------------------------------------------------------------

start_form();

display_item_form();

end_form();
end_page();
?>
