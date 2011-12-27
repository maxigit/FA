,,<?php
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
    $text .= sprintf("$item->stock_id %2.${dec}f %0.${user_price_dec}f %0.1f%%%s\r\n"
      ,$item->quantity
      ,$item->price
      ,$item->discount_percent*100
      ,$item->item_description ? " | $item->item_description" : ""
    );
  }

  return $text;
}

define ('INSERT_MODE', 0);
define ('UPDATE_MODE', 1);
define ('DELETE_MODE', 2);
$mode_map = array (
  '+' => INSERT_MODE
  ,'!' => UPDATE_MODE
  ,'-' => DELETE_MODE
);
function parse_sales_line($line) {
  echo "parsing : $line<br/>";
  $line = trim($line);
  $stock_code = "";
  $quantity = "";
  $price = "";
  $discount = "";
  $description = "";


  if(!$line) {
    // empty line, skip
    return;
  }
  // extract SKU and descriptions
  if (!preg_match('/^([+\-!])?([^\s,;]+)(?:([^|]*)(?:\|\s*(.*))?)?/', $line, $matches)) {
    display_error("error parsing '$line'");
    return;
  }
    $mode = $ModeMap[$matches[1]];
    $stock_code =  $matches[2];
    $fields_str = $matches[3];
    $description = $matches[4];

  $fields = preg_split("/[\s,;]+/", $fields_str);

    // TODO refactore using an array
  foreach ($fields as $field) {
    # quantity are integer or preceeded by a +
    if (preg_match('/^(\d+)$/', $field, $matches)) {
      if($quantity) {
        display_error("quantity already set for line '$line'");
        return;
      };
      $quantity = $matches[1];
    }
    elseif (preg_match('/^\+(\d*\.\d+)$/', $field, $matches)) {
      if($quantity) {
        display_error("quantity already set for line '$line'");
        return;
      };
      $quantity = $matches[1];
    }
    # price are float or integer preceeded by a $
    elseif (preg_match('/^(?:(\d+\.\d+)|\$(\d+(?:\.\d+)?))$/', $field, $matches)) {
      if($price) {
        display_error("price already set for line '$line'");
        return;
      };
      $price = $matches[1] . $matches[2];//  ack to get first match or the second one
    }
    elseif (preg_match('/^(\d+(?:.\d+))%$/', $field, $matches)) {
      if($discount) {
        display_error("discount already set for line '$line'");
        return;
      };
      $discount = $matches[1];
    }

    }
  echo "Stock Code : $stock_code</br><ul>";
  echo "<li>quantity : $quantity</li>"   ;
  echo "<li>price : $price</li>";
  echo "<li>discount : $discount</li>";
  echo "<li>description : $description</li>";
  echo "</ul>";

  return array(
    "mode" => "insert"
    ,"stock_code" => stock_code
    ,"quantity" => quantity
    ,"price" => price
    ,"discount" => discount
    ,"description" => description
  );

}

function process_textcart($cart, $textcart) {
  foreach (explode("\n", $textcart) as $line) {
    parse_sales_line($line);
  }
}
?>

