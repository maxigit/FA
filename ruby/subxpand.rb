#!/usr/bin/ruby
require 'csv'

variants = CSV::parse(open(ARGV[0]).read)

regs = variants.shift.map { |r| /#{r}/}

$stdin.each_line do |line|
  puts line
  variants.each do |var|
    new_line = regs.zip(var).inject (line) do |l, (r,v)|
      v ||= ""
      if v =~ /^\$\((.*)\)/
        eval "\"#{l.gsub(r,"\#{#{$1}}")}\""
      else
      l.gsub(r,v)
      end
    end
    puts new_line unless line == new_line
  end


end


