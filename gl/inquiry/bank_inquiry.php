<?php

$page_security = 8;
$path_to_root="../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/banking.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_("Bank Statement"), false, false, "", $js);

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//------------------------------------------------------------------------------------------------

start_form();

start_table("class='tablestyle_noborder'");
start_row();
bank_accounts_list_cells(_("Account:"), 'bank_account', null);

date_cells(_("From:"), 'TransAfterDate', null, -30);
date_cells(_("To:"), 'TransToDate');

submit_cells('Show',_("Show"));
end_row();
end_table();
end_form();

//------------------------------------------------------------------------------------------------


$date_after = date2sql($_POST['TransAfterDate']);
$date_to = date2sql($_POST['TransToDate']);
if (!isset($_POST['bank_account']))
	$_POST['bank_account'] = "";
$sql = "SELECT ".TB_PREF."bank_trans.*,name AS BankTransType FROM ".TB_PREF."bank_trans, ".TB_PREF."bank_trans_types
	WHERE ".TB_PREF."bank_trans.bank_act = '" . $_POST['bank_account'] . "'
	AND trans_date >= '$date_after'
	AND trans_date <= '$date_to'
	AND ".TB_PREF."bank_trans_types.id = ".TB_PREF."bank_trans.bank_trans_type_id
	ORDER BY trans_date,".TB_PREF."bank_trans.id";

$result = db_query($sql,"The transactions for '" . $_POST['bank_account'] . "' could not be retrieved");

$act = get_bank_account($_POST["bank_account"]);
display_heading($act['bank_account_name']." - ".$act['bank_curr_code']);

start_table($table_style);

$th = array(_("Type"), _("#"), _("Reference"), _("Type"), _("Date"),
	_("Debit"), _("Credit"), _("Balance"), _("Person/Item"), "");
table_header($th);	

$sql = "SELECT SUM(amount) FROM ".TB_PREF."bank_trans WHERE bank_act='" . $_POST['bank_account'] . "'
	AND trans_date < '$date_after'";
$before_qty = db_query($sql, "The starting balance on hand could not be calculated");

start_row("class='inquirybg'");
label_cell("<b>"._("Opening Balance")." - ".$_POST['TransAfterDate']."</b>", "colspan=5");
$bfw_row = db_fetch_row($before_qty);
$bfw = $bfw_row[0];
display_debit_or_credit_cells($bfw);
label_cell("");

end_row();
$running_total = $bfw;
$j = 1;
$k = 0; //row colour counter
while ($myrow = db_fetch($result)) 
{

	alt_table_row_color($k);

	$running_total += $myrow["amount"];

	$trandate = sql2date($myrow["trans_date"]);
	label_cell(systypes::name($myrow["type"]));
	label_cell(get_trans_view_str($myrow["type"],$myrow["trans_no"]));
	label_cell(get_trans_view_str($myrow["type"],$myrow["trans_no"],$myrow['ref']));
	label_cell($myrow["BankTransType"]);
	label_cell($trandate);
	display_debit_or_credit_cells($myrow["amount"]);
	amount_cell($running_total);
	label_cell(payment_person_types::person_name($myrow["person_type_id"],$myrow["person_id"]));
	label_cell(get_gl_view_str($myrow["type"], $myrow["trans_no"]));
	end_row();

	if ($j == 12)
	{
		$j = 1;
		table_header($th);	
	}
	$j++;
}
//end of while loop

start_row("class='inquirybg'");
label_cell("<b>" . _("Ending Balance")." - ". $_POST['TransToDate']. "</b>", "colspan=5");
display_debit_or_credit_cells($running_total);
label_cell("");
end_row();
end_table(2);

//------------------------------------------------------------------------------------------------

end_page();

?>
