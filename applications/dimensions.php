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
	class dimensions_app extends application
	{
		function dimensions_app()
		{
			global $installed_modules;
			$dim = get_company_pref('use_dimension');
			$this->application("proj",_("&Dimensions"));

			if ($dim > 0)
			{
				$this->add_module(_("Transactions"));
				$this->add_lapp_function(0, _("Dimension &Entry"),"dimensions/dimension_entry.php?");
				$this->add_lapp_function(0, _("&Outstanding Dimensions"),"dimensions/inquiry/search_dimensions.php?outstanding_only=1");

				$this->add_module(_("Inquiries and Reports"));
				$this->add_lapp_function(1, _("Dimension &Inquiry"),"dimensions/inquiry/search_dimensions.php?");

				$this->add_rapp_function(1, _("Dimension &Reports"),"reporting/reports_main.php?Class=4");
				if (count($installed_modules) > 0)
				{
					$i = 0;
					foreach ($installed_modules as $mod)
					{
						if ($mod["tab"] == "proj")
						{
							if ($i++ == 0)
								$this->add_module(_("Maintenance"));
							$this->add_rapp_function(2, $mod["name"], "modules/".$mod["path"]."/".$mod["filename"]."?");
						}
					}
				}
			}
		}
	}


?>