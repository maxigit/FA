<?php
/**********************************************************************
// Creator: Alastair Robertson
// date_:   2013-01-30
// Title:   Dashboard application
// Free software under GNU GPL
***********************************************************************/

class dashboard_app extends application
{
    var $widgets;
    var $apps;

	function dashboard_app()
	{
		$this->application("Dashboard", _($this->help_context = "&Dashboard"));

        //$this->add_module(_("Dashboard"));
        $this->widgets = array();
        $this->add_extensions();

        $this->apps = array("orders"=>_("Sales"),
                        "AP"=>_("Purchases"),
                        "stock"=>_("Items and Inventory"),
                        "manuf"=>_("Manufacturing"),
                        "GL"=>_("Banking and General Ledger"),
                        "Dashboard"=>_("Dashboard"));
	}

      function add_widget($name,$title,$path="",$access='SA_OPEN')
      {
          $widget = new widget($name,$title,$path,$access);
          $this->widgets[] = $widget;
          return $widget;
      }

      function get_widget($name)
      {
            foreach ($this->widgets as $widget)
            {
                if ($widget->name == $name)
                  return $widget;
            }
            return null;
      }

      function get_widget_list()
      {

          $list = array();
          foreach ($this->widgets as $widget)
          {
                if ($_SESSION["wa_current_user"]->can_access_page($widget->access))
                {
                    $list[$widget->name] = $widget->title;
                }
          }
          return $list;
      }
}

class widget
{
    var $name;
    var $app;
    var $title;
    var $path;
    var $access;

    function widget($name,$title,$path,$access='SA_OPEN')
    {
        $this->name = $name;
        $this->title = $title;
        $this->path = $path;
        $this->access = $access;
    }

    function render($id, $title)
    {
      // override this function to prepare the widget for display
      // $id is the database id of the row in dashboard_widgets table
      // and should be used to identify the div containing this iteration of the widget
      // $title is the widget title retrieved from dashboard_widgets
    }

    function edit_param()
    {
      // override this function to display the form to collect the parameters that
      // will be passed to the iteration of the widget to be displayed
    }

    function validate_param()
    {
      // override this function to validate the widget's parameters into
      // a json string and save in database row
    }

    function save_param()
    {
      // override this function to format the widget's parameters into
      // a json string and save in database row
    }
}

?>