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
// Title:	Stock Check
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");

//----------------------------------------------------------------------------------------------------

print_stock_check();

function getTransactions($category, $location)
{
	$sql = "SELECT ".TB_PREF."stock_master.category_id,
			".TB_PREF."stock_category.description AS cat_description,
			".TB_PREF."stock_master.stock_id,
			".TB_PREF."stock_master.description,
			IF(".TB_PREF."stock_moves.stock_id IS NULL, '', ".TB_PREF."stock_moves.loc_code) AS loc_code,
			SUM(IF(".TB_PREF."stock_moves.stock_id IS NULL,0,".TB_PREF."stock_moves.qty)) AS QtyOnHand
		FROM (".TB_PREF."stock_master,
			".TB_PREF."stock_category)
		LEFT JOIN ".TB_PREF."stock_moves ON
			(".TB_PREF."stock_master.stock_id=".TB_PREF."stock_moves.stock_id OR ".TB_PREF."stock_master.stock_id IS NULL)
		WHERE ".TB_PREF."stock_master.category_id=".TB_PREF."stock_category.category_id
		AND (".TB_PREF."stock_master.mb_flag='B' OR ".TB_PREF."stock_master.mb_flag='M')";
	if ($category != 0)
		$sql .= " AND ".TB_PREF."stock_master.category_id = '$category'";
	if ($location != 'all')
		$sql .= " AND ".TB_PREF."stock_moves.loc_code = '$location'";
	$sql .= " GROUP BY ".TB_PREF."stock_master.category_id,
		".TB_PREF."stock_category.description,
		".TB_PREF."stock_master.stock_id,
		".TB_PREF."stock_master.description
		ORDER BY ".TB_PREF."stock_master.category_id,
		".TB_PREF."stock_master.stock_id";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_stock_check()
{
    global $comp_path, $path_to_root, $pic_height, $pic_width;

    $category = $_POST['PARAM_0'];
    $location = $_POST['PARAM_1'];
    $pictures = $_POST['PARAM_2'];
    $check    = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($category == reserved_words::get_all_numeric())
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);

	if ($location == reserved_words::get_all())
		$location = 'all';
	if ($location == 'all')
		$loc = _('All');
	else
		$loc = $location;

	if ($check)
	{
		$cols = array(0, 100, 250, 305, 375, 445,	515);
		$headers = array(_('Category'), _('Description'), _('Quantity'), _('Check'), _('Demand'), _('Difference'));
		$aligns = array('left',	'left',	'right', 'right', 'right', 'right');
	}
	else
	{
		$cols = array(0, 100, 305, 375, 445,	515);
		$headers = array(_('Category'), _('Description'), _('Quantity'), _('Demand'), _('Difference'));
		$aligns = array('left',	'left',	'right', 'right', 'right');
	}


    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    2 => array('text' => _('Location'), 'from' => $loc, 'to' => ''));

	if ($pictures)
		$user_comp = user_company();
	else
		$user_comp = "";

    $rep = new FrontReport(_('Stock Check Sheets'), "StockCheckSheet", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	$res = getTransactions($category, $location);
	$catt = '';
	while ($trans=db_fetch($res))
	{
		if ($catt != $trans['cat_description'])
		{
			if ($catt != '')
			{
				$rep->Line($rep->row - 2);
				$rep->NewLine(2, 3);
			}
			$rep->TextCol(0, 1, $trans['category_id']);
			$rep->TextCol(1, 2, $trans['cat_description']);
			$catt = $trans['cat_description'];
			$rep->NewLine();
		}
		if ($location == 'all')
			$loc_code = "";
		else
			$loc_code = $trans['loc_code'];
		$demandqty = get_demand_qty($trans['stock_id'], $loc_code);
		$demandqty += get_demand_asm_qty($trans['stock_id'], $loc_code);
		$rep->NewLine();
		$dec = get_qty_dec($trans['stock_id']);
		$rep->TextCol(0, 1, $trans['stock_id']);
		$rep->TextCol(1, 2, $trans['description']);
		$rep->AmountCol(2, 3, $trans['QtyOnHand'], $dec);
		if ($check)
		{
			$rep->TextCol(3, 4, "_________");
			$rep->AmountCol(4, 5, $demandqty, $dec);
			$rep->AmountCol(5, 6, $trans['QtyOnHand'] - $demandqty, $dec);
		}
		else
		{
			$rep->AmountCol(3, 4, $demandqty, $dec);
			$rep->AmountCol(4, 5, $trans['QtyOnHand'] - $demandqty, $dec);
		}
		if ($pictures)
		{
			$image = $comp_path .'/'. $user_comp . '/images/' 
				. item_img_name($trans['stock_id']) . '.jpg';
			if (file_exists($image))
			{
				$rep->NewLine();
				if ($rep->row - $pic_height < $rep->bottomMargin)
					$rep->Header();
				$rep->AddImage($image, $rep->cols[1], $rep->row - $pic_height, 0, $pic_height);
				$rep->row -= $pic_height;
				$rep->NewLine();
			}
		}
	}
	$rep->Line($rep->row - 4);
	$rep->NewLine();
    $rep->End();
}

?>