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
// Author: Joe Hunt, 09/13/2010


include_once('xpMenu.class.php');

	class renderer
	{
		function wa_header()
		{
			page(_($help_context = "Main Menu"), false, true);
		}

		function wa_footer()
		{
			end_page(false, true);
		}
		function shortcut($url, $label) 
		{
			echo "<li>";
			echo menu_link($url, $label);
			echo "</li>";
		}
		function menu_header($title, $no_menu, $is_index)
		{
			global $path_to_root, $help_base_url, $power_by, $version, $db_connections, $installed_extensions;

			$sel_app = $_SESSION['sel_app'];
			echo "<div class='fa-main'>\n";
			if (!$no_menu)
			{
				$applications = $_SESSION['App']->applications;
				$local_path_to_root = $path_to_root;
				$img = "<img src='$local_path_to_root/themes/exclusive/images/login.gif' width='14' height='14' border='0' alt='"._('Logout')."'>&nbsp;&nbsp;";
				$himg = "<img src='$local_path_to_root/themes/exclusive/images/help.gif' width='14' height='14' border='0' alt='"._('Help')."'>&nbsp;&nbsp;";
				echo "<div id='header'>\n";
				echo "<ul>\n";
				echo "  <li><a href='$path_to_root/admin/display_prefs.php?'>" . _("Preferences") . "</a></li>\n";
				echo "  <li><a href='$path_to_root/admin/change_current_user_password.php?selected_id=" . $_SESSION["wa_current_user"]->username . "'>" . _("Change password") . "</a></li>\n";
			 	if ($help_base_url != null)
					echo "  <li><a target = '_blank' onclick=" .'"'."javascript:openWindow(this.href,this.target); return false;".'" '. "href='". 
						help_url()."'>$himg" . _("Help") . "</a></li>";
				echo "  <li><a href='$path_to_root/access/logout.php?'>$img" . _("Logout") . "</a></li>";
				echo "</ul>\n";
				$indicator = "$path_to_root/themes/".user_theme(). "/images/ajax-loader.gif";
				echo "<h1>$power_by $version<span style='padding-left:300px;'><img id='ajaxmark' src='$indicator' align='center' style='visibility:hidden;'></span></h1>\n";
				echo "</div>\n"; // header
				echo "<div class='fa-menu'>";
				echo "<ul>\n";
				foreach($applications as $app)
				{
					if ($sel_app == $app->id)
						$sel_application = $app;
					$acc = access_string($app->name);
					echo "<li ".($sel_app == $app->id ? "class='active' " : "") . "><a href='$local_path_to_root/index.php?application=" . $app->id
						."'$acc[1]><b>" . $acc[0] . "</b></a></li>\n";
				}
				echo "</ul>\n"; 
				echo "</div>\n"; // menu
				echo "<div class='clear'></div>\n";
			}				
			echo "<div class='fa-body'>\n";
			if (!$no_menu)
			{		
				add_access_extensions();
				echo "<div class='fa-side'>\n";
				echo "<div class='fa-submenu'>\n";
				$app = $sel_application;
				$xpmenu = new xpMenu();
				$xpmenu->addMenu($sel_app);
				$acc = access_string($app->name);
				$imgs = array('orders'=>'invoice.gif', 'AP'=>'receive.gif', 'stock'=>'basket.png',
				 	'manuf'=>'cog.png', 'proj'=>'time.png', 'GL'=>'gl.png', 'system'=>'controller.png');
				if (!isset($imgs[$app->id]))
					$imgs[$app->id] = "controller.png";
				$xpmenu->addCategory($app->id, $acc[0], "$local_path_to_root/themes/exclusive/images/".$imgs[$app->id], $app->id);
				$i = $j = 0;
				if ($sel_app == "system")
					$imgs2 = array("page_edit.png", "page_edit.png", "page_edit.png", "page_edit.png", "folder.gif");
				else	
					$imgs2 = array("folder.gif", "report.png", "page_edit.png", "money.png", "folder.gif");
				foreach ($app->modules as $module)
				{
					$xpmenu->addOption($i++, $module->name, "$local_path_to_root/themes/exclusive/images/transparent.gif", "", $app->id, $sel_app);
					$apps = array();
					foreach ($module->lappfunctions as $appfunction)
						$apps[] = $appfunction;
					foreach ($module->rappfunctions as $appfunction)
						$apps[] = $appfunction;
					$application = array();	
					foreach ($apps as $application)	
					{
						$lnk = access_string($application->label);
						if ($_SESSION["wa_current_user"]->can_access_page($application->access))
						{
							if ($application->label != "")
							{
								$xpmenu->addOption($i++, $lnk[0], "$local_path_to_root/themes/exclusive/images/".$imgs2[$j], "$path_to_root/$application->link", $app->id, $sel_app);
							}
						}
						else	
							$xpmenu->addOption($i++, $lnk[0], "$local_path_to_root/themes/exclusive/images/".$imgs2[$j], "#", $app->id, $sel_app);
					}
					$j++;	
				}
				$txt = $xpmenu->javaScript();
				$txt .= $xpmenu->javaScript();
				$txt .= $xpmenu->style();
				$txt .= $xpmenu->mountMenu($sel_app, $sel_app);
				echo $txt;
				echo "</div>\n"; // submenu
				echo "<div class='clear'></div>\n";
				echo "</div>\n"; // fa-side
				echo "<div class='fa-content'>\n";
			}
			if ($no_menu)
				echo "<br>";
			elseif ($title && !$no_menu && !$is_index)
			{
				echo "<center><table id='title'><tr><td width='100%' class='titletext'>$title</td>"
				."<td align=right>"
				.(user_hints() ? "<span id='hints'></span>" : '')
				."</td>"
				."</tr></table></center>";
			}
		}

		function menu_footer($no_menu, $is_index)
		{
			global $path_to_root, $power_url, $power_by, $version, $db_connections;
			include_once($path_to_root . "/includes/date_functions.inc");

			if (!$no_menu)
				echo "</div>\n"; // fa-content
			echo "</div>\n"; // fa-body
			if (!$no_menu)
			{
				echo "<div class='fa-footer'>\n";
				if (isset($_SESSION['wa_current_user']))
				{
					echo "<span class='power'><a target='_blank' href='$power_url'>$power_by $version</a></span>\n";
					echo "<span class='date'>".Today() . "&nbsp;" . Now()."</span>\n";
					echo "<span class='date'>" . $db_connections[$_SESSION["wa_current_user"]->company]["name"] . "</span>\n";
					echo "<span class='date'>" . $_SERVER['SERVER_NAME'] . "</span>\n";
					echo "<span class='date'>" . $_SESSION["wa_current_user"]->name . "</span>\n";
					echo "<span class='date'>" . _("Theme:") . " " . user_theme() . "</span>\n";
					echo "<span class='date'>".show_users_online()."</span>\n";
				}
				echo "</div>\n"; // footer
			}
			echo "</div>\n"; // fa-main
		}

		function display_applications(&$waapp)
		{
			global $path_to_root, $use_popup_windows;
			include_once("$path_to_root/includes/ui.inc");
			include_once($path_to_root . "/reporting/includes/class.graphic.inc");
			include($path_to_root . "/includes/system_tests.inc");

			if ($use_popup_windows)
			{
				echo "<script language='javascript'>\n";
				echo get_js_open_window(900, 500);
				echo "</script>\n"; 
			}
			$selected_app = $waapp->get_selected_application();
			// first have a look through the directory, 
			// and remove old temporary pdfs and pngs
			$dir = company_path(). '/pdf_files';
	
			if ($d = @opendir($dir)) {
				while (($file = readdir($d)) !== false) {
					if (!is_file($dir.'/'.$file) || $file == 'index.php') continue;
				// then check to see if this one is too old
					$ftime = filemtime($dir.'/'.$file);
				 // seems 3 min is enough for any report download, isn't it?
					if (time()-$ftime > 180){
						unlink($dir.'/'.$file);
					}
				}
				closedir($d);
			}

			if ($selected_app->id == "orders")
      {
				display_customer_topten();
       display_to_sell_topten();
      }
			elseif ($selected_app->id == "AP")
				display_supplier_topten();
			elseif ($selected_app->id == "stock")
      {
        display_sales_graph();
        display_stock_graph();
				display_stock_uc_topten();
				display_color_topten();
				display_stock_topten();
      }
			elseif ($selected_app->id == "manuf")
      {
				display_stock_topten(true);

      }
			elseif ($selected_app->id == "proj")
      {
				display_dimension_topten();
				display_dimension_topten(2);

      }
      elseif ($selected_app->id == "GL")
      {
        $total = display_bank_info();
        display_cash_flow($total);
        display_loan_info($total,true);
				display_gl_info();
      }
			else	
			{
				$title = "Display System Diagnostics";
				br(2);
				display_heading($title);
				br();
				display_system_tests();
			}	
		}
	}
	
	function display_customer_topten()
	{
		global $path_to_root;
		
		$pg = new graph();

		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		$sql = "SELECT SUM((ov_amount + ov_discount) * rate) AS total,d.debtor_no, d.name FROM
			".TB_PREF."debtor_trans AS trans, ".TB_PREF."debtors_master AS d WHERE trans.debtor_no=d.debtor_no
			AND (trans.type = ".ST_SALESINVOICE." OR trans.type = ".ST_CUSTCREDIT.")
			AND tran_date >= '$begin1' AND tran_date <= '$today1' GROUP by d.debtor_no ORDER BY total DESC, d.debtor_no 
			LIMIT 10";
		$result = db_query($sql);
		$title = _("Top 10 customers in fiscal year");
		br(2);
		display_heading($title);
		br();
		$th = array(_("Customer"), _("Amount"));
		start_table(TABLESTYLE, "width=30%");
		table_header($th);
		$k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	$name = $myrow["debtor_no"]." ".$myrow["name"];
    		label_cell($name);
		    amount_cell($myrow['total']);
		    $pg->x[$i] = $name; 
		    $pg->y[$i] = $myrow['total'];
		    $i++;
			end_row();
		}
		end_table(2);
		$pg->title     = $title;
		$pg->axis_x    = _("Customer");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $today;
		$pg->type      = 2;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
		
		$today = date2sql(Today());
		$sql = "SELECT trans.trans_no, trans.reference,	trans.tran_date, trans.due_date, debtor.debtor_no, 
			debtor.name, branch.br_name, debtor.curr_code,
			(trans.ov_amount + trans.ov_gst + trans.ov_freight 
				+ trans.ov_freight_tax + trans.ov_discount)	AS total,  
			(trans.ov_amount + trans.ov_gst + trans.ov_freight 
				+ trans.ov_freight_tax + trans.ov_discount - trans.alloc) AS remainder,
			DATEDIFF('$today', trans.due_date) AS days 	
			FROM ".TB_PREF."debtor_trans as trans, ".TB_PREF."debtors_master as debtor, 
				".TB_PREF."cust_branch as branch
			WHERE debtor.debtor_no = trans.debtor_no AND trans.branch_code = branch.branch_code
				AND trans.type = ".ST_SALESINVOICE." AND (trans.ov_amount + trans.ov_gst + trans.ov_freight 
				+ trans.ov_freight_tax + trans.ov_discount - trans.alloc) > 1e-6
				AND DATEDIFF('$today', trans.due_date) > 0 ORDER BY days DESC";
		$result = db_query($sql);
		$title = db_num_rows($result) . _(" overdue Sales Invoices");
		br(1);
		display_heading($title);
		br();
		$th = array("#", _("Ref."), _("Date"), _("Due Date"), _("Customer"), _("Branch"), _("Currency"), 
			_("Total"), _("Remainder"),	_("Days"));
		start_table(TABLESTYLE);
		table_header($th);
		$k = 0; //row colour counter
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
			label_cell(get_trans_view_str(ST_SALESINVOICE, $myrow["trans_no"]));
			label_cell($myrow['reference']);
			label_cell(sql2date($myrow['tran_date']));
			label_cell(sql2date($myrow['due_date']));
	    	$name = $myrow["debtor_no"]." ".$myrow["name"];
    		label_cell($name);
    		label_cell($myrow['br_name']);
    		label_cell($myrow['curr_code']);
		    amount_cell($myrow['total']);
		    amount_cell($myrow['remainder']);
		    label_cell($myrow['days'], "align='right'");
			end_row();
		}
		end_table(2);
	}
	
	function display_supplier_topten()
	{
		global $path_to_root;
		
		$pg = new graph();

		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		$sql = "SELECT SUM((trans.ov_amount + trans.ov_discount) * rate) AS total, s.supplier_id, s.supp_name FROM
			".TB_PREF."supp_trans AS trans, ".TB_PREF."suppliers AS s WHERE trans.supplier_id=s.supplier_id
			AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.")
			AND tran_date >= '$begin1' AND tran_date <= '$today1' GROUP by s.supplier_id ORDER BY total DESC, s.supplier_id 
			LIMIT 10";
		$result = db_query($sql);
		$title = _("Top 10 suppliers in fiscal year");
		br(2);
		display_heading($title);
		br();
		$th = array(_("Supplier"), _("Amount"));
		start_table(TABLESTYLE, "width=30%");
		table_header($th);
		$k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	$name = $myrow["supplier_id"]." ".$myrow["supp_name"];
    		label_cell($name);
		    amount_cell($myrow['total']);
		    $pg->x[$i] = $name; 
		    $pg->y[$i] = $myrow['total'];
		    $i++;
			end_row();
		}
		end_table(2);
		$pg->title     = $title;
		$pg->axis_x    = _("Supplier");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $today;
		$pg->type      = 2;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
		
		$today = date2sql(Today());
		$sql = "SELECT trans.trans_no, trans.reference, trans.tran_date, trans.due_date, s.supplier_id, 
			s.supp_name, s.curr_code,
			(trans.ov_amount + trans.ov_gst + trans.ov_discount) AS total,  
			(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) AS remainder,
			DATEDIFF('$today', trans.due_date) AS days 	
			FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as s 
			WHERE s.supplier_id = trans.supplier_id
				AND trans.type = ".ST_SUPPINVOICE." AND (trans.ov_amount + trans.ov_gst + 
					trans.ov_discount - trans.alloc) > 1e-6
				AND DATEDIFF('$today', trans.due_date) > 0 ORDER BY days DESC";
		$result = db_query($sql);
		$title = db_num_rows($result) . _(" overdue Purchase Invoices");
		br(1);
		display_heading($title);
		br();
		$th = array("#", _("Ref."), _("Date"), _("Due Date"), _("Supplier"), _("Currency"), _("Total"), 
			_("Remainder"),	_("Days"));
		start_table(TABLESTYLE);
		table_header($th);
		$k = 0; //row colour counter
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
			label_cell(get_trans_view_str(ST_SUPPINVOICE, $myrow["trans_no"]));
			label_cell($myrow['reference']);
			label_cell(sql2date($myrow['tran_date']));
			label_cell(sql2date($myrow['due_date']));
	    	$name = $myrow["supplier_id"]." ".$myrow["supp_name"];
    		label_cell($name);
    		label_cell($myrow['curr_code']);
		    amount_cell($myrow['total']);
		    amount_cell($myrow['remainder']);
		    label_cell($myrow['days'], "align='right'");
			end_row();
		}
		end_table(2);
	}

	function display_stock_topten($manuf=false)
	{
		global $path_to_root;
		
		$pg = new graph();

		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		$sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, s.stock_id as stock, s.description, 
			SUM(trans.quantity) AS qty FROM
			".TB_PREF."debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d 
			WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no
			AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";
		if ($manuf)
			$sql .= "AND s.mb_flag='M' ";
		$sql .= "AND d.tran_date >= '$begin1' AND d.tran_date <= '$today1' GROUP by stock ORDER BY total DESC, stock
			LIMIT 10";
		$result = db_query($sql);
		if ($manuf)
			$title = _("Top 10 Manufactured Items in fiscal year");
		else	
			$title = _("Top 10 Sold Items in fiscal year");
		br(2);
		display_heading($title);
		br();
		$th = array(_("Item"), _("Amount"), _("Quantity"));
		start_table(TABLESTYLE, "width=30%");
		table_header($th);
		$k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	$name = $myrow["stock"];
    		label_cell($name);
		    amount_cell($myrow['total']);
		    qty_cell($myrow['qty']);
		    $pg->x[$i] = $name; 
		    $pg->y[$i] = $myrow['total'];
		    $i++;
			end_row();
		}
		end_table(2);
		$pg->title     = $title;
		$pg->axis_x    = _("Item");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $today;
		$pg->type      = 2;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
	}
	
	function display_stock_uc_topten($manuf=false)
	{
		global $path_to_root;
		
		$pg = new graph();

		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		$sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, LEFT(s.stock_id,8) as stock, s.description, 
			SUM(trans.quantity) AS qty FROM
			".TB_PREF."debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d 
			WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no
			AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";
		if ($manuf)
			$sql .= "AND s.mb_flag='M' ";
		$sql .= "AND d.tran_date >= '$begin1' AND d.tran_date <= '$today1' GROUP by stock ORDER BY total DESC, stock
			LIMIT 10";
		$result = db_query($sql);
		if ($manuf)
			$title = _("Top 10 Manufactured Items in fiscal year");
		else	
			$title = _("Top 10 Sold Items in fiscal year");
		br(2);
		display_heading($title);
		br();
		$th = array(_("Item"), _("Amount"), _("Quantity"));
		start_table(TABLESTYLE, "width=30%");
		table_header($th);
		$k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	$name = $myrow["stock"];
    		label_cell($name);
		    amount_cell($myrow['total']);
		    qty_cell($myrow['qty']);
		    $pg->x[$i] = $name; 
		    $pg->y[$i] = $myrow['total'];
		    $i++;
			end_row();
		}
		end_table(2);
		$pg->title     = $title;
		$pg->axis_x    = _("Item");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $today;
		$pg->type      = 2;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
	}

	function display_color_topten($manuf=false)
	{
		global $path_to_root;
		
		$pg = new graph();

		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		$sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, SUBSTRING(s.stock_id,10,4) as stock, s.description, 
			SUM(trans.quantity) AS qty FROM
			".TB_PREF."debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d 
			WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no
			AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";
		if ($manuf)
			$sql .= "AND s.mb_flag='M' ";
		$sql .= "AND d.tran_date >= '$begin1' AND d.tran_date <= '$today1' GROUP by stock ORDER BY total DESC, stock
			LIMIT 10";
		$result = db_query($sql);
		if ($manuf)
			$title = _("Top 10 Manufactured Colors in fiscal year");
		else	
			$title = _("Top 10 Sold Colors in fiscal year");
		br(2);
		display_heading($title);
		br();
		$th = array(_("Item"), _("Amount"), _("Quantity"));
		start_table(TABLESTYLE, "width=30%");
		table_header($th);
		$k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	$name = $myrow["stock"];
    		label_cell($name);
		    amount_cell($myrow['total']);
		    qty_cell($myrow['qty']);
		    $pg->x[$i] = $name; 
		    $pg->y[$i] = $myrow['total'];
		    $i++;
			end_row();
		}
		end_table(2);
		$pg->title     = $title;
		$pg->axis_x    = _("Item");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $today;
		$pg->type      = 2;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
  }
  function display_to_sell_topten()
	{
		global $path_to_root;
		
		$pg = new graph();

		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
    $sql = "SELECT SUM(qty) AS qty, SUM(qty*standard_cost) cost, stock_id
      FROM ".TB_PREF."stock_moves WHERE visible = 1 
      GROUP by stock_id
      ORDER by cost DESC LIMIT 20";
		$result = db_query($sql);
    $title = "Items on stock to sale";
		br(2);
		display_heading($title);
		br();
		$th = array(_("Item"), _("Amount"), _("Quantity"));
		start_table(TABLESTYLE, "width=30%");
		table_header($th);
		$k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	$name = $myrow["stock_id"];
    		label_cell($name);
		    amount_cell($myrow['cost']);
		    qty_cell($myrow['qty']);
		    $pg->x[$i] = $name; 
		    $pg->y[$i] = $myrow['cost'];
		    $i++;
			end_row();
		}
		end_table(2);
		$pg->title     = $title;
		$pg->axis_x    = _("Item");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $today;
		$pg->type      = 2;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		//$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
	}
	function display_dimension_topten($dim_type=1)
	{
		global $path_to_root;
		
		$pg = new graph();

		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		$sql = "SELECT SUM(-t.amount) AS total, d.reference, d.name FROM
			".TB_PREF."gl_trans AS t,".TB_PREF."dimensions AS d WHERE
			t.dimension".($dim_type==1 ? "" : "2")."_id = d.id AND
			t.tran_date >= '$begin1' AND t.tran_date <= '$today1' GROUP BY d.id ORDER BY total DESC LIMIT 10";
		$result = db_query($sql, "Transactions could not be calculated");
		$title = _("Top 10 Dimensions in fiscal year");
		br(2);
		display_heading($title);
		br();
		$th = array(_("Dimension"), _("Amount"));
		start_table(TABLESTYLE, "width=30%");
		table_header($th);
		$k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	$name = $myrow['reference']." ".$myrow["name"];
    		label_cell($name);
		    amount_cell($myrow['total']);
		    $pg->x[$i] = $name; 
		    $pg->y[$i] = abs($myrow['total']);
		    $i++;
			end_row();
		}
		end_table(2);
		$pg->title     = $title;
		$pg->axis_x    = _("Dimension");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $today;
		$pg->type      = 5;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
	}	

	function display_gl_info()
	{
		global $path_to_root;
		
		$pg = new graph();

		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		$sql = "SELECT SUM(amount) AS total, c.class_name, c.ctype FROM
			".TB_PREF."gl_trans,".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t, 
			".TB_PREF."chart_class AS c WHERE
			account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid
			AND IF(c.ctype > 3, tran_date >= '$begin1', tran_date >= '0000-00-00') 
			AND tran_date <= '$today1' GROUP BY c.cid ORDER BY c.cid"; 
		$result = db_query($sql, "Transactions could not be calculated");
		$title = _("Class Balances");
		br(2);
		display_heading($title);
		br();
		start_table(TABLESTYLE2, "width=30%");
		$i = 0;
		$total = 0;
		while ($myrow = db_fetch($result))
		{
			if ($myrow['ctype'] > 3)
			{
		    	$total += $myrow['total'];
				$myrow['total'] = -$myrow['total'];
		    	$pg->x[$i] = $myrow['class_name']; 
		    	$pg->y[$i] = abs($myrow['total']);
		    	$i++;
		    }	
			label_row($myrow['class_name'], number_format2($myrow['total'], user_price_dec()), 
				"class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
		}
		$calculated = _("Calculated Return");
		label_row("&nbsp;", "");
		label_row($calculated, number_format2(-$total, user_price_dec()), 
			"class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
    	$pg->x[$i] = $calculated; 
    	$pg->y[$i] = -$total;
		
		end_table(2);
		$pg->title     = $title;
		$pg->axis_x    = _("Class");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $today;
		$pg->type      = 5;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
	}	

  function get_customer_balance($date_from, $date_to)
  {
    $sql = "SELECT sum(if(type = ".ST_CUSTCREDIT."
      OR type =".ST_CUSTPAYMENT.", -1, 1)*(
        ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount-alloc
      )*rate)
      FROM ".TB_PREF."debtor_trans
      WHERE  (type = ".ST_SALESINVOICE."
        OR type = ".ST_CUSTCREDIT."
        OR type =".ST_CUSTPAYMENT.") ";
    if  ($date_from)
      $sql .= " AND due_date >= '".$date_from."'";
    if ($date_to)
      $sql .= " AND due_date < '".$date_to."'";

    $result = db_query($sql, "could not find debtor transactions");
    $row = db_fetch($result);
    return $row[0];

  }
  function get_supplier_balance($date_from, $date_to)
  {
    $sql = "SELECT sum(greatest(0,amount)) FROM (SELECT sum(if(type = ".ST_SUPPCREDIT."
      OR type =".ST_SUPPAYMENT.", -1, 1)*(
        if(type = ".ST_SUPPAYMENT.",-ov_amount,ov_amount)+ov_gst+ov_discount-alloc
      )*rate) AS amount
      FROM ".TB_PREF."supp_trans
      WHERE  (type = ".ST_SUPPINVOICE."
        OR type = ".ST_SUPPCREDIT."
        OR type =".ST_SUPPAYMENT.")";
    if  ($date_from)
      $sql .= " AND due_date >= '".$date_from."'";
    if ($date_to)
      $sql .= " AND due_date < '".$date_to."'";

    $sql .= " GROUP BY supplier_id) AS supplier ";

    $result = db_query($sql, "could not find supplier transactions");
    $row = db_fetch($result);
    return $row[0];

  }

  function get_vat_balance($date_from, $date_to=null)
  {
    $sql = "SELECT sum(amount)  
      FROM ".TB_PREF."gl_trans, ".TB_PREF."tax_types
      WHERE account = sales_gl_code 
      AND name = 'VAT'";
    if  ($date_from)
      $sql .= " AND tran_date >= '".$date_from."'";
    if ($date_to)
      $sql .= " AND tran_date < '".$date_to."'";

    $result = db_query($sql, "could not find VAT transactions");
    $row = db_fetch($result);
    return $row[0];

  }

	function display_bank_info()
  {
    global $path_to_root;
    $today = Today();
    $today_sql = date2sql($today);

    # find list of bank and current balance
    $pg = new graph();
    $sql = "SELECT ".TB_PREF."bank_accounts.id bank_id, bank_account_name, bank_curr_code, account_code
      FROM ".TB_PREF."bank_accounts WHERE inactive = 0
      AND account_code < 2000 
      AND account_type <> 3";
    $result = db_query($sql, "Bank accounts couldn't be found");
    $title = _("Bank Balance");
    br(2);
    display_heading($title);
    br();
    start_table(TABLESTYLE2, "width=30%");

    $i = 0;
    $total = 0;
    while ($myrow = db_fetch($result))
    {
      $bank_account_name = $myrow['bank_account_name'];
      $bank_account = $myrow['bank_id'];
      $currency = $myrow['bank_curr_code'];
      $raw_balance = get_balance_before_for_bank_account($bank_account, "2099-01-01");
      $balance = $raw_balance*retrieve_exrate($currency,$today_sql);

      $pg->x[$i] = $bank_account_name;
      $pg->y[$i] = $balance;
      $total += $balance;
      $pg->z[$i] = $total;
      $i++;


      label_row($bank_account_name, number_format2($raw_balance, user_price_dec())."  ".$currency, 
        "class='label' style='font-weight:italic;'", "style='font-weight:bold;' align=right");
    }
    #Total Balance
    $calculated = _("Total Balance");
    label_row("&nbsp;", "");
    label_row($calculated, number_format2($total, user_price_dec()), 
    "class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
    $pg->x[$i] = $calculated; 
    $pg->y[$i] = $total;
    end_table(2);
    $pg->title     = $title;
    $pg->axis_x    = _("Account");
    $pg->axis_y    = _("Amount");
    $pg->graphic_1 = "Balance";
    $pg->graphic_2 = "Cumulated";
    $pg->type      = 2;
    $pg->skin      = 1;
    $pg->built_in  = false;
    $filename = company_path(). "/pdf_files/". uniqid("").".png";
    //$pg->display($filename, true);
    //start_table(TABLESTYLE);
    //start_row();
    //echo "<td>";
    //echo "<img src='$filename' border='0' alt='$title'>";
    //echo "</td>";
    //end_row();
    //end_table(1);

    return $total;
  }

  function display_loan_info($total,$skip_graphic)
  {
    global $path_to_root;
    $today = Today();
    $today_sql = date2sql($today);

    $pg = new graph();
    $i = 0;
    $total_loan = 0;
    #new graphic

    $sql = "SELECT ".TB_PREF."bank_accounts.id bank_id, bank_account_name, bank_curr_code, account_code
      FROM ".TB_PREF."bank_accounts WHERE inactive = 0
      AND account_code >= 2000 
      AND account_type <> 3";
    $result = db_query($sql, "Bank accounts couldn't be found");
    $title = _("Loan");
    br(2);
    display_heading($title);
    br();
    start_table(TABLESTYLE2, "width=30%");
    while ($myrow = db_fetch($result))
    {
      $bank_account_name = $myrow['bank_account_name'];
      $bank_account = $myrow['bank_id'];
      $currency = $myrow['bank_curr_code'];
      $raw_balance = -get_balance_before_for_bank_account($bank_account, "2012-01-01");
      $balance = $raw_balance*retrieve_exrate($currency,$today_sql);

      $pg->x[$i] = $bank_account_name;
      $pg->y[$i] = $balance;
      $total_loan += $balance;
      //$pg->z[$i] = $total+$total_loan;
      $pg->z[$i] = $total_loan;
      $i++;


      label_row($bank_account_name, number_format2($raw_balance, user_price_dec())."  ".$currency, 
        "class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
    }


    #Total Balance
    $calculated = _("Balance");
    label_row("&nbsp;", "");
    label_row("Total", number_format2($total_loan, user_price_dec()), 
    "class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
    label_row("Balance", number_format2(-$total_loan+$total, user_price_dec()), 
    "class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
    $pg->x[$i] = $calculated; 
    $pg->y[$i] = $total;
    $pg->z[$i] = $total-$total_loan;
    end_table(2);
if ($skip_grapic==True)
{
    $pg->title     = $title;
    $pg->axis_x    = _("Account");
    $pg->axis_y    = _("Amount");
    $pg->graphic_1 = "Balance";
    $pg->graphic_2 = "Cumulated";
    $pg->type      = 2;
    $pg->skin      = 1;
    $pg->built_in  = false;
    $filename = company_path(). "/pdf_files/". uniqid("").".png";
    $pg->display($filename, true);
    start_table(TABLESTYLE);
    start_row();
    echo "<td>";
    echo "<img src='$filename' border='0' alt='$title'>";
    echo "</td>";
    end_row();
    end_table(1);
}
  }	                

	function display_cash_flow($total_bank)
  {
    global $path_to_root;
		$begin = begin_fiscalyear();
		$today = Today();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
    $fortnight = add_days($today,14);
    $fortnight1 = date2sql($fortnight);
    $month = add_months($today,1);
    $month1 = date2sql($month);
    $bvat_day = get_tax_date(true);
    $evat_day = get_tax_date(false);
    $pvat_day = add_months($evat_day,1);
    $bvat_day1 = date2sql($bvat_day);
    $evat_day1 = date2sql($evat_day);

    $pg = new graph();
    $i = 0;
    $total =0;

    $title = _("Cash Flow");
    //br(2);
    //display_heading($title);
    //br();
    //start_table(TABLESTYLE2, "width=30%");
    $rows=array(array("Bank", $total_bank));
    if (date_diff2($pvat_day,$today,"s") <=0 )
      $rows[]=array("VAT", get_vat_balance($bvat_day1, $evat_day1));
    $rows[]=array("Overdue", get_customer_balance($begin1,$today1));
    $rows[]=array("*  Supplier", -get_supplier_balance($begin1,$today1));

    if (date_diff2($pvat_day, $today, "s") > 0  && date_diff2($pvat_day ,$fortnight, "s") <= 0 )
      $rows[]=array("VAT", get_vat_balance($bvat_day1, $evat_day1));
    $rows[]=array("14 days", get_customer_balance($today1,$fortnight1));
    $rows[]=array("*  Supplier", -get_supplier_balance($today1, $fortnight1));

    if (date_diff2($pvat_day, $fortnight, "s") > 0  && date_diff2($pvat_day,$month, "s") <= 0 )
      $rows[]=array("VAT", get_vat_balance($bvat_day1, $evat_day1));
    $rows[]=array("1 month", get_customer_balance($fortnight1,$month1));
    $rows[]=array("* Supplier ", -get_supplier_balance($fortnight1,$month1));

    if (date_diff2($pvat_day, $month1, "s") > 0)
      $rows[]=array("next VAT", get_vat_balance($bvat_day1)); #all vat
    else
      $rows[]=array("next VAT", get_vat_balance($evat_day1)); #VAT left
    #next months
    $d=$month;
    $d1=$month1;
    for($j=2; $j <= 5 ; $j++)
    {
      $e=add_months($d,1);
      $e1= date2sql($e);
      $rows[]=array($j." month", get_customer_balance($d1,$e1));
      $rows[]=array("* Supplier ", -get_supplier_balance($d1,$e1));
      $d=$e;
      $d1=$e1;

    }

    while ($myrow = $rows[$i])
    {
      $name = $myrow[0];
      $amount = $myrow[1];

      $pg->x[$i] = $name;
      $pg->y[$i] = $amount;
      $total += $amount;
      if ($i != 0) $pg->z[$i] = $total;
      $i++;


      //label_row($name, number_format2($amount, user_price_dec()), 
        //"class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
    }




    #Total Balance
    $calculated = _("Balance");
    //label_row("&nbsp;", "");
    //label_row("Total", number_format2($total, user_price_dec()), 
    //"class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
    $pg->x[$i] = $calculated; 
    $pg->y[$i] = $total;
    //$pg->z[$i] = $total-$total_loan;
    //end_table(2);
    $pg->title     = $title;
    $pg->axis_x    = _("Account");
    $pg->axis_y    = _("Amount");
    $pg->graphic_1 = "Balance";
    $pg->graphic_2 = "Cumulated";
    $pg->type      = 2;
    $pg->skin      = 1;
    $pg->built_in  = false;
    $filename = company_path(). "/pdf_files/". uniqid("").".png";
    $pg->display($filename, true);
    start_table(TABLESTYLE);
    start_row();
    echo "<td>";
    echo "<img src='$filename' border='0' alt='$title'>";
    echo "</td>";
    end_row();
    end_table(1);
  }	

  function get_tax_date($start=true)
  {
    $date = Today();
    $row = get_company_prefs();
    $edate = add_months($date, -$row['tax_last']);
    $edate = end_month($edate);
    $bdate = begin_month($edate);
    $bdate = add_months($bdate, -$row['tax_prd'] + 1);
    #firt tax term include beginnig of life
    //if (date1_greater_date2(__date(2011,06,02),$bdate))
      //$bdate=__date(2011,04,01);
    return ($start ? $bdate : $edate);
  }

 function get_initial_stock($date)
	{
		$begin = begin_fiscalyear();

    $sql = "SELECT sum(amount)
      FROM ".TB_PREF."gl_trans
      WHERE (account = 1001 or account = 1002)
      AND tran_date >= '".date2sql($begin)."'
      AND tran_date < '".date2sql($date)."' 
      ";

		$result = db_query($sql);
    $row = db_fetch($result);
    return $row[0];
	 }

 function display_stock_graph()
	{
    $end = Today();

    $date_format = "DATE_FORMAT(tran_date, '%y-%m')";
    $begin = add_years($end,-1);
    $title = "Stock";

    $sql = "SELECT ".$date_format." AS date_g,
      DATE_FORMAT(MIN(tran_date),'%b %y') AS date,
      sum(greatest(0,amount)) AS pos, sum(least(0,amount)) AS neg
      FROM ".TB_PREF."gl_trans
      WHERE account = 1001
      AND tran_date >= '".date2sql($begin)."'
      AND tran_date <= '".date2sql($end)."' 
      GROUP BY  date_g
      ORDER BY date_g
      ";

		$result = db_query($sql);
    $pg = new graph();
    $i = 0;
    $total = get_initial_stock($begin);
    $last_date = "";

      $pg->x[$i] = "";
      $pg->z[$i] = "";
      $pg->z[$i] = $total;
      $i++;

		while ($myrow = db_fetch($result))
    {
      $date = $myrow['date'];
      $cust = $myrow['neg'];
      $supp = $myrow['pos'];

      $total += $supp;
      $total += $cust;
      $pg->x[$i] = $date == $last_date ? "" : $date;
      $last_date = $date;
      $pg->y[$i] = -$cust;
      $pg->z[$i] = $total;
      $i++;
    }
    #last stock


		$pg->title     = $title;
		$pg->axis_x    = _("Time");
		$pg->axis_y    = _("Sales");
		$pg->graphic_1 = "Dispatched";
		$pg->graphic_2 = "Stock Left";
		$pg->type      = 1;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
	 }

 function display_sales_graph()
	{
    $title = "Sales/Week";
    $end = Today();
    $begin = add_months($end,-3);

    $date_format = "DATE_FORMAT(tran_date, '%u')";

    $sql = "SELECT ".$date_format." AS date_g,
      DATE_FORMAT(MIN(tran_date),'%b/%d') AS date,
      sum(if(account = 1001, least(amount,0), 0)) AS stock, sum(if(account=4000,least(amount,0), 0)) AS sales
      FROM ".TB_PREF."gl_trans
      WHERE (account = 1001 OR account = 4000)
      AND tran_date >= '".date2sql($begin)."'
      AND tran_date <= '".date2sql($end)."' 
      GROUP BY  date_g
      ORDER BY date_g
      ";

		$result = db_query($sql);
    $pg = new graph();
    $i = 0;
    $total = get_initial_stock($begin);
    $last_date = "";

		while ($myrow = db_fetch($result))
    {
      $date = $myrow['date'];
      $sales = $myrow['sales'];
      $stock = -$myrow['stock'];

      //if ($date != $last_date)       n
      $pg->x[$i] = $date == $last_date ? "" : $date;
      $last_date = $date;
      $pg->y[$i] = $stock;
      $pg->z[$i] = -$sales;
      $i++;
    }

		$pg->title     = $title;
		$pg->axis_x    = _("Time");
		$pg->axis_y    = _("Sales");
		$pg->graphic_2 = "Invoiced";
		$pg->graphic_1 = "Dispatched";
		$pg->type      = 1;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
	 }

 function display_account_graph($month)
	{
    $title = "Sales/Week";
    $end = Today();

    $date_format = "DATE_FORMAT(tran_date, '%u-%b')";
    if ($month)
    {
      $date_format = "DATE_FORMAT(tran_date, '%ye%m')";
      $begin = add_years($end,-1);
      $title = "Stock";
    }
    else
    {
      $begin = add_months($end,-3);
    }

    $sql = "SELECT ".$date_format." AS date_g,
      DATE_FORMAT(MIN(tran_date),'%b %y') AS date,
      sum(greatest(0,amount)) AS pos, sum(least(0,amount)) AS neg
      FROM ".TB_PREF."gl_trans
      WHERE account = 1001
      AND tran_date >= '".date2sql($begin)."'
      AND tran_date <= '".date2sql($end)."' 
      GROUP BY  date_g
      ORDER BY date_g
      ";

		$result = db_query($sql);
    $pg = new graph();
    $i = 0;
    $total = get_initial_stock($begin);
    $last_date = "";

		while ($myrow = db_fetch($result))
    {
      $date = $myrow['date'];
      $cust = $myrow['neg'];
      $supp = $myrow['pos'];

      //if ($date != $last_date)       n
      $pg->x[$i] = $date == $last_date ? "" : $date;
      $last_date = $date;
      $pg->y[$i] = -$cust;
      $total += $supp;
      if($month)
        $pg->z[$i] = $total;
      $total += $cust;
      $i++;
    }
    if($month)
    {

      $pg->x[$i] = ".";
      $pg->z[$i] = $total;
    }

		$pg->title     = $title;
		$pg->axis_x    = _("Time");
		$pg->axis_y    = _("Sales");
		$pg->graphic_1 = "Sales";
		$pg->graphic_2 = "Stock";
		$pg->type      = 1;
		$pg->skin      = 1;
		$pg->built_in  = false;
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		start_table(TABLESTYLE);
		start_row();
		echo "<td>";
		echo "<img src='$filename' border='0' alt='$title'>";
		echo "</td>";
		end_row();
		end_table(1);
	 }
?>
