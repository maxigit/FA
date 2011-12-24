<?php
function display_sales_textcart($title, $cart) {
	div_start('textcart');	
    display_heading($title);
		start_table(TABLESTYLE1, "width=100%");
    textarea_cells("", "name", "Mon cart", 80, 10, $title);
		end_table();
	div_end();
}
?>

