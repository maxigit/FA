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
$page_security = 9;
$path_to_root="../..";
include_once($path_to_root . "/includes/session.inc");

page(_("Currencies"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");

simple_page_mode(false);

//---------------------------------------------------------------------------------------------

function check_data()
{
	if (strlen($_POST['Abbreviation']) == 0) 
	{
		display_error( _("The currency abbreviation must be entered."));
		set_focus('Abbreviation');
		return false;
	} 
	elseif (strlen($_POST['CurrencyName']) == 0) 
	{
		display_error( _("The currency name must be entered."));
		set_focus('CurrencyName');
		return false;		
	} 
	elseif (strlen($_POST['Symbol']) == 0) 
	{
		display_error( _("The currency symbol must be entered."));
		set_focus('Symbol');
		return false;		
	} 
	elseif (strlen($_POST['hundreds_name']) == 0) 
	{
		display_error( _("The hundredths name must be entered."));
		set_focus('hundreds_name');
		return false;		
	}  	
	
	return true;
}

//---------------------------------------------------------------------------------------------

function handle_submit()
{
	global $selected_id, $Mode;
	
	if (!check_data())
		return false;
		
	if ($selected_id != "") 
	{

		update_currency($_POST['Abbreviation'], $_POST['Symbol'], $_POST['CurrencyName'], 
			$_POST['country'], $_POST['hundreds_name']);
		display_notification(_('Selected currency settings has been updated'));
	} 
	else 
	{

		add_currency($_POST['Abbreviation'], $_POST['Symbol'], $_POST['CurrencyName'], 
			$_POST['country'], $_POST['hundreds_name']);
		display_notification(_('New currency has been added'));
	}	
	$Mode = 'RESET';
}

//---------------------------------------------------------------------------------------------

function check_can_delete()
{
	global $selected_id;
		
	if ($selected_id == "")
		return false;
	// PREVENT DELETES IF DEPENDENT RECORDS IN debtors_master
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."debtors_master WHERE curr_code = '$selected_id'";
	$result = db_query($sql);
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this currency, because customer accounts have been created referring to this currency."));
		return false;
	}

	$sql= "SELECT COUNT(*) FROM ".TB_PREF."suppliers WHERE curr_code = '$selected_id'";
	$result = db_query($sql);
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this currency, because supplier accounts have been created referring to this currency."));
		return false;
	}
		
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."company WHERE curr_default = '$selected_id'";
	$result = db_query($sql);
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this currency, because the company preferences uses this currency."));
		return false;
	}
	
	// see if there are any bank accounts that use this currency
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."bank_accounts WHERE bank_curr_code = '$selected_id'";
	$result = db_query($sql);
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this currency, because thre are bank accounts that use this currency."));
		return false;
	}
	
	return true;
}

//---------------------------------------------------------------------------------------------

function handle_delete()
{
	global $selected_id, $Mode;
	if (check_can_delete()) {
	//only delete if used in neither customer or supplier, comp prefs, bank trans accounts
		delete_currency($selected_id);
		display_notification(_('Selected currency has been deleted'));
	}
	$Mode = 'RESET';
}

//---------------------------------------------------------------------------------------------

function display_currencies()
{
	global $table_style;

	$company_currency = get_company_currency();	
	
    $result = get_currencies();
	start_form();    
    start_table($table_style);
    $th = array(_("Abbreviation"), _("Symbol"), _("Currency Name"),
    	_("Hundredths name"), _("Country"), "", "");
    table_header($th);	
    
    $k = 0; //row colour counter
    
    while ($myrow = db_fetch($result)) 
    {
    	
    	if ($myrow[1] == $company_currency) 
    	{
    		start_row("class='currencybg'");
    	} 
    	else
    		alt_table_row_color($k);
    		
    	label_cell($myrow["curr_abrev"]);
		label_cell($myrow["curr_symbol"]);
		label_cell($myrow["currency"]);
		label_cell($myrow["hundreds_name"]);
		label_cell($myrow["country"]);
 		edit_button_cell("Edit".$myrow["curr_abrev"], _("Edit"));
		if ($myrow["curr_abrev"] != $company_currency)
 			delete_button_cell("Delete".$myrow["curr_abrev"], _("Delete"));
		else
			label_cell('');
		end_row();
		
    } //END WHILE LIST LOOP
    
    end_table();
	end_form();    
    display_note(_("The marked currency is the home currency which cannot be deleted."), 0, 0, "class='currentfg'");
}

//---------------------------------------------------------------------------------------------

function display_currency_edit($selected_id)
{
	global $table_style2, $Mode;
	
	start_form();
	start_table($table_style2);

	if ($selected_id != '') 
	{
		if ($Mode == 'Edit') {
			//editing an existing currency
			$myrow = get_currency($selected_id);

			$_POST['Abbreviation'] = $myrow["curr_abrev"];
			$_POST['Symbol'] = $myrow["curr_symbol"];
			$_POST['CurrencyName']  = $myrow["currency"];
			$_POST['country']  = $myrow["country"];
			$_POST['hundreds_name']  = $myrow["hundreds_name"];
		}
		hidden('Abbreviation');
		hidden('selected_id', $selected_id);
		label_row(_("Currency Abbreviation:"), $_POST['Abbreviation']);
	} 
	else 
	{ 
		text_row_ex(_("Currency Abbreviation:"), 'Abbreviation', 4, 3);		
	}

	text_row_ex(_("Currency Symbol:"), 'Symbol', 10);
	text_row_ex(_("Currency Name:"), 'CurrencyName', 20);
	text_row_ex(_("Hundredths Name:"), 'hundreds_name', 15);	
	text_row_ex(_("Country:"), 'country', 40);	

	end_table(1);

	submit_add_or_update_center($selected_id == '', '', true);

	end_form();
}

//---------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
	handle_submit();

//--------------------------------------------------------------------------------------------- 

if ($Mode == 'Delete')
	handle_delete();

//---------------------------------------------------------------------------------------------
if ($Mode == 'RESET')
{
 		$selected_id = '';
		$_POST['Abbreviation'] = $_POST['Symbol'] = '';
		$_POST['CurrencyName'] = $_POST['country']  = '';
		$_POST['hundreds_name']  = '';
}

display_currencies();

display_currency_edit($selected_id);

//---------------------------------------------------------------------------------------------

end_page();

?>
