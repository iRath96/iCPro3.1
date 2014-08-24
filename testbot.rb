require 'socket'

s = TCPSocket.new '127.0.0.1', 9875
s.write "%xt%s%bo#lgn%TestUser1%29A7315C13D82946575633D60F56C549%\x00"
s.write "%xt%s%j#js%\x00"
s.write "%xt%s%j#jr%2002%220%160%320%\x00"
s.write "%xt%s%a#jt%15%206%\x00"
s.write "%xt%z%gz%206%\x00"
s.write "%xt%z%jz%206%\x00"

while true
  p = gets.strip
  s.write p + "\x00"
end