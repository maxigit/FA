<?php
function display_sales_textcart($title, $cart) {
	div_start('textcart');	
    display_heading($title);
		start_table(TABLESTYLE, "width=100%");
    textarea_cells("", "textcart", sales_cart_to_text($cart), 80, 10, $title);
		end_table();
    //submit_center("Name", "Value", true, "title", "ajaxsubmit");
    submit_center_first("ReplaceTextCart", "Replace");
    submit("ModifyTextCart", "Modify");
    submit_center_last("CancelTextCart", "Cancel");
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
  ,'=' => UPDATE_MODE
  ,'-' => DELETE_MODE
  ,'' => null
);
function parse_sales_line($line) {
  global $mode_map;
  echo "parsing : $line<br/>";
  // TODO create global variable
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
  if (!preg_match('/^([+\-=])?\s*([^\s,;]+)(?:([^|]*)(?:\|\s*(.*))?)?/', $line, $matches)) {
    display_error("error parsing '$line'");
    return;
  }
    $mode = $mode_map[$matches[1]];
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
    elseif (preg_match('/^(\d+(?:.\d+)?)%$/', $field, $matches)) {
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
    "mode" => $mode
    ,"stock_code" => $stock_code
    ,"quantity" => $quantity
    ,"price" => $price
    ,"discount" => $discount/100.0
    ,"description" => $description
  );

}

function process_textcart($cart, $textcart) {
  foreach (explode("\n", $textcart) as $line) {
    $attributes = parse_sales_line($line);
    if (!$attributes)
      continue;

    $stock_code = $attributes["stock_code"];
    $mode = $attributes['mode'];
    $quantity = $attributes["quantity"];
    $price = $attributes["price"];
    $discount = $attributes["discount"];
    $description = $attributes["description"];

  echo "Stock Code : $stock_code</br><ul>";
  echo "<li>mode : $mode</li>";
  echo "<li>quantity : $quantity</li>"   ;
  echo "<li>price : $price</li>";
  echo "<li>discount : $discount</li>";
  echo "<li>description : $description</li>";
  echo "</ul>";

  // Checking that product exists, to not process dodgy one
  $kit = get_item_kit($stock_code);
  $number = db_num_rows($kit);
  if ($number == 0) {
    display_error("Product '$stock_code' doesn't exist");
    display_error("Line '$line' skipped");
  }
  db_free_result($kit);

    switch ($mode) {
    case UPDATE_MODE:
      // we modifidy the first element, we can't use the attributes as criteria as 
      // they are the new value
      $line_no = find_line_number($cart, $stock_code);
      if (!isset($line_no)) { return; }
      $cart->update_cart_item($line_no, $quantity, $price, $discount, $description);
      break;
    case DELETE_MODE:
      $line_no = find_line_number($cart, $stock_code, $quantity, $price, $discount, $description);
        echo "deleting $line_no";
      if (!isset($line_no)) { return; }
      $cart->remove_from_cart($line_no);
      break;
    default: // INSERT
      if (!$quantity) $quantity = 1;
      if (!$price) $price = get_kit_price($stock_code, $cart->customer_currency, $cart->sales_type, $cart->price_factor);
      add_to_order($cart, $stock_code, $quantity, $price, $discount, $description );
    }
  }
}

// return if criteria is not set or value match it
function match_criteria($value, $criteria) {
 return ! ( isset($criteria) && $value == $criteria);
}

// Try to find the first line matching the criteria
function find_line_number($cart, $stock_code, $quantity=null, $price=null, $discount=null,  $description=null) {
  foreach ($cart->line_items as $line_no => $line) {
    if ($line->stock_id == $stock_code 
      //&& match_criteria($line->quantity, $quantity)
      //&& match_criteria($line->price , $price)
      //&& match_criteria($line->discount_percent , $discount)
      //&& match_criteria($line->item_description, $description)
    ) {
        return $line_no;

      }
  }
  display_error("Can't find line details with ".join(", ",array($stock_code, $quantity, $price, $discount, $description)));
  return null;
}
