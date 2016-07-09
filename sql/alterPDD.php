<?php
/*
 * Install all fields to store PPD prices
 * per item (in order, invoices etc)
 */
class PDD {
    var $version = '2.3';
    var $description = 'Prompt Payment Discount';
    var $sql='alterPDD.sql';

    function install($pref, $force) {
       return true;
    }

    
    function pre_check($pref, $force) {
       return true;
    }
    function installed($pref) {
        check_table($pref, 'sales_order_details', 'ppd_price');
    }
}

$install = new PDD;
?>