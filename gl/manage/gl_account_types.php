<?php

$page_security = 3;
$path_to_root="../..";
include($path_to_root . "/includes/session.inc");

page(_("GL Account Groups"));

include($path_to_root . "/gl/includes/gl_db.inc");

include($path_to_root . "/includes/ui.inc");

simple_page_mode(false);
//-----------------------------------------------------------------------------------

function can_process() 
{
	global $selected_id;

	if (strlen($_POST['name']) == 0) 
	{
		display_error( _("The account group name cannot be empty."));
		set_focus('name');
		return false;
	}

	if (isset($selected_id) && ($selected_id == $_POST['parent'])) 
	{
		display_error(_("You cannot set an account group to be a subgroup of itself."));
		return false;
	}

	return true;
}

//-----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	if (can_process()) 
	{

    	if ($selected_id != "") 
    	{
    		update_account_type($selected_id, $_POST['name'], $_POST['class_id'], $_POST['parent']);
			display_notification(_('Selected account type has been updated'));
    	} 
    	else 
    	{
    		add_account_type($_POST['name'], $_POST['class_id'], $_POST['parent']);
			display_notification(_('New account type has been added'));
    	}
		$Mode = 'RESET';
	}
}

//-----------------------------------------------------------------------------------

function can_delete($selected_id)
{
	if ($selected_id == "")
		return false;
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."chart_master
		WHERE account_type=$selected_id";
	$result = db_query($sql, "could not query chart master");
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account group because GL accounts have been created referring to it."));
		return false;
	}

	$sql= "SELECT COUNT(*) FROM ".TB_PREF."chart_types
		WHERE parent=$selected_id";
	$result = db_query($sql, "could not query chart types");
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account group because GL account groups have been created referring to it."));
		return false;
	}

	return true;
}


//-----------------------------------------------------------------------------------

if ($Mode == 'Delete')
{

	if (can_delete($selected_id))
	{
		delete_account_type($selected_id);
		display_notification(_('Selected currency has been deleted'));
	}
	$Mode = 'RESET';
}
if ($Mode == 'RESET')
{
 	$selected_id = '';
	$_POST['name']  = '';
	unset($_POST['parent']);
	unset($_POST['class_id']);
}
//-----------------------------------------------------------------------------------

$result = get_account_types();
start_form();
start_table($table_style);
$th = array(_("Name"), _("Subgroup Of"), _("Class Type"), "", "");
table_header($th);

$k = 0;
while ($myrow = db_fetch($result)) 
{

	alt_table_row_color($k);

	$bs_text = get_account_class_name($myrow["class_id"]);

	if ($myrow["parent"] == reserved_words::get_any_numeric()) 
	{
		$parent_text = "";
	} 
	else 
	{
		$parent_text = get_account_type_name($myrow["parent"]);
	}

	label_cell($myrow["name"]);
	label_cell($parent_text);
	label_cell($bs_text);
	edit_button_cell("Edit".$myrow["id"], _("Edit"));
	edit_button_cell("Delete".$myrow["id"], _("Delete"));
	end_row();
}

end_table();
end_form();
echo '<br>';
//-----------------------------------------------------------------------------------

start_form();

start_table($table_style2);

if ($Mode == 'Edit') 
{
	//editing an existing status code
	$myrow = get_account_type($selected_id);

	$_POST['name']  = $myrow["name"];
	$_POST['parent']  = $myrow["parent"];
	$_POST['class_id']  = $myrow["class_id"];
 }
	hidden('selected_id', $selected_id);

text_row_ex(_("Name:"), 'name', 50);

gl_account_types_list_row(_("Subgroup Of:"), 'parent', null, _("None"), true);

class_list_row(_("Class Type:"), 'class_id', null);

end_table(1);

submit_add_or_update_center($selected_id == '', '', true);

end_form();

//------------------------------------------------------------------------------------

end_page();

?>
