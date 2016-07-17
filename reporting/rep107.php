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
$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Print Invoices
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

print_invoices();

//----------------------------------------------------------------------------------------------------
$print_as_quote = 0;

function print_invoices()
{
	global $path_to_root, $alternative_tax_include_on_docs, $suppress_tax_rates, $no_zero_lines_amount, $print_as_quote;
	
	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$pay_service = $_POST['PARAM_4'];
	$print_as_quote = $_POST['PARAM_5'];
	$comments = $_POST['PARAM_6'];
	$customer = $_POST['PARAM_7'];
	$orientation = $_POST['PARAM_8'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

 	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	$cols = array(4, 60, 225, 300, 325, 385, 450, 515);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'right', 'left', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize(), 9, $orientation);
	if ($orientation == 'L')
		recalculate_cols($cols);
	for ($i = $from; $i <= $to; $i++)
	{
			if (!exists_customer_trans(ST_SALESINVOICE, $i))
				continue;
			$sign = 1;
			$myrow = get_customer_trans($i, ST_SALESINVOICE);

			if($customer && $myrow['debtor_no'] != $customer) {
				continue;
			}
			$baccount = get_default_bank_account($myrow['curr_code']);
			$params['bankaccount'] = $baccount['id'];

			$branch = get_branch($myrow["branch_code"]);
			$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
				$rep->title = _('INVOICE');
				$rep->filename = "Invoice" . $myrow['reference'] . ".pdf";
				$rep->Info($params, $cols, null, $aligns);
			}
			else
				$rep->title = _('INVOICE');
			$rep->filename = strtr("MAE-IN-" . $myrow['DebtorName'] ."-" . $i . ".pdf", " ", "_");
			$rep->SetHeaderType('Header2');
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);

			$contacts = get_branch_contacts($branch['branch_code'], 'invoice', $branch['debtor_no'], true);
			$baccount['payment_service'] = $pay_service;
			$rep->SetCommonData($myrow, $branch, $sales_order, $baccount, ST_SALESINVOICE, $contacts);
			$rep->NewPage();
   			$result = get_customer_trans_details(ST_SALESINVOICE, $i);
			$SubTotal = 0;
			while ($myrow2=db_fetch($result))
			{
				if ($myrow2["quantity"] == 0)
					continue;

				$Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   user_price_dec());
				$SubTotal += $Net;
	    		$DisplayPrice = number_format2($myrow2["unit_price"]*(1-$myrow2["discount_percent"]),$dec);
	    		$DisplayQty = number_format2($sign*$myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		$DisplayNet = number_format2($Net,$dec);
				$rep->TextCol(0, 1,	$myrow2['stock_id'], -2);
				$oldrow = $rep->row;
				$rep->TextColLines(1, 2, $myrow2['StockDescription'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !isset($no_zero_lines_amount) || $no_zero_lines_amount == 0)
				{
					$rep->TextCol(2, 3,	$DisplayQty, -2);

					$rep->TextCol(3, 4,	$myrow2['units'], -2);
					$rep->TextCol(4, 5,	$DisplayPrice, -2);
                    if ($myrow2["ppd"]==0) {
                        $rep->Font("italic");
                        $rep->TextCol(5, 6,	"NA     ", -2);
                        $rep->Font("normal");
                    }
                        else {
                            $DisplayDiscount = number_format2($DisplayPrice*(1-$myrow2["ppd"]),user_price_dec(true));
                            $rep->TextCol(5, 6,	$DisplayDiscount, -2);
                            
                        }
					$rep->TextCol(6, 7,	$DisplayNet, -2);
				}	
				$rep->row = $newrow;
				//$rep->NewLine(1);
				if ($rep->row < $rep->bottomMargin + (12 * $rep->lineHeight)) {
					$rep->NewPage();
                    
                }
			}

			$memo = get_comments_string(ST_SALESINVOICE, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(1, 5, $memo, -2);
			}

   			$DisplaySubTot = number_format2($SubTotal,$dec);
   			$DisplayFreight = number_format2($sign*$myrow["ov_freight"],$dec);

    		$rep->row = $rep->bottomMargin + (12 * $rep->lineHeight);
			$doctype = ST_SALESINVOICE;

			$rep->TextCol(3, 6, _("Sub-total"), -2);
			$rep->TextCol(6, 7,	$DisplaySubTot, -2);
			$rep->NewLine();
			$rep->TextCol(3, 6, _("Shipping"), -2);
			$rep->TextCol(6, 7,	$DisplayFreight, -2);
			$rep->NewLine();
			$tax_items = get_trans_tax_details(ST_SALESINVOICE, $i);
			$first = true;
    		while ($tax_item = db_fetch($tax_items))
    		{
    			if ($tax_item['amount'] == 0)
    				continue;
    			$DisplayTax = number_format2($sign*$tax_item['amount'], $dec);
    			
    			if (isset($suppress_tax_rates) && $suppress_tax_rates == 1)
    				$tax_type_name = $tax_item['tax_type_name'];
    			else
    				$tax_type_name = $tax_item['tax_type_name']." (".$tax_item['rate']."%) ";

    			if ($tax_item['included_in_price'])
    			{
    				if (isset($alternative_tax_include_on_docs) && $alternative_tax_include_on_docs == 1)
    				{
    					if ($first)
    					{
							$rep->TextCol(3, 6, _("Total Tax Excluded"), -2);
							$rep->TextCol(6, 7,	number_format2($sign*$tax_item['net_amount'], $dec), -2);
							$rep->NewLine();
    					}
						$rep->TextCol(3, 6, $tax_type_name, -2);
						$rep->TextCol(6, 7,	$DisplayTax, -2);
						$first = false;
    				}
    				else
						$rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
				}
    			else
    			{
					$rep->TextCol(3, 6, $tax_type_name, -2);
					$rep->TextCol(6, 7,	$DisplayTax, -2);
				}
				$rep->NewLine();
    		}

    		$rep->NewLine();
			$total = $myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"]+$myrow["ov_freight_tax"];
            $tax = $myrow["ov_gst"] + $myrow["ov_freight_tax"];
            $net = $total - $tax;
            $ppd = $myrow["ov_ppd_amount"];
            $ppd_gst = $myrow["ov_ppd_gst"];
			$DisplayTotal = number_format2($sign*$total,$dec);
            $DisplayTotalPPD = number_format2($total - $myrow["ov_ppd_amount"] - $myrow["ov_ppd_gst"], $dec);
            $ppd_percent= number_format2(($myrow["ppd"] ?: 0)*100,user_percent_dec());
            $ppd_days = $myrow["ppd_days"] ?: 1; // can be null
            $ppd_days_s = $ppd_days  > 1 ? "s" : "";
			$rep->Font('bold');
			$rep->TextCol(3, 6, _("TOTAL INVOICE"), - 2);
			$rep->TextCol(6, 7, $DisplayTotal, -2);
            // Add PPD text if needed
            if($ppd+$ppd_gst <> 0) {
                $rep->Font('italic');
                $rep->SetTextColor(0,0,255);
                $rep->NewLine(2);
                $rep->TextCol(0,6, _("(*) A Prompt Payment Discount (PPD) of $ppd_percent % applies on selected items if payment made within $ppd_days day$ppd_days_s."));

                $rep->NewLine(1);
                $rep->TextCol(0,2, _("Net Disc. : ") . number_format($ppd,$dec). _("  -  Net : ") . number_format($net-$ppd, $dec). "  -  VAT : " . number_format($tax-$ppd_gst, $dec). "  -");
                $rep->TextCol(2,6, _("Amount due if payment made before the ").add_days(sql2date($myrow['tran_date']),10)." ");
                $rep->TextCol(6,7, number_format($DisplayTotalPPD, $dec),-2);

                $rep->NewLine(1);
                  $rep->TextCol(0,6, _("No credit note will be issued. Following payment you must ensure you have only recovered the VAT actually paid."));
            }
            else {
                $rep->Font('italic');
                $rep->SetTextColor(0,0,255);
                $rep->NewLine(2);
                $rep->TextCol(0,6, _("(*) This invoice is not eligible for Prompt Payment Discount (PPD)."));
            }
            $rep->SetTextColor(0,0,0);
			$words = price_in_words($myrow['Total'], ST_SALESINVOICE);
			if ($words != "")
			{
				$rep->NewLine(1);
				$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
			}
			$rep->Font();
			if ($email == 1)
			{
				if($print_as_quote)  {
                    $ppd_body = "";
                    if($DisplayTotalPPD != $DisplayTotal)  {
                        $ppd_body =  <<<EOT
However, if you pay within $ppd_days day$ppd_days_s, you are entitled to a prompt payment discount of $ppd_percent %
on selected items and therefore only need to pay $DisplayTotalPPD $cur.

EOT;
                    }
					$rep->email_body =  <<<EOT

Hi [contact],

Your order is picked, packed and ready to be dispatched.
The total amount due is $DisplayTotal $cur (including delivery).
$ppd_body                                                                                       
Could you please arrange the payment ASAP and we will send your order upon receipt.


Payments can be made by bank transfer to our bank , details are :

Max & Ellie Ltd
sort code : 40-25-59
account number : 40093653
IBAN : GB18MIDL40255940093653
HSBC

or alternatively by cheque payable to Max & Ellie Ltd and post to the address below.

Max & Ellie Ltd
Max & Ellie Warehouse, Walcott Street
Hull
East riding of Yorkshire
HU3 4AU

International Payment: Please note that we are not liable for any bank charges. Make sure 
all payment costs are covered from your side.
EOT;
				}
				$rep->End($email);
			}
	}
	if ($email == 0)
		$rep->End();
}

?>
