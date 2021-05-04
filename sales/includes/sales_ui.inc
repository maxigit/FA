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
include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/db_pager.inc");

//----------------------------------------------------------------------------
// helper functions for script execution control
//
function processing_start()
{
	page_processing(false);
    processing_end();
    $_SESSION['Processing'] = $_SERVER['PHP_SELF'];
}

function processing_end()
{
	page_processing(true);
    unset($_SESSION['Processing']);
    if ( isset($_SESSION['Items']) ) {
		unset($_SESSION['Items']->line_items);
		unset($_SESSION['Items']);
    }
}

function processing_active()
{
    return (isset($_SESSION['Processing']) && $_SESSION['Processing']==$_SERVER['PHP_SELF']);
}
/*
	Check if the cart was not destroyed during opening the edition page in
	another browser tab.
*/
function check_edit_conflicts($cartname='Items')
{
	global $Ajax, $no_check_edit_conflicts;

	if ((!isset($no_check_edit_conflicts) || $no_check_edit_conflicts==0) && get_post('cart_id') && $_POST['cart_id'] != $_SESSION[$cartname]->cart_id) {
		display_error(_('This edit session has been abandoned by opening sales document in another browser tab. You cannot edit more than one sales document at once.'));
		$Ajax->activate('_page_body');
		display_footer_exit();
	}
}

function now_or_never_cell($now_or_never) {
    label_cell($now_or_never ? "<span class='redfg'>Now or never</span>": "Happy to wait");
}
//------------------------------------------------------------------------------------------------
// Delivery Table
function trans_view($trans, $trans_no)
{
	return get_customer_trans_view_str(ST_CUSTDELIVERY, $trans['trans_no']);
}

function batch_checkbox($row, $checked=false)
{
  $checked = $checked ? "checked" : "";
	$name = "Sel_" .$row['trans_no'];
	return $row['Done'] ? '' :
		"<input type='checkbox' name='$name' value='1' $checked>"
// add also trans_no => branch code for checking after 'Batch' submit
	 ."<input name='Sel_[".$row['trans_no']."]' type='hidden' value='"
	 .$row['branch_code'].'-'.$row['ppd'].'-'.$row['ppd_days']."'>\n";
}

function batch_checkbox_checked($row)
{
  return batch_checkbox($row, true);
}

function edit_delivery_link($row)
{
	return $row["Outstanding"]==0 ? '' :
		pager_link(_('Edit'), "/sales/customer_delivery.php?ModifyDelivery="
			.$row['trans_no'], ICON_EDIT);
}

function prt_delivery_link($row)
{
	return print_document_link($row['trans_no'], _("Print"), true, ST_CUSTDELIVERY, ICON_PRINT);
}

function outstanding_invoice_link($row)
{
	return $row["Outstanding"]==0 ? '' :
		pager_link(_('Invoice'), "/sales/customer_invoice.php?DeliveryNumber=" 
			.$row['trans_no'], ICON_DOC);
}

function check_overdue_delivery($row)
{
   	return date1_greater_date2(Today(), sql2date($row["due_date"])) && 
			$row["Outstanding"]!=0;
}

function ppd_percent($row) {
    return number_format2($row['ppd']*100, user_percent_dec())."% (".$row['ppd_days'] .")";
    
}

function deliveries_table($selected_customer, $selected_stock_item=null,$check_batch=false) {
  $sql = get_sql_for_sales_deliveries_view($selected_customer, $selected_stock_item);
  if($check_batch)
     $batch_fun = 'batch_checkbox_checked';
  else 
    $batch_fun = 'batch_checkbox';

  $cols = array(
      _("Delivery #") => array('fun'=>'trans_view'), 
      _("Customer"), 
      'branch_code' => 'skip',
      _("Branch") => array('ord'=>''), 
      _("Contact"),
      _("Reference"), 
      _("Cust Ref"), 
      _("Delivery Date") => array('type'=>'date', 'ord'=>''),
      _("Due By") => 'date', 
      _("Delivery Total") => array('type'=>'amount', 'ord'=>''),
          _("PPD") => array('fun'=>'ppd_percent', 'align'=>'right') ,
          _("days") => 'skip', // array('align'=>'center') ,
      _("Currency") => array('align'=>'center'),
      submit('BatchInvoice',_("Batch"), false, _("Batch Invoicing")) 
        => array('insert'=>true, 'fun'=>$batch_fun, 'align'=>'center'),
      array('insert'=>true, 'fun'=>'edit_delivery_link'),
      array('insert'=>true, 'fun'=>'outstanding_invoice_link'),
      array('insert'=>true, 'fun'=>'prt_delivery_link')
  );
  $table =& new_db_pager('deliveries_tbl', $sql, $cols);
  $table->set_marker('check_overdue_delivery', _("Marked items are overdue."));

  //$table->width = "92%";

  display_db_pager($table);
}
//---------------------------------------------------------------------------------------------
//	Orders inquiry table
//
function check_overdue_order($row)
{
	global $trans_type;
	if ($trans_type == ST_SALESQUOTE)
		return (date1_greater_date2(Today(), sql2date($row['delivery_date'])));
	else
		return ($row['type'] == 0
			&& date1_greater_date2(Today(), sql2date($row['delivery_date']))
			&& ($row['TotDelivered'] < $row['TotQuantity']));
}

function view_order_link($dummy, $order_no)
{
	global $trans_type;
	return  get_customer_trans_view_str($trans_type, $order_no);
}

function prt_link($row)
{
	global $trans_type;
	return print_document_link($row['order_no'], _("Print"), true, $trans_type, ICON_PRINT);
}

function edit_order_link($row) 
{
	if (@$_GET['popup'])
		return '';
	global $trans_type;
	$modify = ($trans_type == ST_SALESORDER ? "ModifyOrderNumber" : "ModifyQuotationNumber");
  return pager_link( _("Edit"),
    "/sales/sales_order_entry.php?$modify=" . $row['order_no'], ICON_EDIT);
}

function dispatch_link($row)
{
	global $trans_type;
	if ($trans_type == ST_SALESORDER)
  		return pager_link( _("Dispatch"),
			"/sales/customer_delivery.php?OrderNumber=" .$row['order_no'], ICON_DOC);
	else		
  		return pager_link( _("Sales Order"),
			"/sales/sales_order_entry.php?OrderNumber=" .$row['order_no'], ICON_DOC);
}

function invoice_link($row)
{
	global $trans_type;
	if ($trans_type == ST_SALESORDER)
  		return pager_link( _("Invoice"),
			"/sales/sales_order_entry.php?NewInvoice=" .$row["order_no"], ICON_DOC);
	else
		return '';
}

function delivery_link($row)
{
  return pager_link( _("Delivery"),
	"/sales/sales_order_entry.php?NewDelivery=" .$row['order_no'], ICON_DOC);
}

function sales_order_link($row)
{
  return pager_link( _("Sales Order"),
	"/sales/sales_order_entry.php?NewQuoteToSalesOrder=" .$row['order_no'], ICON_DOC);
}

function tmpl_checkbox($row)
{
	global $trans_type;
	if ($trans_type == ST_SALESQUOTE)
		return '';
	if (@$_GET['popup'])
		return '';
	$name = "chgtpl" .$row['order_no'];
	$value = $row['type'] ? 1:0;

// save also in hidden field for testing during 'Update'

 return checkbox(null, $name, $value, true,
 	_('Set this order as a template for direct deliveries/invoices'))
	. hidden('last['.$row['order_no'].']', $value, false);
}
function order_inquiry_table($selected_customer, $trans_type_local) {
  global $trans_type;
  $save_trans_type = $trans_type;
  $trans_type = $trans_type_local;
  $sql = get_sql_for_sales_orders_view($selected_customer, $trans_type, $_POST['OrderNumber'], $_POST['order_view_mode'],
    @$selected_stock_item, @$_POST['OrdersAfterDate'], @$_POST['OrdersToDate'], @$_POST['OrderReference'], $_POST['StockLocation'], $_POST['customer_id']);

if ($trans_type == ST_SALESORDER)
	$cols = array(
		_("Order #") => array('fun'=>'view_order_link'),
		_("Ref") => array('type' => 'sorder.reference', 'ord' => '') ,
		_("Customer") => array('type' => 'debtor.name' , 'ord' => 'asc') ,
		_("Branch"), 
		_("Cust Order Ref"),
		_("Order Date") => array('type' =>  'date', 'ord' => ''),
		_("Required By") =>array('type'=>'date', 'ord'=>''),
		_("Delivery To"), 
		_("Order Total") => array('type'=>'amount', 'ord'=>''),
		'Type' => 'skip',
		_("Currency") => array('align'=>'center')
	);
else
	$cols = array(
		_("Quote #") => array('fun'=>'view_order_link'),
		_("Ref"),
		_("Customer"),
		_("Branch"), 
		_("Cust Order Ref"),
		_("Quote Date") => 'date',
		_("Valid until") =>array('type'=>'date', 'ord'=>''),
		_("Delivery To"), 
		_("Quote Total") => array('type'=>'amount', 'ord'=>''),
		'Type' => 'skip',
		_("Currency") => array('align'=>'center')
	);
if ($_POST['order_view_mode'] == 'OutstandingOnly') {
	//array_substitute($cols, 4, 1, _("Cust Order Ref"));
	array_append($cols, array(
		array('insert'=>true, 'fun'=>'dispatch_link'),
		array('insert'=>true, 'fun'=>'edit_order_link')));

} elseif ($_POST['order_view_mode'] == 'InvoiceTemplates') {
	array_substitute($cols, 4, 1, _("Description"));
	array_append($cols, array( array('insert'=>true, 'fun'=>'invoice_link')));

} else if ($_POST['order_view_mode'] == 'DeliveryTemplates') {
	array_substitute($cols, 4, 1, _("Description"));
	array_append($cols, array(
			array('insert'=>true, 'fun'=>'delivery_link'))
	);

} elseif ($trans_type == ST_SALESQUOTE) {
	 array_append($cols,array(
					array('insert'=>true, 'fun'=>'edit_order_link'),
					array('insert'=>true, 'fun'=>'sales_order_link'),
					array('insert'=>true, 'fun'=>'prt_link')));
} elseif ($trans_type == ST_SALESORDER) {
	 array_append($cols,array(
			_("Tmpl") => array('insert'=>true, 'fun'=>'tmpl_checkbox'),
					array('insert'=>true, 'fun'=>'edit_order_link'),
					array('insert'=>true, 'fun'=>'prt_link')));
};


$table =& new_db_pager('orders_tbl', $sql, $cols);
$table->set_marker('check_overdue_order', _("Marked items are overdue."));

$table->width = "80%";

display_db_pager($table);
$trans_type = $save_trans_type;
}
?>
