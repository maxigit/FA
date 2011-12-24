<?php
function display_sales_textcart($title, $cart) {
	div_start('textcart');	
    display_heading($title);
		start_table(TABLESTYLE, "width=100%");
    textarea_cells("", "textcart", sales_cart_to_text($cart), 80, 10, $title);
		end_table();
    //submit_center("Name", "Value", true, "title", "ajaxsubmit");
    submit_center("ProcessTextCart", "Process");
	div_end();
}

//TODO create a class
function sales_cart_to_text($cart) {
  $text = "";
  $user_price_dec = user_price_dec();

  foreach ($cart->line_items as $item) {
    $dec = get_unit_dec($item->stock_id);
    $text .= sprintf("$item->stock_id %2.${dec}f %0.${user_price_dec}f %0.1f%%%s\n"
      ,$item->quantity
      ,$item->price
      ,$item->discount_percent
      ,$item->item_description ? " | $item->item_description" : ""
    );
  }

  return $text;
}

function process_textcart($cart, $textcart) {
  foreach (explode('\n', $textcart) as $line) {
    echo  "LIN: ".$line."<br/>";
  }
}
?>

