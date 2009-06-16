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
$page_security = 10;
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/manufacturing.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/manufacturing/includes/manufacturing_db.inc");
include_once($path_to_root . "/manufacturing/includes/manufacturing_ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_("Work Order Entry"), false, false, "", $js);


check_db_has_manufacturable_items(_("There are no manufacturable items defined in the system."));

check_db_has_locations(("There are no inventory locations defined in the system."));

//---------------------------------------------------------------------------------------

if (isset($_GET['trans_no']))
{
	$selected_id = $_GET['trans_no'];
}
elseif(isset($_POST['selected_id']))
{
	$selected_id = $_POST['selected_id'];
}

//---------------------------------------------------------------------------------------

if (isset($_GET['AddedID']))
{
	$id = $_GET['AddedID'];
	$stype = systypes::work_order();

	display_notification_centered(_("The work order been added."));

    display_note(get_trans_view_str($stype, $id, _("View this Work Order")));

	if ($_GET['type'] != wo_types::advanced())
	{
		include_once($path_to_root . "/reporting/includes/reporting.inc");
    	display_note(get_gl_view_str($stype, $id, _("View the GL Journal Entries for this Work Order")), 1);
    	$ar = array('PARAM_0' => $_GET['date'], 'PARAM_1' => $_GET['date'], 'PARAM_2' => $stype); 
    	display_note(print_link(_("Print the GL Journal Entries for this Work Order"), 702, $ar), 1);
	}
	
	safe_exit();
}

//---------------------------------------------------------------------------------------

if (isset($_GET['UpdatedID']))
{
	$id = $_GET['UpdatedID'];

	display_notification_centered(_("The work order been updated."));
	safe_exit();
}

//---------------------------------------------------------------------------------------

if (isset($_GET['DeletedID']))
{
	$id = $_GET['DeletedID'];

	display_notification_centered(_("Work order has been deleted."));
	safe_exit();
}

//---------------------------------------------------------------------------------------

if (isset($_GET['ClosedID']))
{
	$id = $_GET['ClosedID'];

	display_notification_centered(_("This work order has been closed. There can be no more issues against it.") . " #$id");
	safe_exit();
}

//---------------------------------------------------------------------------------------

function safe_exit()
{
	global $path_to_root;

	hyperlink_no_params("", _("Enter a new work order"));
	hyperlink_no_params("search_work_orders.php", _("Select an existing work order"));
	
	display_footer_exit();
}

//-------------------------------------------------------------------------------------
if (!isset($_POST['date_']))
{
	$_POST['date_'] = Today();
	if (!is_date_in_fiscalyear($_POST['date_']))
		$_POST['date_'] = end_fiscalyear();
}

function can_process()
{
	global $selected_id;

	if (!isset($selected_id))
	{
    	if (!references::is_valid($_POST['wo_ref']))
    	{
    		display_error(_("You must enter a reference."));
			set_focus('wo_ref');
    		return false;
    	}

    	if (!is_new_reference($_POST['wo_ref'], systypes::work_order()))
    	{
    		display_error(_("The entered reference is already in use."));
			set_focus('wo_ref');
    		return false;
    	}
	}

	if (!check_num('quantity', 0))
	{
		display_error( _("The quantity entered is invalid or less than zero."));
		set_focus('quantity');
		return false;
	}

	if (!is_date($_POST['date_']))
	{
		display_error( _("The date entered is in an invalid format."));
		set_focus('date_');
		return false;
	}
	elseif (!is_date_in_fiscalyear($_POST['date_']))
	{
		display_error(_("The entered date is not in fiscal year."));
		set_focus('date_');
		return false;
	}
	// only check bom and quantites if quick assembly
	if (!($_POST['type'] == wo_types::advanced()))
	{
        if (!has_bom($_POST['stock_id']))
        {
        	display_error(_("The selected item to manufacture does not have a bom."));
			set_focus('stock_id');
        	return false;
        }

		if ($_POST['Labour'] == "")
			$_POST['Labour'] = price_format(0);
    	if (!check_num('Labour', 0))
    	{
    		display_error( _("The labour cost entered is invalid or less than zero."));
			set_focus('Labour');
    		return false;
    	}
		if ($_POST['Costs'] == "")
			$_POST['Costs'] = price_format(0);
    	if (!check_num('Costs', 0))
    	{
    		display_error( _("The cost entered is invalid or less than zero."));
			set_focus('Costs');
    		return false;
    	}

        if (!sys_prefs::allow_negative_stock())
        {
        	if ($_POST['type'] == wo_types::assemble())
        	{
        		// check bom if assembling
                $result = get_bom($_POST['stock_id']);

            	while ($bom_item = db_fetch($result))
            	{

            		if (has_stock_holding($bom_item["ResourceType"]))
            		{

                		$quantity = $bom_item["quantity"] * input_num('quantity');

                        $qoh = get_qoh_on_date($bom_item["component"], $bom_item["loc_code"], $_POST['date_']);
                		if (-$quantity + $qoh < 0)
                		{
                			display_error(_("The work order cannot be processed because there is an insufficient quantity for component:") .
                				" " . $bom_item["component"] . " - " .  $bom_item["description"] . ".  " . _("Location:") . " " . $bom_item["location_name"]);
							set_focus('quantity');
        					return false;
                		}
            		}
            	}
        	}
        	elseif ($_POST['type'] == wo_types::unassemble())
        	{
        		// if unassembling, check item to unassemble
				$qoh = get_qoh_on_date($_POST['stock_id'], $_POST['StockLocation'], $_POST['date_']);
        		if (-input_num('quantity') + $qoh < 0)
        		{
        			display_error(_("The selected item cannot be unassembled because there is insufficient stock."));
					return false;
        		}
        	}
    	}
     }
     else
     {
    	if (!is_date($_POST['RequDate']))
    	{
			set_focus('RequDate');
    		display_error( _("The date entered is in an invalid format."));
    		return false;
		}
		//elseif (!is_date_in_fiscalyear($_POST['RequDate']))
		//{
		//	display_error(_("The entered date is not in fiscal year."));
		//	return false;
		//}
    	if (isset($selected_id))
    	{
    		$myrow = get_work_order($selected_id, true);

    		if ($_POST['units_issued'] > input_num('quantity'))
    		{
				set_focus('quantity');
    			display_error(_("The quantity cannot be changed to be less than the quantity already manufactured for this order."));
        		return false;
    		}
    	}
	}

	return true;
}

//-------------------------------------------------------------------------------------

if (isset($_POST['ADD_ITEM']) && can_process())
{
	if (!isset($_POST['cr_acc']))
		$_POST['cr_acc'] = "";
	if (!isset($_POST['cr_lab_acc']))
		$_POST['cr_lab_acc'] = "";
	$id = add_work_order($_POST['wo_ref'], $_POST['StockLocation'], input_num('quantity'),
		$_POST['stock_id'],  $_POST['type'], $_POST['date_'],
		$_POST['RequDate'], $_POST['memo_'], input_num('Costs'), $_POST['cr_acc'], input_num('Labour'), $_POST['cr_lab_acc']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$id&type=".$_POST['type']."&date=".$_POST['date_']);
}

//-------------------------------------------------------------------------------------

if (isset($_POST['UPDATE_ITEM']) && can_process())
{

	update_work_order($selected_id, $_POST['StockLocation'], input_num('quantity'),
		$_POST['stock_id'],  $_POST['date_'], $_POST['RequDate'], $_POST['memo_']);

	meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$selected_id");
}

//--------------------------------------------------------------------------------------

if (isset($_POST['delete']))
{
	//the link to delete a selected record was clicked instead of the submit button

	$cancel_delete = false;

	// can't delete it there are productions or issues
	if (work_order_has_productions($selected_id) ||
		work_order_has_issues($selected_id)	||
		work_order_has_payments($selected_id))
	{
		display_error(_("This work order cannot be deleted because it has already been processed."));
		$cancel_delete = true;
	}

	if ($cancel_delete == false)
	{ //ie not cancelled the delete as a result of above tests

		// delete the actual work order
		delete_work_order($selected_id);
		meta_forward($_SERVER['PHP_SELF'], "DeletedID=$selected_id");
	}
}

//-------------------------------------------------------------------------------------

if (isset($_POST['close']))
{

	// update the closed flag in the work order
	close_work_order($selected_id);
	meta_forward($_SERVER['PHP_SELF'], "ClosedID=$selected_id");
}

//-------------------------------------------------------------------------------------
if (get_post('_type_update')) 
{
  $Ajax->activate('_page_body');
}
//-------------------------------------------------------------------------------------

start_form();

start_table($table_style2);

$existing_comments = "";

$dec = 0;
if (isset($selected_id))
{
	$myrow = get_work_order($selected_id);

	if (strlen($myrow[0]) == 0)
	{
		echo _("The order number sent is not valid.");
		safe_exit();
	}

	// if it's a closed work order can't edit it
	if ($myrow["closed"] == 1)
	{
		echo "<center>";
		display_error(_("This work order is closed and cannot be edited."));
		safe_exit();
	}

	$_POST['wo_ref'] = $myrow["wo_ref"];
	$_POST['stock_id'] = $myrow["stock_id"];
	$_POST['quantity'] = qty_format($myrow["units_reqd"], $_POST['stock_id'], $dec);
	$_POST['StockLocation'] = $myrow["loc_code"];
	$_POST['released'] = $myrow["released"];
	$_POST['closed'] = $myrow["closed"];
	$_POST['type'] = $myrow["type"];
	$_POST['date_'] = sql2date($myrow["date_"]);
	$_POST['RequDate'] = sql2date($myrow["required_by"]);
	$_POST['released_date'] = sql2date($myrow["released_date"]);
	$_POST['memo_'] = "";
	$_POST['units_issued'] = $myrow["units_issued"];
	$_POST['Costs'] = price_format($myrow["additional_costs"]);

	$_POST['memo_'] = get_comments_string(systypes::work_order(), $selected_id);

	hidden('wo_ref', $_POST['wo_ref']);
	hidden('units_issued', $_POST['units_issued']);
	hidden('released', $_POST['released']);
	hidden('released_date', $_POST['released_date']);
	hidden('selected_id',  $selected_id);
	hidden('old_qty', $myrow["units_reqd"]);
	hidden('old_stk_id', $myrow["stock_id"]);

	label_row(_("Reference:"), $_POST['wo_ref']);
	label_row(_("Type:"), wo_types::name($_POST['type']));
	hidden('type', $myrow["type"]);
}
else
{
	$_POST['units_issued'] = $_POST['released'] = 0;
	ref_row(_("Reference:"), 'wo_ref', '', references::get_next(systypes::work_order()));

	wo_types_list_row(_("Type:"), 'type', null);
}

if (get_post('released'))
{
	hidden('stock_id', $_POST['stock_id']);
	hidden('StockLocation', $_POST['StockLocation']);
	hidden('type', $_POST['type']);

	label_row(_("Item:"), $myrow["StockItemName"]);
	label_row(_("Destination Location:"), $myrow["location_name"]);
}
else
{
	stock_manufactured_items_list_row(_("Item:"), 'stock_id', null, false, true);
	if (list_updated('stock_id'))
		$Ajax->activate('quantity');

	locations_list_row(_("Destination Location:"), 'StockLocation', null);
}

if (!isset($_POST['quantity']))
	$_POST['quantity'] = qty_format(1, $_POST['stock_id'], $dec);
else
	$_POST['quantity'] = qty_format($_POST['quantity'], $_POST['stock_id'], $dec);
	

if (get_post('type') == wo_types::advanced())
{
    qty_row(_("Quantity Required:"), 'quantity', null, null, null, $dec);
    if ($_POST['released'])
    	label_row(_("Quantity Manufactured:"), number_format($_POST['units_issued'], get_qty_dec($_POST['stock_id'])));
    date_row(_("Date") . ":", 'date_');
	date_row(_("Date Required By") . ":", 'RequDate', '', null, sys_prefs::default_wo_required_by());
}
else
{
    qty_row(_("Quantity:"), 'quantity', null, null, null, $dec);
    date_row(_("Date") . ":", 'date_');
	hidden('RequDate', '');

	$sql = "SELECT DISTINCT account_code FROM ".TB_PREF."bank_accounts";
	$rs = db_query($sql,"could not get bank accounts");
	$r = db_fetch_row($rs);
	if (!isset($_POST['Labour']))
	{
		$_POST['Labour'] = price_format(0);
		$_POST['cr_lab_acc'] = $r[0];
	}
	amount_row($wo_cost_types[WO_LABOUR], 'Labour');
	gl_all_accounts_list_row(_("Credit Labour Account"), 'cr_lab_acc', null);
	if (!isset($_POST['Costs']))
	{
		$_POST['Costs'] = price_format(0);
		$_POST['cr_acc'] = $r[0];
	}
	amount_row($wo_cost_types[WO_OVERHEAD], 'Costs');
	gl_all_accounts_list_row(_("Credit Overhead Account"), 'cr_acc', null);
	
}

if (get_post('released'))
	label_row(_("Released On:"),$_POST['released_date']);

textarea_row(_("Memo:"), 'memo_', null, 40, 5);

end_table(1);

if (isset($selected_id))
{
	echo "<table align=center><tr>";

	submit_cells('UPDATE_ITEM', _("Update"), '', _('Save changes to work order'), true);
	if (get_post('released'))
	{
		submit_cells('close', _("Close This Work Order"),'','',true);
	}
	submit_cells('delete', _("Delete This Work Order"),'','',true);

	echo "</tr></table>";
}
else
{
	submit_center('ADD_ITEM', _("Add Workorder"), true, '', true);
}

end_form();
end_page();

?>