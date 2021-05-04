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
  display_warning("SQL: $sql");
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
?>
