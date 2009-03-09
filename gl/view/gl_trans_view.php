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
$page_security = 8;
$path_to_root="../..";
include_once($path_to_root . "/includes/session.inc");

page(_("General Ledger Transaction Details"), true);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

if (!isset($_GET['type_id']) || !isset($_GET['trans_no'])) 
{ /*Script was not passed the correct parameters */

	echo "<p>" . _("The script must be called with a valid transaction type and transaction number to review the general ledger postings for.") . "</p>";
	exit;
}

function display_gl_heading($myrow)
{
	global $table_style;
	$trans_name = systypes::name($_GET['type_id']);
    start_table("$table_style width=95%");
    $th = array(_("General Ledger Transaction Details"),
    	_("Date"), _("Person/Item"));
    table_header($th);	
    start_row();	
    label_cell("$trans_name #" . $_GET['trans_no']);
	label_cell(sql2date($myrow["tran_date"]));
	label_cell(payment_person_types::person_name($myrow["person_type_id"],$myrow["person_id"]));
	
	end_row();

	comments_display_row($_GET['type_id'], $_GET['trans_no']);

    end_table(1);
}

$sql = "SELECT ".TB_PREF."gl_trans.*, account_name FROM ".TB_PREF."gl_trans, ".TB_PREF."chart_master WHERE ".TB_PREF."gl_trans.account = ".TB_PREF."chart_master.account_code AND type= " . $_GET['type_id'] . " AND type_no = " . $_GET['trans_no'] . " ORDER BY counter";
$result = db_query($sql,"could not get transactions");
//alert("sql = ".$sql);

if (db_num_rows($result) == 0)
{
    echo "<p><center>" . _("No general ledger transactions have been created for") . " " .systypes::name($_GET['type_id'])." " . _("number") . " " . $_GET['trans_no'] . "</center></p><br><br>";
	end_page(true);
	exit;
}

/*show a table of the transactions returned by the sql */
$dim = get_company_pref('use_dimension');

if ($dim == 2)
	$th = array(_("Account Code"), _("Account Name"), _("Dimension")." 1", _("Dimension")." 2",
		_("Debit"), _("Credit"), _("Memo"));
else if ($dim == 1)
	$th = array(_("Account Code"), _("Account Name"), _("Dimension"),
		_("Debit"), _("Credit"), _("Memo"));
else		
	$th = array(_("Account Code"), _("Account Name"),
		_("Debit"), _("Credit"), _("Memo"));
$k = 0; //row colour counter
$heading_shown = false;

while ($myrow = db_fetch($result)) 
{
	if ($myrow['amount'] == 0) continue;
	if (!$heading_shown)
	{
		display_gl_heading($myrow);
		start_table("$table_style width=95%");
		table_header($th);
		$heading_shown = true;
	}	

	alt_table_row_color($k);
	
    label_cell($myrow['account']);
	label_cell($myrow['account_name']);
	if ($dim >= 1)
		label_cell(get_dimension_string($myrow['dimension_id'], true));
	if ($dim > 1)
		label_cell(get_dimension_string($myrow['dimension2_id'], true));

	display_debit_or_credit_cells($myrow['amount']);
	label_cell($myrow['memo_']);
	end_row();

}
//end of while loop
if ($heading_shown)
	end_table(1);

is_voided_display($_GET['type_id'], $_GET['trans_no'], _("This transaction has been voided."));

end_page(true);

?>
