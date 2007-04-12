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

if (isset($_GET['supplier_id']))
{
	$supplier_id = strtoupper($_GET['supplier_id']);
} 
elseif (isset($_POST['supplier_id']))
{
	$supplier_id = strtoupper($_POST['supplier_id']);
}

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}

//--------------------------------------------------------------------------------------------------

if ((isset($_POST['AddRecord']) || isset($_POST['UpdateRecord'])) && isset($supplier_id))
{

   	$input_error = 0;
   	if ($_POST['stock_id'] == "" || !isset($_POST['stock_id']))
   	{
      	$input_error = 1;
      	display_error( _("There is no item selected."));
   	}
   	elseif (!is_numeric($_POST['price']) || $_POST['price']==0)
   	{
      	$input_error = 1;
      	display_error( _("The price entered was not numeric."));
   	}
   	elseif (!is_numeric($_POST['conversion_factor']))
   	{
      	$input_error = 1;
      	display_error( _("The conversion factor entered was not numeric. The conversion factor is the number by which the price must be divided by to get the unit price in our unit of measure."));
   	}

	if ($input_error == 0)
	{
       	if (isset($_POST['AddRecord']))
       	{

    		$sql = "INSERT INTO ".TB_PREF."purch_data (supplier_id, stock_id, price, suppliers_uom,
    			conversion_factor, supplier_description) VALUES (";
    		$sql .= "'$supplier_id', '" . $_POST['stock_id'] . "', " . $_POST['price'] . ", '" . $_POST['suppliers_uom'] . "', " .
    			$_POST['conversion_factor'] . ", '" . $_POST['supplier_description'] . "')";

    		db_query($sql,"The supplier purchasing details could not be added");
    		display_notification(_("This supplier purchasing data has been added."));
       	}

       	if (isset($_POST['UpdateRecord']))
       	{
          	$sql = "UPDATE ".TB_PREF."purch_data SET price=" . $_POST['price'] . ",
				suppliers_uom='" . $_POST['suppliers_uom'] . "',
				conversion_factor=" . $_POST['conversion_factor'] . ",
				supplier_description='" . $_POST['supplier_description'] . "'
				WHERE stock_id='" . $_POST['stock_id'] . "' AND
				supplier_id='$supplier_id'";
          	db_query($sql,"The supplier purchasing details could not be updated");

    	  	display_notification(_("Supplier purchasing data has been updated."));
       	}

       	if (isset($_POST['UpdateRecord']) || isset($_POST['AddRecord']))
       	{
          	//update or insert took place and need to clear the form
          	unset($supplier_id);
          	unset($_POST['price']);
          	unset($_POST['suppliers_uom']);
          	unset($_POST['conversion_factor']);
          	unset($_POST['supplier_description']);
       	}
	}
}

//--------------------------------------------------------------------------------------------------

if (isset($_GET['Delete']))
{

	$sql = "DELETE FROM ".TB_PREF."purch_data WHERE supplier_id='$supplier_id'
		AND stock_id='" . $_POST['stock_id'] . "'";
	db_query($sql,"could not delete purchasing data");

	display_note(_("The purchasing data item has been sucessfully deleted."));
	unset ($supplier_id);
}

//--------------------------------------------------------------------------------------------------

start_form(false, true);

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();

echo "<center>" . _("Item:"). "&nbsp;";
stock_purchasable_items_list('stock_id', $_POST['stock_id'], false, true);

echo "<hr><center>";

set_global_stock_item($_POST['stock_id']);

$mb_flag = get_mb_flag($_POST['stock_id']);

if ($mb_flag == -1) 
{
	display_error(_("Entered item is not defined. Please re-enter."));
} 
else 
{

    $sql = "SELECT ".TB_PREF."purch_data.*,".TB_PREF."suppliers.supp_name,".TB_PREF."suppliers.curr_code
		FROM ".TB_PREF."purch_data INNER JOIN ".TB_PREF."suppliers
		ON ".TB_PREF."purch_data.supplier_id=".TB_PREF."suppliers.supplier_id
		WHERE stock_id = '" . $_POST['stock_id'] . "'";

    $result = db_query($sql, "The supplier purchasing details for the selected part could not be retrieved");

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
            edit_link_cell("stock_id=" . $_POST['stock_id']. "&supplier_id=" . $myrow["supplier_id"] . "&Edit=1");
            delete_link_cell("stock_id=" . $_POST['stock_id']. "&supplier_id=" . $myrow["supplier_id"] . "&Delete=1");
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
}

//------------------------------------------------------------------------------------------------

if (isset($_GET['Edit']))
{

	$sql = "SELECT ".TB_PREF."purch_data.*,".TB_PREF."suppliers.supp_name FROM ".TB_PREF."purch_data
		INNER JOIN ".TB_PREF."suppliers ON ".TB_PREF."purch_data.supplier_id=".TB_PREF."suppliers.supplier_id
		WHERE ".TB_PREF."purch_data.supplier_id='$supplier_id'
		AND ".TB_PREF."purch_data.stock_id='" . $_POST['stock_id'] . "'";

	$result = db_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

	$myrow = db_fetch($result);

    $supp_name = $myrow["supp_name"];
    $_POST['price'] = $myrow["price"];
    $_POST['suppliers_uom'] = $myrow["suppliers_uom"];
    $_POST['supplier_description'] = $myrow["supplier_description"];
    $_POST['conversion_factor'] = $myrow["conversion_factor"];
}

echo "<br>";
start_table($table_style2);

if (isset($_GET['Edit'])) 
{
	hidden('supplier_id', $supplier_id);
	label_row(_("Supplier:"), $supp_name);
} 
else
{
	supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true);
	$supplier_id = $_POST['supplier_id'];
}	
text_row(_("Price:"), 'price', null, 12, 12, "", get_supplier_currency($supplier_id));
text_row(_("Suppliers Unit of Measure:"), 'suppliers_uom', null, 50, 51);

if (!isset($_POST['conversion_factor']) || $_POST['conversion_factor'] == "")
{
   	$_POST['conversion_factor'] = 1;
}
text_row(_("Conversion Factor (to our UOM):"), 'conversion_factor', $_POST['conversion_factor'], 12, 12);
text_row(_("Supplier's Code or Description:"), 'supplier_description', null, 50, 51);

end_table(1);

if (isset($_GET['Edit']))
{
	submit_center('UpdateRecord', _("Update Purchasing Data"));
} 
else 
{
	submit_center('AddRecord', _("Add Purchasing Data"));
}

end_form();
end_page();

?>
