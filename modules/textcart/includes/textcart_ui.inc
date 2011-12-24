<?php
function display_sales_textcart($title, $cart) {
	div_start('textcart');	
    display_heading($title);
		start_table(TABLESTYLE1, "width=100%");
    textarea_cells("", "name", sales_cart_to_text($cart), 80, 10, $title);
		end_table();
	div_end();
}

//TODO create a class
function sales_cart_to_text($cart) {
  $text = "";

  foreach ($cart->line_items as $item) {
    $text .= sprintf("$item->stock_id %2d %0.2f %0.1f%%%s\n"
      ,$item->quantity
      ,$item->price
      ,$item->discount_percent
      ,$item->item_description ? " | $item->item_description" : ""
    );
  }

  return $text;
}
?>

