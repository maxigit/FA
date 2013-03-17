<?php
/**********************************************************************
// Creator: Alastair Robertson
// date_:   2013-01-30
// Title:   Daily Sales widget for dashboard
// Free software under GNU GPL
***********************************************************************/

global $path_to_root;
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/dashboard/includes/dashboard_ui.inc");

class dailysales
{
    var $page_security = 'SA_CUSTPAYMREP';

    var $top;
    var $graph_type;
    var $data_filter;

    function dailysales($params='')
    {
        if (isset($params))
        {
            $data=json_decode(html_entity_decode($params));
            if ($data != null) {
                if ($data->top != '')
                    $this->top = $data->top;
                $this->graph_type = $data->graph_type;
                if ($data->data_filter != '')
                    $this->data_filter = $data->data_filter;
            }
        }
    }

    function render($id, $title)
    {
        $begin = begin_fiscalyear();
        $today = Today();
        $begin1 = date2sql($begin);
        $today1 = date2sql($today);

        $sql = 'SELECT * FROM (SELECT subdate(trans_date, weekday(trans_date)) `Week Start`, '
            .'weekofyear(trans_date) `Week No`, '
            .'sum(case when weekday(trans_date)=0 then amount else 0 end) Monday, '
            .'sum(case when weekday(trans_date)=1 then amount else 0 end) Tuesday, '
            .'sum(case when weekday(trans_date)=2 then amount else 0 end) Wednesday, '
            .'sum(case when weekday(trans_date)=3 then amount else 0 end) Thursday, '
            .'sum(case when weekday(trans_date)=4 then amount else 0 end) Friday, '
            .'sum(case when weekday(trans_date)=5 then amount else 0 end) Saturday, '
            .'sum(case when weekday(trans_date)=6 then amount else 0 end) Sunday '
            .'FROM (SELECT ttd.tran_date AS trans_date, '
                .'sum(ttd.net_amount*ex_rate) amount '
            .'FROM  '.TB_PREF.'trans_tax_details ttd  '
	    .'WHERE  ttd.trans_type = '.ST_SALESINVOICE ." AND tran_date >= '$begin1' AND tran_date <= '$today1'";
        if ($this->data_filter != '')
            $sql .= ' AND '.$this->data_filter;
        $sql .= ' GROUP BY trans_date  '
            .') days '
            .'GROUP BY weekofyear(trans_date) '
            .'ORDER BY max(trans_date) desc ';
        if (isset($this->top))
            $sql .= ' limit '.$this->top;
        $sql .= ") weeks ORDER BY `Week Start`";
        $result = db_query($sql) or die(_('Error getting daily sales data'));

        $rows = array();
        //flag is not needed
        $flag = true;
        $table = array();
        $table['cols'] = array(
            array('label' => 'Week Start', 'type' => 'string'),
            array('label' => 'Monday', 'type' => 'number'),
            array('label' => 'Tuesday', 'type' => 'number'),
            array('label' => 'Wednesday', 'type' => 'number'),
            array('label' => 'Thursday', 'type' => 'number'),
            array('label' => 'Friday', 'type' => 'number'),
            array('label' => 'Saturday', 'type' => 'number'),
            array('label' => 'Sunday', 'type' => 'number')
        );

        $rows = array();
        while($r = db_fetch_assoc($result)) {
            $temp = array();
            // the following line will used to slice the Pie chart
            $temp[] = array('v' => (string) $r['Week Start'], 'f' => sql2date($r['Week Start']));
            $temp[] = array('v' => (float) $r['Monday'], 'f' => number_format2($r['Monday'], user_price_dec()));
            $temp[] = array('v' => (float) $r['Tuesday'], 'f' => number_format2($r['Tuesday'], user_price_dec()));
            $temp[] = array('v' => (float) $r['Wednesday'], 'f' => number_format2($r['Wednesday'], user_price_dec()));
            $temp[] = array('v' => (float) $r['Thursday'], 'f' => number_format2($r['Thursday'], user_price_dec()));
            $temp[] = array('v' => (float) $r['Friday'], 'f' => number_format2($r['Friday'], user_price_dec()));
            $temp[] = array('v' => (float) $r['Saturday'], 'f' => number_format2($r['Saturday'], user_price_dec()));
            $temp[] = array('v' => (float) $r['Sunday'], 'f' => number_format2($r['Sunday'], user_price_dec()));
            $rows[] = array('c' => $temp);
        }

        $table['rows'] = $rows;
        $jsonTable = json_encode($table);

        $js =
"google.load('visualization', '1', {'packages':['corechart','table']});
google.setOnLoadCallback(drawChart".$id.");
function drawChart".$id."() {
  var data = new google.visualization.DataTable(".$jsonTable.");
  var options = {";
        if ($this->graph_type != 'Table')
            $js .="height: 300, ";
        $js .= "title: '".$title."'
      };
  var chart".$id." = new google.visualization.".$this->graph_type."(document.getElementById('widget_div_".$id."'));
  chart".$id.".draw(data, options);
}";
        add_js_source($js);
    }

    function edit_param()
    {
        global $top, $sequences, $asc_desc;

        $graph_types = array(
            'LineChart' => _("Line Chart"),
            'ColumnChart' => _("Column Chart"),
            'Table' => _("Table")
        );
        $_POST['top'] = $this->top;
        $_POST['graph_type'] = $this->graph_type;
        $_POST['data_filter'] = $this->data_filter;
        text_row_ex(_("Number of weeks:"), 'top', 2);
        text_row_ex(_("Filter:"), 'data_filter', 50);
        select_row(_("Graph Type:"), "graph_type", null, $graph_types, null);
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
                        'data_filter' => $_POST['data_filter'],
                        'graph_type' => $_POST['graph_type']);
        return json_encode($param);
    }

}
