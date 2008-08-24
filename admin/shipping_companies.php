<?php


$page_security = 14;
$path_to_root="..";
include($path_to_root . "/includes/session.inc");
page(_("Shipping Company"));
include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------------------

function can_process() 
{
	if (strlen($_POST['shipper_name']) == 0) 
	{
		display_error(_("The shipping company name cannot be empty."));
		set_focus('shipper_name');
		return false;
	}
	return true;
}

//----------------------------------------------------------------------------------------------
if ($Mode=='ADD_ITEM' && can_process()) 
{

	$sql = "INSERT INTO ".TB_PREF."shippers (shipper_name, contact, phone, address)
		VALUES (" . db_escape($_POST['shipper_name']) . ", " .
		db_escape($_POST['contact']). ", " .
		db_escape($_POST['phone']). ", " .
		db_escape($_POST['address']) . ")";

	db_query($sql,"The Shipping Company could not be added");
	display_notification(_('New shipping company has been added'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------

if ($Mode=='UPDATE_ITEM' && can_process()) 
{

	$sql = "UPDATE ".TB_PREF."shippers SET shipper_name=" . db_escape($_POST['shipper_name']). " ,
		contact =" . db_escape($_POST['contact']). " ,
		phone =" . db_escape($_POST['phone']). " ,
		address =" . db_escape($_POST['address']). "
		WHERE shipper_id = $selected_id";

	db_query($sql,"The shipping company could not be updated");
	display_notification(_('Selected shipping company has been updated'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
// PREVENT DELETES IF DEPENDENT RECORDS IN 'sales_orders'

	$sql= "SELECT COUNT(*) FROM ".TB_PREF."sales_orders WHERE ship_via='$selected_id'";
	$result = db_query($sql,"check failed");
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		$cancel_delete = 1;
		display_error(_("Cannot delete this shipping company because sales orders have been created using this shipper."));
	} 
	else 
	{
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'

		$sql= "SELECT COUNT(*) FROM ".TB_PREF."debtor_trans WHERE ship_via='$selected_id'";
		$result = db_query($sql,"check failed");
		$myrow = db_fetch_row($result);
		if ($myrow[0] > 0) 
		{
			$cancel_delete = 1;
			display_error(_("Cannot delete this shipping company because invoices have been created using this shipping company."));
		} 
		else 
		{
			$sql="DELETE FROM ".TB_PREF."shippers WHERE shipper_id=$selected_id";
			db_query($sql,"could not delete shipper");
			display_notification(_('Selected shipping company has been deleted'));
		}
	}
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	unset($_POST);
}
//----------------------------------------------------------------------------------------------

$sql = "SELECT * FROM ".TB_PREF."shippers ORDER BY shipper_id";
$result = db_query($sql,"could not get shippers");

start_form();
start_table($table_style);
$th = array(_("Name"), _("Contact Person"), _("Phone Number"), _("Address"), "", "");
table_header($th);

$k = 0; //row colour counter

while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);
	label_cell($myrow["shipper_name"]);
	label_cell($myrow["contact"]);
	label_cell($myrow["phone"]);
	label_cell($myrow["address"]);
 	edit_button_cell("Edit".$myrow[0], _("Edit"));
 	edit_button_cell("Delete".$myrow[0], _("Delete"));
	end_row();
}

end_table();
end_form();
echo '<br>';

//----------------------------------------------------------------------------------------------

start_form();

start_table($table_style2);

if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing Shipper

		$sql = "SELECT * FROM ".TB_PREF."shippers WHERE shipper_id=$selected_id";

		$result = db_query($sql, "could not get shipper");
		$myrow = db_fetch($result);

		$_POST['shipper_name']	= $myrow["shipper_name"];
		$_POST['contact']	= $myrow["contact"];
		$_POST['phone']	= $myrow["phone"];
		$_POST['address'] = $myrow["address"];
	}
	hidden('selected_id', $selected_id);
}

text_row_ex(_("Name:"), 'shipper_name', 40);

text_row_ex(_("Contact Person:"), 'contact', 30);

text_row_ex(_("Phone Number:"), 'phone', 20);

text_row_ex(_("Address:"), 'address', 50);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', true);

end_form();
end_page();
?>
