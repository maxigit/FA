#!/usr/bin/ruby
require 'csv_to_hash'

items = []
CsvToHash::parse($stdin.readlines.join()).each do |item|
  variants.map do |variant|
    item.stock_id = 
    item << item
  end
end

CsvToHash::write(items, $stdout)


