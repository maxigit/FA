<?php
$page_security = 4;
$path_to_root="..";
include_once($path_to_root . "/includes/session.inc");

page(_("Supplier Purchasing Data"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/manufacturing.inc");
include_once($path_to_root . "/includes/data_checks.inc");

check_db_has_purchasable_items(_("There are no purchasable inventory items defined in the system."));
check_db_has_suppliers(_("There are no suppliers defined in the system."));

//----------------------------------------------------------------------------------------
if ($ret = context_restore()) {
	if(isset($ret['supplier_id']))
		$_POST['supplier_id'] = $ret['supplier_id'];
}
if (isset($_POST['_supplier_id_editor'])) {
	context_call($path_to_root.'/purchasing/manage/suppliers.php?supplier_id='.$_POST['supplier_id'], 
		array( 'supplier_id', 'stock_id','_stock_id_edit', 'price', 
			'suppliers_uom', 'supplier_description','conversion_factor'));
}
simple_page_mode(true);
//--------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM')
{

   	$input_error = 0;
   	if ($_POST['stock_id'] == "" || !isset($_POST['stock_id']))
   	{
      	$input_error = 1;
      	display_error( _("There is no item selected."));
	set_focus('stock_id');
   	}
   	elseif (!check_num('price', 0))
   	{
      	$input_error = 1;
      	display_error( _("The price entered was not numeric."));
	set_focus('price');
   	}
   	elseif (!check_num('conversion_factor'))
   	{
      	$input_error = 1;
      	display_error( _("The conversion factor entered was not numeric. The conversion factor is the number by which the price must be divided by to get the unit price in our unit of measure."));
		set_focus('conversion_factor');
   	}

	if ($input_error == 0)
	{
     	if ($Mode == 'ADD_ITEM') 
       	{

    		$sql = "INSERT INTO ".TB_PREF."purch_data (supplier_id, stock_id, price, suppliers_uom,
    			conversion_factor, supplier_description) VALUES (";
    		$sql .= "'".$_POST['supplier_id']."', '" . $_POST['stock_id'] . "', " .
		    input_num('price') . ", '" . $_POST['suppliers_uom'] . "', " .
    			input_num('conversion_factor') . ", '" . $_POST['supplier_description'] . "')";

    		db_query($sql,"The supplier purchasing details could not be added");
    		display_notification(_("This supplier purchasing data has been added."));
       	} else
       	{
          	$sql = "UPDATE ".TB_PREF."purch_data SET price=" . input_num('price') . ",
				suppliers_uom='" . $_POST['suppliers_uom'] . "',
				conversion_factor=" . input_num('conversion_factor') . ",
				supplier_description='" . $_POST['supplier_description'] . "'
				WHERE stock_id='" . $_POST['stock_id'] . "' AND
				supplier_id='$selected_id'";
          	db_query($sql,"The supplier purchasing details could not be updated");

    	  	display_notification(_("Supplier purchasing data has been updated."));
       	}
		$Mode = 'RESET';
	}
}

//--------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{

	$sql = "DELETE FROM ".TB_PREF."purch_data WHERE supplier_id='$selected_id'
		AND stock_id='" . $_POST['stock_id'] . "'";
	db_query($sql,"could not delete purchasing data");

	display_notification(_("The purchasing data item has been sucessfully deleted."));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
}

if (isset($_POST['_selected_id_update']) )
{
	$selected_id = $_POST['selected_id'];
	$Ajax->activate('_page_body');
}

if (list_updated('stock_id')) 
	$Ajax->activate('price_table');
//--------------------------------------------------------------------------------------------------

start_form(false, true);

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();

echo "<center>" . _("Item:"). "&nbsp;";
stock_purchasable_items_list('stock_id', $_POST['stock_id'], false, true);

echo "<hr></center>";

set_global_stock_item($_POST['stock_id']);

$mb_flag = get_mb_flag($_POST['stock_id']);

if ($mb_flag == -1)
{
	display_error(_("Entered item is not defined. Please re-enter."));
	set_focus('stock_id');
}
else
{

    $sql = "SELECT ".TB_PREF."purch_data.*,".TB_PREF."suppliers.supp_name,".TB_PREF."suppliers.curr_code
		FROM ".TB_PREF."purch_data INNER JOIN ".TB_PREF."suppliers
		ON ".TB_PREF."purch_data.supplier_id=".TB_PREF."suppliers.supplier_id
		WHERE stock_id = '" . $_POST['stock_id'] . "'";

    $result = db_query($sql, "The supplier purchasing details for the selected part could not be retrieved");
  div_start('price_table');
    if (db_num_rows($result) == 0)
    {
    	display_note(_("There is no purchasing data set up for the part selected"));
    }
    else
    {
        start_table("$table_style width=60%");

		$th = array(_("Supplier"), _("Price"), _("Currency"),
			_("Supplier's Unit"), _("Supplier's Description"), "", "");

        table_header($th);

        $k = $j = 0; //row colour counter

        while ($myrow = db_fetch($result))
        {
			alt_table_row_color($k);

            label_cell($myrow["supp_name"]);
            amount_cell($myrow["price"]);
            label_cell($myrow["curr_code"]);
            label_cell($myrow["suppliers_uom"]);
            label_cell($myrow["supplier_description"]);
		 	edit_button_cell("Edit".$myrow['supplier_id'], _("Edit"));
		 	edit_button_cell("Delete".$myrow['supplier_id'], _("Delete"));
            end_row();

            $j++;
            If ($j == 12)
            {
            	$j = 1;
        		table_header($th);
            } //end of page full new headings
        } //end of while loop

        end_table();
    }
 div_end();
}

//-----------------------------------------------------------------------------------------------

if ($Mode =='Edit')
{

	$sql = "SELECT ".TB_PREF."purch_data.*,".TB_PREF."suppliers.supp_name FROM ".TB_PREF."purch_data
		INNER JOIN ".TB_PREF."suppliers ON ".TB_PREF."purch_data.supplier_id=".TB_PREF."suppliers.supplier_id
		WHERE ".TB_PREF."purch_data.supplier_id='$selected_id'
		AND ".TB_PREF."purch_data.stock_id='" . $_POST['stock_id'] . "'";

	$result = db_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

	$myrow = db_fetch($result);

    $supp_name = $myrow["supp_name"];
    $_POST['price'] = price_format($myrow["price"]);
    $_POST['suppliers_uom'] = $myrow["suppliers_uom"];
    $_POST['supplier_description'] = $myrow["supplier_description"];
    $_POST['conversion_factor'] = exrate_format($myrow["conversion_factor"]);
}

echo "<br>";
hidden('selected_id', $selected_id);
start_table($table_style2);

if ($Mode == 'Edit')
{
	hidden('supplier_id');
	label_row(_("Supplier:"), $supp_name);
}
else
{
	supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true);
}
amount_row(_("Price:"), 'price', null,'', get_supplier_currency($selected_id));
text_row(_("Suppliers Unit of Measure:"), 'suppliers_uom', null, 50, 51);

if (!isset($_POST['conversion_factor']) || $_POST['conversion_factor'] == "")
{
   	$_POST['conversion_factor'] = exrate_format(1);
}
amount_row(_("Conversion Factor (to our UOM):"), 'conversion_factor',
  exrate_format($_POST['conversion_factor']), null, null, user_exrate_dec() );
text_row(_("Supplier's Code or Description:"), 'supplier_description', null, 50, 51);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', true);

end_form();
end_page();

?>
