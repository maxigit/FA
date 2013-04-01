<?php

/* List of installed additional extensions. If extensions are added to the list manually
	make sure they have unique and so far never used extension_ids as a keys,
	and $next_extension_id is also updated. More about format of this file yo will find in 
	FA extension system documentation.
*/

$next_extension_id = 31; // unique id for next installed extension

$installed_extensions = array (
  0 => 
  array (
    'name' => 'British COA',
    'package' => 'chart_en_GB-general',
    'version' => '2.3.0-2',
    'type' => 'chart',
    'active' => true,
    'path' => 'sql',
    'sql' => 'en_GB-general.sql',
  ),
  1 => 
  array (
    'name' => 'Theme Elegant',
    'package' => 'elegant',
    'version' => '2.3.0-5',
    'type' => 'theme',
    'active' => true,
    'path' => 'themes/elegant',
  ),
  2 => 
  array (
    'name' => 'Theme Anterp',
    'package' => 'anterp',
    'version' => '2.3.0-1',
    'type' => 'theme',
    'active' => true,
    'path' => 'themes/anterp',
  ),
  5 => 
  array (
    'name' => 'Inventory Items CSV Import',
    'package' => 'import_items',
    'version' => '2.3.0-1',
    'type' => 'extension',
    'active' => true,
    'path' => 'modules/import_items',
  ),
  6 => 
  array (
    'name' => 'Theme Modern',
    'package' => 'modern',
    'version' => '2.3.0-3',
    'type' => 'theme',
    'active' => true,
    'path' => 'themes/modern',
  ),
  7 => 
  array (
    'name' => 'Theme Exclusive',
    'package' => 'exclusive',
    'version' => '2.3.0-5',
    'type' => 'theme',
    'active' => true,
    'path' => 'themes/exclusive',
  ),
  8 => 
  array (
    'name' => 'Theme Newwave',
    'package' => 'newwave',
    'version' => '2.3.0-4',
    'type' => 'theme',
    'active' => true,
    'path' => 'themes/newwave',
  ),
  9 => 
  array (
    'name' => 'Theme Studio',
    'package' => 'studio',
    'version' => '2.3.0-3',
    'type' => 'theme',
    'active' => true,
    'path' => 'themes/studio',
  ),
  11 => 
  array (
    'name' => 'zen_import',
    'package' => 'zen_import',
    'version' => '2.3.2-3',
    'type' => 'extension',
    'active' => true,
    'path' => 'modules/zen_import',
  ),
  12 => 
  array (
    'name' => 'Annual expense breakdown report',
    'package' => 'rep_annual_expense_breakdown',
    'version' => '2.3.0-1',
    'type' => 'extension',
    'active' => true,
    'path' => 'modules/rep_annual_expense_breakdown',
  ),
  13 => 
  array (
    'name' => 'textcart',
    'package' => 'textcart',
    'version' => '2.3.9-1',
    'type' => 'extension',
    'active' => true,
    'path' => 'modules/textcart',
  ),
  15 => 
  array (
    'name' => 'Report Generator',
    'package' => 'repgen',
    'version' => '2.3.9-2',
    'type' => 'extension',
    'active' => true,
    'path' => 'modules/repgen',
  ),
  16 => 
  array (
    'name' => 'Requisitions',
    'package' => 'requisitions',
    'version' => '2.3.3-8',
    'type' => 'extension',
    'active' => true,
    'path' => 'modules/requisitions',
  ),
  17 => 
  array (
    'name' => 'Bank Statement w/ Reconcile',
    'package' => 'rep_statement_reconcile',
    'version' => '2.3.3-2',
    'type' => 'extension',
    'active' => true,
    'path' => 'modules/rep_statement_reconcile',
  ),
  24 => 
  array (
    'name' => 'Inventory History',
    'package' => 'rep_inventory_history',
    'version' => '2.3.2-1',
    'type' => 'extension',
    'active' => true,
    'path' => 'modules/rep_inventory_history',
  ),
  27 => 
  array (
    'package' => 'rep_picking_list',
    'name' => 'rep_picking_list',
    'version' => '-',
    'available' => '',
    'type' => 'extension',
    'path' => 'modules/rep_picking_list',
    'active' => false,
  ),
  28 => 
  array (
    'name' => 'Theme Dashboard',
    'package' => 'dashboard_theme',
    'version' => '2.3.15-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/dashboard',
  ),
  29 => 
  array (
    'name' => 'Company Dashboard',
    'package' => 'dashboard',
    'version' => '2.3.15-3',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/dashboard',
  ),
  30 => 
  array (
    'package' => 'picking',
    'name' => 'picking',
    'version' => '-',
    'available' => '',
    'type' => 'extension',
    'path' => 'modules/picking',
    'active' => false,
  ),
);
?>