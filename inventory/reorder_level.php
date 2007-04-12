<?php

$page_security = 4;
$path_to_root="..";
include_once($path_to_root . "/includes/session.inc");

page(_("Reorder Levels"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");

check_db_has_costable_items(_("There are no inventory items defined in the system (Purchased or manufactured items)."));

//------------------------------------------------------------------------------------

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}

//------------------------------------------------------------------------------------

start_form(false, true);

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();

echo "<center>" . _("Item:"). "&nbsp;";
stock_costable_items_list('stock_id', $_POST['stock_id'], false, true);

echo "<hr>";

stock_item_heading($_POST['stock_id']);

set_global_stock_item($_POST['stock_id']);

start_table("$table_style width=30%");

$th = array(_("Location"), _("Quantity On Hand"), _("Re-Order Level"));
table_header($th);

$j = 1;
$k=0; //row colour counter

$result = get_loc_details($_POST['stock_id']);

while ($myrow = db_fetch($result)) 
{

	alt_table_row_color($k);

	if (isset($_POST['UpdateData']) && is_numeric($_POST[$myrow["loc_code"]]))
	{

		$myrow["reorder_level"] = $_POST[$myrow["loc_code"]];
		set_reorder_level($_POST['stock_id'], $myrow["loc_code"], $_POST[$myrow["loc_code"]]);
	}

	$qoh = get_qoh_on_date($_POST['stock_id'], $myrow["loc_code"]);

	label_cell($myrow["location_name"]);
	label_cell(number_format2($qoh,user_qty_dec()), "nowrap align='right'");
	text_cells(null, $myrow["loc_code"], $myrow["reorder_level"], 10, 10);
	end_row();
	$j++;
	If ($j == 12)
	{
		$j = 1;
		table_header($th);
	}
}

end_table(1);

submit('UpdateData', _("Update"));

end_form();
end_page();

?>
