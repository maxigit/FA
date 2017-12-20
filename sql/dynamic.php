<?php
/*
 * Install all fields to store DYNAMIC prices
 * per item (in order, invoices etc)
 */
class DYNAMIC {
    var $version = '2.3.1';
    var $description = 'Now or Never customer default';
    var $sql='alter_dynamic.sql';

    function install($pref, $force) {
       return true;
    }

    
    function pre_check($pref, $force) {
       return true;
    }
    function installed($pref) {
        return check_table($pref, 'debtors_master', 'now_or_never') == 0;
    }
}

$install = new DYNAMIC;
?>
