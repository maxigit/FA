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
$page_security = 2;
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Chart of GL Accounts
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_Chart_of_Accounts();

//----------------------------------------------------------------------------------------------------

function print_Chart_of_Accounts()
{
	global $path_to_root;

	$showbalance = $_POST['PARAM_0'];
	$comments = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$dec = 0;

	$cols = array(0, 50, 300, 425, 500);

	$headers = array(_('Account'), _('Account Name'), _('Account Code'), _('Balance'));
	
	$aligns = array('left',	'left',	'left',	'right');
	
	$params = array(0 => $comments);

	$rep = new FrontReport(_('Chart of Accounts'), "ChartOfAccounts", user_pagesize());
	
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();

	$classname = '';
	$group = '';

	$types = get_account_types_all();

	while ($type=db_fetch($types))
	{
		if (!num_accounts_in_type($type['AccountType'], $type['parent']))
			continue;
		if ($type['AccountTypeName'] != $group)
		{
			if ($classname != '')
				$rep->row -= 4;
			if ($type['AccountClassName'] != $classname)
			{
				$rep->Font('bold');
				$rep->TextCol(0, 4, $type['AccountClassName']);
				$rep->Font();
				//$rep->row -= ($rep->lineHeight + 4);
				$rep->NewLine();
			}
			$group = $type['AccountTypeName'];
			$rep->TextCol(0, 4, $type['AccountTypeName']);
			//$rep->Line($rep->row - 4);
			//$rep->row -= ($rep->lineHeight + 4);
			$rep->NewLine();
		}
		$classname = $type['AccountClassName'];
		
		$accounts = get_gl_accounts_in_type($type['AccountType']);
		while ($account=db_fetch($accounts))
		{
			if ($showbalance == 1)
			{
				$begin = begin_fiscalyear();
				if (is_account_balancesheet($account["account_code"]))
					$begin = "";
				$balance = get_gl_trans_from_to($begin, ToDay(), $account["account_code"], 0);
			}
			$rep->TextCol(0, 1,	$account['account_code']);
			$rep->TextCol(1, 2,	$account['account_name']);
			$rep->TextCol(2, 3,	$account['account_code2']);
			if ($showbalance == 1)	
				$rep->AmountCol(3, 4, $balance, $dec);

			$rep->NewLine();
			if ($rep->row < $rep->bottomMargin + 3 * $rep->lineHeight)
			{
				$rep->Line($rep->row - 2);
				$rep->Header();
			}
		}	
	}
	$rep->Line($rep->row);
	$rep->End();
}

?>