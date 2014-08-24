require 'socket'
require 'io/wait'

$CLIENT_DELAY_TIME = 0.0

# TODO: Switch servers depending on CP-username
$swap_out = {
  [ '204.75.167.165' , 3724 ] => [ '127.0.0.1', 3724 ],
  [ '204.75.167.11'  , 9875 ] => [ '127.0.0.1', 9875 ]
}

$last_chan = nil
$swap_out = {} if ARGV.include? 'pass'
puts $swap_out.inspect

$CREATE_LOG = ARGV.include? 'log'

class Channel
  attr_accessor :ip, :port
  attr_accessor :client, :server
  attr_accessor :buffers
  
  def initialize(ip, port, rip, rport)
    @ip, @port = ip, port
    @buffers = Hash.new ""
    
    return unless self.of_interest?
    return unless $CREATE_LOG
    
    @log = File.open(Time.now.to_f.to_s + '.log', 'w+')
    @log.puts "Connected to #{ip}:#{port} (#{rip}:#{rport}) with client-delay #{$CLIENT_DELAY_TIME}s."
  end
  
  def push(is_local, data, injected = false)
    return unless self.of_interest?
    
    *packets, @buffers[is_local] = (@buffers[is_local] + data).split("\x00", -1)
    packets.each do |packet|
      raw_packet = packet.clone
      
      if packet.match /^%xt%/
        if is_local
          packet.gsub!(/^%xt%(.+?)%(.+?)%/) do |match|
            colors = [ 0, 0, 92, 95, 0 ]
            match.split('%', -1).map.with_index { |v,i| v.empty? ? '' : "\033[#{colors[i]}m#{v}\033[0m" } * '%'
          end
        else
          packet.gsub!(/^%xt%(.+?)%/) do |match|
            colors = [ 0, 0, 96, 0 ]
            match.split('%', -1).map.with_index { |v,i| v.empty? ? '' : "\033[#{colors[i]}m#{v}\033[0m" } * '%'
          end
        end
      end
      
      puts "#{is_local ? 'C' : 'S'}: " + packet
      
      next unless $CREATE_LOG
      @log.puts "(injecting packet below)" if injected
      @log.puts "#{is_local ? 'C' : 'S'} " + raw_packet # TODO: Timestamps?
      $last_chan = self
    end
  end
  
  def comment(line)
    return if line == ''
    if line[0] == '*' # Inject packet.
      is_local = line[1].upcase == 'C'
      packet = line[2..-1] + "\x00"
      
      socket = is_local ? server : client # Whom to send this to?
      socket.send packet, 0
      
      self.push is_local, packet, true
    else
      puts "Comment #{line.inspect} written."
      @log.puts "X: #{line}."
    end
  end
  
  def close(is_local)
    return unless self.of_interest? and $CREATE_LOG
    @log.puts "#{is_local ? 'C' : 'S'} closed the connection."
  end
  
  def of_interest?
    [ 3724, 6112, 9875 ].include? @port
  end
end

def blocked_recv(socket, length)
  data = socket.recv(length)
  data += socket.recv(length - data.length) while data.length < length
  return data
end

Thread.new do
  $server = TCPServer.new '127.0.0.1', 9050
  while (cl = $server.accept)
    Thread.new(cl) do |client|
      begin
        version, methodCount = blocked_recv(client, 2).unpack("C2")
        methods = blocked_recv(client, methodCount).unpack("C*")
        
        raise "Version mismatch: #{version}" unless version == 5
        raise "Unsupported methods: #{methods.inspect}" unless methods == [ 0 ]
        
        client.send [ 5, 0 ].pack("CC"), 0
        
        packet = blocked_recv(client, 4)
        version, cmdConnect, zero, type = packet.unpack("C4")
        
        raise "Version mismatch: #{version}" unless version == 5
        raise "Command mismatch: #{cmdConnect}" unless cmdConnect == 1
        raise "Zero was not 0: #{zero}" unless zero == 0
        
        success = true
        begin
          socket = case type
            when 1 then # IPv4
              packet = blocked_recv(client, 6)
              
              ip = (rawIP = packet[0...4]).chars.map { |c| c.ord } * '.'
              port = packet[4...6].unpack('n')[0]
              
              rip, rport = ($swap_out[[ip,port]] or [ ip, port ])
              
              conName = "#{ip}:#{port} (#{rip}:#{rport})"
              puts "Connecting to #{conName}"
              
              TCPSocket.new rip, rport
            when 3 then # Domain
              raise "Domain is unsupported."
            when 4 then # IPv6
              raise "IPv6 is unsupported."
            else
              raise "Unknown type: #{type}"
          end
        rescue => e
          success = false
          puts "(could not connect to #{ip}:#{port})"
        end
        
        raise "socket = nil, success = true" if socket == nil and success == true
        raise "rawIP.length != 4: #{rawIP.length}" unless rawIP.length == 4
        
        client.send [ 5, success ? 0 : 1, 0, 1 ].pack('C*'), 0
        client.send rawIP, 0
        client.send [ port ].pack('n'), 0
        
        next unless success
        
        chan = Channel.new ip, port, rip, rport
        chan.client = client
        chan.server = socket
        
        pbuf = []
        pbufLastSend = Time.now - $CLIENT_DELAY_TIME
        
        while true
          begin
            read, write, error = IO.select([ socket, client ], [], [], *($CLIENT_DELAY_TIME > 0 ? [ $CLIENT_DELAY_TIME / 2 ] : []))
            (read or []).each do |c|
              packet = c.recv(8192)
              other = c == client ? socket : client
              
              if packet == nil or packet.length == 0
                puts "Connection to #{conName} closed."
                other.close
                chan.close c == client
                
                raise "Session closed"
              end
              
              if c == client and $CLIENT_DELAY_TIME > 0 and chan.of_interest?
              ##puts "(delaying #{packet})"
                pbuf.push packet
              else
                chan.push c == client, packet
                other.write packet
                other.flush
              end
            end
            
            if pbuf.length > 0 and (Time.now - pbufLastSend) >= $CLIENT_DELAY_TIME
              packet = pbuf.shift
              pbufLastSend = Time.now
              
              chan.push true, packet
              socket.write packet
              socket.flush
            end
          rescue => e
            #puts e.inspect
            #puts e.backtrace.inspect
            
            client.close rescue nil
            socket.close rescue nil
            
            break
          end
        end
      rescue => e
        #puts e.inspect
        #puts e.backtrace.inspect
        
        client.close rescue nil
      end
    end
  end
end

while true
  line = STDIN.gets.strip
  if $last_chan
    $last_chan.comment line
  else
    puts "(could not comment: no last channel available)"
  end
end