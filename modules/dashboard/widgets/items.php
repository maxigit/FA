<?php
/**********************************************************************
// Creator: Alastair Robertson
// date_:   2013-01-30
// Title:   Items widget for dashboard
// Free software under GNU GPL
***********************************************************************/

global $path_to_root;
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/dashboard/includes/dashboard_ui.inc");

class items
{
    var $page_security = 'SA_SALESANALYTIC';

    var $top;
    var $ss_start;
    var $ss_length;
    var $item_type;
    var $graph_type;
    var $data_filter;

    function items($params='')
    {
        if (isset($params))
        {
            $data=json_decode(html_entity_decode($params, ENT_QUOTES));
            if ($data != null) {
                if ($data->top != '')
                    $this->top = $data->top;
				if($data->ss_start != '') $this->ss_start = $data->ss_start;
				if($data->ss_length != '') $this->ss_length = $data->ss_length;
                $this->item_type = $data->item_type;
                $this->graph_type = $data->graph_type;
                if ($data->data_filter != '')
                    $this->data_filter = $data->data_filter;
            }
        }
    }

    function render($id, $title)
    {
        global $path_to_root;
        if (!isset($this->top))
            $this->top = 10;
		if(!isset($this->ss_start))
			$this->ss_start  = 1;

        $begin = begin_fiscalyear();
        $today = Today();
        $begin1 = date2sql($begin);
        $today1 = date2sql($today);
		$sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total,";
		$sql .= "SUBSTRING(s.stock_id, ".$this->ss_start;
		if(isset($this->ss_length))
			$sql .= ", ".$this->ss_length;
		$sql .= ") stock_id , s.description,
            SUM(trans.quantity) AS qty FROM
            ".TB_PREF."debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d
            WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no
            AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";
        if ($this->item_type == 'manuf')
            $sql .= "AND s.mb_flag='M' ";
        if ($this->data_filter != '')
            $sql .= ' AND '.$this->data_filter;
        $sql .= "AND d.tran_date >= '$begin1' AND d.tran_date <= '$today1' GROUP by stock_id ORDER BY total DESC, s.stock_id "
              ." LIMIT ".$this->top;
        $result = db_query($sql);

        if ($this->graph_type=='Table') {
            if ($this->item_type == 'manuf')
                $title = _("Top Manufactured Items");
            else
                $title = _("Top Sold Items");
            display_heading($title);
            br();
            $th = array(_("Rank"),_("Item"), _("Amount"), _("Quantity"));
            start_table(TABLESTYLE, "width=98%");
            table_header($th);
            $k = 0; //row colour counter
			$rank=1;
            while ($myrow = db_fetch($result))
            {
                alt_table_row_color($k);
                $name = $myrow["stock_id"];
                label_cell($rank++);
                label_cell($name);
                amount_cell($myrow['total']);
                qty_cell($myrow['qty']);
                end_row();
            }
            end_table(1);
        } else {
            $pg = new graph();
            $i = 0;
            while ($myrow = db_fetch($result))
            {
                $pg->x[$i] = $myrow["stock_id"];
                $pg->y[$i] = $myrow['total'];
                $i++;
            }
            $pg->title     = $title;
            $pg->axis_x    = _("Item");
            $pg->axis_y    = _("Amount");
            $pg->graphic_1 = $today;
            $pg->type      = 2;
            $pg->skin      = 1;
            $pg->built_in  = false;
            $filename = company_path(). "/pdf_files/". uniqid("").".png";
            $pg->display($filename, true);
            echo "<img src='$filename' border='0' alt='$title' style='max-width:100%'>";
        }
    }

    function edit_param()
    {
        global $top;

        $item_types = array(
            'stock' => _("Stock"),
            'manuf' => _("Manufactured")
        );
        $graph_types = array(
            'PieChart' => _("Pie Chart"),
            'Table' => _("Table")
        );
        $_POST['top'] = $this->top;
        $_POST['item_type'] = $this->item_type;
        $_POST['graph_type'] = $this->graph_type;
        $_POST['ss_start'] = $this->ss_start;
        $_POST['ss_length'] = $this->ss_length;
        $_POST['data_filter'] = $this->data_filter;
        text_row_ex(_("Number of items:"), 'top', 2);
        text_row_ex(_("Filter:"), 'data_filter', 50);
        text_row_ex(_("Substring start"), 'ss_start', 2);
        text_row_ex(_("Substring end"), 'ss_length', 2);
        select_row(_("Graph Type"), "graph_type", null, $graph_types, null);
        select_row(_("Item Type"), "item_type", null, $item_types, null);
    }

    function validate_param()
    {
        $input_error = 0;
        //if (!is_numeric($_POST['top']))
        //  {
        //      $input_error = 1;
        //      display_error( _("The number of weeks must be numeric."));
        //      set_focus('top');
        //  }

        //  if ($_POST['top'] == '')
        //      $_POST['top'] = 10;

          return $input_error;
      }

    function save_param()
    {
        $param = array('top' => $_POST['top'],
                        'item_type' => $_POST['item_type'],
			'graph_type' => $_POST['graph_type'],
			'ss_start' => $_POST['ss_start'],
			'ss_length' =>  $_POST['ss_length'],
        'data_filter' => $_POST['data_filter']);
        return json_encode($param);
    }

}
