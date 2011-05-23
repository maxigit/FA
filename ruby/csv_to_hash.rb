# TODO: change name
require 'csv'
module CsvToHash

  def self.class_from_header(header)
    klass = Class.new(Array)
    header.each_with_index do |col, i|
      next unless col
      klass.class_eval <<-EOC
        def #{col}()
            self[#{i}]
        end
        def #{col}=(x)
          self[#{i}]=x
        end
      EOC
    end
    klass.class_eval <<-EOC
      def initialize(array=nil)
        array ||= []
        array = array[0, #{header.size}]
        array += [nil]*(#{ header.size } -array.size)
        super(array)
      end

      def header
        #{header.inspect}
      end
    EOC
    return klass
  end

  def self.arrays_to_accesses(header, arrays=nil)
    if arrays.nil?
      arrays = Array.new(header)
      header = arrays.shift
    end
    klass = class_from_header(header)
    arrays.map do |array|
      klass.new(array)
    end
  end

  def self.parse(str, options= nil)
    arrays = CSV::parse(str, options)
    arrays_to_accesses(arrays)
  end

  def self.headered(array)
    [array.first.header]+array
  end

  def self.map(str, options=nil, &block)
    headered(parse(str, options).map(&block))
  end

  def self.read(filename, options=nil)
    parse(open(filename).read)
  end


  def self.write(array, file, options=nil)
    headered(array).each do |row|
      file.puts CSV::generate_line(row, options)
    end
  end
end
