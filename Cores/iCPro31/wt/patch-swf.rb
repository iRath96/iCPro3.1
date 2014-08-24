require 'zlib'

$type_table = Hash.new { |h,k| h[k] = "Unknown<#{k}>" }
$type_table[0] = "End"
$type_table[1] = "ShowFrameTag"
$type_table[9] = "SetBackgroundColor"
$type_table[12] = "DoAction"
$type_table[24] = "Protect"
$type_table[39] = "DefineSprite"
$type_table[56] = "ExportAssets"
$type_table[59] = "DoInitAction"
$type_table[69] = "FileAttribute"

module SWF
  class Header
    attr_accessor :compression, :version, :rect, :frame_rate, :frame_count
  end
  
  class Tag
    attr_accessor :type
    def self.create(type, data)
      # puts "A #{$type_table[type]} with #{data.length}B"
      case type
        when 59 then DoInitActionTag
        else         GenericTag
      end.new type, data
    end
    
    def to_s
      "<#{self.class.to_s}:#{data.length}>"
    end
  end
  
  class GenericTag < Tag
    attr_accessor :data
    def initialize(t,d); @type, @data = t,d; end
  end
  
  class DoInitActionTag < Tag
    attr_accessor :action_records
    def initialize(type, data)
      @type = type
      @action_records = []
      
      istream = InputStream.new data
      @action_records.push istream.read_action_record while !istream.eos?
    end
    
    def data
      ostream = OutputStream.new
      @action_records.each { |ar| ostream.write_action_record ar }
      ostream.string
    end
  end
  
  class ActionRecord
    attr_accessor :type
    def self.create(type, data)
      GenericActionRecord.new type, data
    end
  end
  
  class GenericActionRecord < ActionRecord
    attr_accessor :data
    def initialize(t,d); @type, @data = t,d; end
  end
  
  class InputStream
    attr_accessor :string, :position
    def initialize(string)
      @string = string
      @position = 0
    end
    
    def read_header # May only be called once!
      header = Header.new
      case @string[0]
        when 'F' then header.compression = :none
        when 'C' then header.compression = :zlib
        else raise "Unsupported compression: #{@string[0]}"
      end
      
      @position = 3
      header.version = read_u8
      
      if header.compression == :zlib
        @string = @string[0...8] + Zlib::Inflate.new.inflate(@string[8..-1])
      end
      
      file_size = read_u32 # the entire file size (uncompressed) - not used.
      
      last_pos = @position
      nbits = @string[@position].ord >> 3
      @position += ((nbits * 4 + 5) / 8.0).ceil
      header.rect = @string[last_pos...@position]
      
      header.frame_rate = read_u16
      header.frame_count = read_u16
      
      puts @position
      
      return header
    end
    
    def read_u8  ; @string[@position...@position+=1].unpack('C')[0]; end
    def read_u16 ; @string[@position...@position+=2].unpack('v')[0]; end
    def read_u32 ; @string[@position...@position+=4].unpack('V')[0]; end
    
    def read_string
      start_pos = @position
      @position += 1 while @string[@position].ord != 0
      @position += 1 # Skip the NULL-byte
      return @string[start_pos...@position-1] # but don't include it in the output
    end
    
    def read_tag
      first_pos = @position
      entry = read_u16
      type = entry >> 6
      length = entry & 0x3f # RECORDHEADER (short)
      length = read_u32 if length == 0x3f # RECORDHEADER (long)
      
      tag = Tag.create type, @string[@position...@position+=length]
      puts "InputStream: #{tag} at #{first_pos}"
      tag
    end
    
    def read_action_record
      type = read_u8
      length = type >= 0x80 ? read_u16 : 0
      data = @string[@position...@position+=length]
      
      ActionRecord.create type, data
    end
    
    def eos?; @position >= @string.length; end
  end
  
  class OutputStream
    def initialize
      @string = ""
      @header = nil
    end
    
    def write_header(v)
      @header = v
      @string = ""
      
      @compression = v.compression
      @string += case v.compression
        when :zlib then 'CWS'
        when :none then 'FWS'
      end
      
      write_u8 v.version
      write_u32 0 # Placeholder for length, written in OutputStream#length
      
      @string += v.rect
      
      write_u16 v.frame_rate
      write_u16 v.frame_count
    end
    
    def write_u8  (v); @string += [ v ].pack('C'); end
    def write_u16 (v); @string += [ v ].pack('v'); end
    def write_u32 (v); @string += [ v ].pack('V'); end
    
    def write_string(v); @string += v + "\x00"; end
    
    def write_tag(v)
      puts "OutputStream: #{v} at index #{@string.length}"
      data = v.data # Cache this
      write_u16 (v.type << 6) | [ 0x3f, data.length ].min
      write_u32 data.length if data.length >= 0x3f
      @string += data
    end
    
    def write_action_record(v)
      data = v.type >= 0x80 ? v.data : '' # Cache this and ensure that types below 0x80 do not have data
      
      write_u8 v.type
      write_u16 data.length if v.type >= 0x80
      @string += data
    end
    
    def string
      return @string unless @header # If we have a header, we also need to update the length and compress our output
      @string[0...4] + [ @string.length ].pack('V') + case @header.compression
        when :zlib then
          z = Zlib::Deflate.new
          dst = z.deflate @string[8..-1], Zlib::FINISH
          z.close
          
          dst
        else @string[8..-1]
      end
    end
  end
  
  class Document
    attr_accessor :filename, :header, :tags
    def initialize(filename)
      istream = InputStream.new File.open(filename, 'rb') { |f| f.read } # Read the input file in binary mode
      
      @filename = filename
      @header = istream.read_header
      @tags = []
      
      @tags.push(istream.read_tag) while !istream.eos?
    end
    
    def overwrite!; write @filename; end
    
    def write(filename)
      ostream = OutputStream.new
      ostream.write_header @header
      @tags.each { |tag| ostream.write_tag tag }
      
      File.open(filename, 'wb+') { |f| f.write ostream.string }
    end
  end
end

f = SWF::Document.new 'airtower.swf'
f.write 'airtower.out.swf'

exit

=begin
def read_tag(content, i)
  entry = content[i...i+=2].unpack('v')[0]
  type = entry >> 6
  length = entry & 0x3f # RECORDHEADER (short)
  length = content[i...i+=4].unpack('V')[0]  if length == 0x3f # RECORDHEADER (long)
  
  puts "#{i}: A #{$type_table[type]} with #{length.inspect}B"
  data = content[i...i+=length]
  return type, data, i
end

def read_string(content, i)
  start_i = i
  i += 1 while content[i].ord != 0
  return content[start_i...i], i + 1
end

def read_u16(content, i)
  return content[i...i+=2].unpack('v')[0], i
end

def parse_actionrecords(data)
  constant_pool = []
  
  i = 0
  while i < data.length
    type = data[i...i+=1].unpack('C')[0]
    length = type >= 0x80 ? data[i...i+=2].unpack('v')[0] : 0
    ar_data = data[i...i+=length]
    
    puts "  AR:0x#{type.to_s 16}"
    
    case type
      when 0x12 then
        puts "  . not"
      when 0x17 then
        puts "  . (pop)"
      when 0x1c then
        puts "  . get_variable"
      when 0x3c then
        puts "  . define_local"
      when 0x3d then
        puts "  . call_function"
      when 0x3e then
        puts "  . return"
      when 0x40 then
        puts "  . new object"
      when 0x47 then
        puts "  . add-2"
      when 0x4e then
        puts "  . get_member"
      when 0x4f then
        puts "  . set_member"
      when 0x52 then
        puts "  . call_method"
      when 0x88 then
        constant_count = ar_data[0...2].unpack('v')[0]
        j = 2; last_j = 2
        while j < ar_data.length
          if ar_data[j].ord == 0
            constant_pool.push ar_data[last_j...j]
            last_j = j + 1
          end
          j += 1
        end
        puts constant_pool.inspect
      when 0x8e then
        j = 0
        name, j = read_string ar_data, j
        num_params, j = read_u16 ar_data, j
        puts "  . #{ar_data.inspect}"
        puts "  . method #{name.inspect}, #{num_params} params"
      when 0x96 then
        push_type = ar_data[0...1].unpack('C')[0]
        if push_type == 4
          puts "  . push-reg #{ar_data[1...2].unpack('C')[0]}"
        elsif push_type == 7
          push_value = ar_data[1...5].unpack('V')[0]
          puts "  . push-uint32 #{push_value}"
        elsif push_type == 8
          push_addr = ar_data[1...2].unpack('C')[0]
          puts "  . push #{constant_pool[push_addr].inspect}"
        elsif push_type == 9
          push_addr = ar_data[1...3].unpack('v')[0]
          puts "  . lpush #{constant_pool[push_addr].inspect}"
        else
          puts "  . push-type #{push_type}"
        end
      when 0x9d then
        puts "  . if"
    end
  end
end

def read_flash(raw)
  header = raw[0...8]
  content = case header[0]
    when 'F' then raw[8...-1] # uncompressed
    when 'C' then Zlib::Inflate.new.inflate(raw[8...-1]) # Zlib
    else raise "Unsupported compression: #{header[0]}"
  end
  
  version = header[3].ord
  length = header[4...8].unpack('V')[0] # mind this is the uncompressed length
  
  raise "File length mismatches (#{length} specified but total file size is #{content.length + 8})" unless length == content.length + 8
  
  # Read the RECT record
  nbits = content[i = 0].ord >> 3
  i += ((nbits * 4 + 5) / 8.0).ceil
  
  frame_rate, frame_count = content[i...i+=4].unpack('v2')
  
  puts "#{frame_count} frames"
  while i < content.length
    type, data, i = read_tag content, i
    if type == 12
      parse_actionrecords data
    elsif type == 59
      parse_actionrecords data[2..-1]
    end
  end
end

read_flash File.open('airtower.swf', 'rb') { |f| f.read }
=end