from machine import Pin, I2C
from onewire import OneWire
from ds18x20 import DS18X20
import time
from time import sleep_ms

import network
import urequests
import ntptime
import ujson
from ubinascii import hexlify    # for sensor ID nice display
import gc #garbage collector
import secrets
from ssd1306 import SSD1306_I2C
from writer import Writer
import arial50 # Font to use

import gfx
import framebuf
import imagesRepoGH

#Sensor and screen pins
owPin=28
sclPin=17
sdaPin=16

# import random
#start garbage control
gc.enable()

counter = 0


#set up screen
WIDTH =128 
HEIGHT= 64
i2c=I2C(0,scl=Pin(sclPin),sda=Pin(sdaPin),freq=200000)
time.sleep_ms(1000)
oled = SSD1306_I2C(WIDTH,HEIGHT,i2c)
graphics = gfx.GFX(WIDTH, HEIGHT, oled.pixel)

ch1 = imagesRepoGH.ch1 #start-up image
ch2 = imagesRepoGH.ch2 
ch3 = imagesRepoGH.ch3


fbCh1 = framebuf.FrameBuffer(ch1, 128, 64, framebuf.MONO_HLSB)
fbCh2 = framebuf.FrameBuffer(ch2, 128, 64, framebuf.MONO_HLSB)
fbCh3 = framebuf.FrameBuffer(ch3, 128, 64, framebuf.MONO_HLSB)

oled.fill(0)
oled.blit(fbCh1, 8, 0)
oled.show()
time.sleep_ms(2000)
oled.fill(0)
oled.text("Connecting to", 0, 0)
graphics.fill_rect(0, 19, 100, 12, 1)
oled.show()
oled.text("the network...", 0, 20,0)

oled.show()

wri = Writer(oled, arial50)  # verbose = False to suppress console output

time.sleep(1)
#Connecting to wifi
ssid = secrets.ssid
password = secrets.password
wlan = network.WLAN(network.STA_IF)
wlan.active(True)
wlan.connect(ssid, password)

# Wait for connect or fail
max_wait = 10
while max_wait > 0:
  if wlan.status() < 0 or wlan.status() >= 3:
    break
  max_wait -= 1
  print('waiting for connection...')
  time.sleep(1)

oled.fill(0)
oled.show()

# Handle connection error
if wlan.status() != 3:
    connected = 0
    print('not connected')
    oled.text("Not connected", 0, 0)   
else:
    connected = 1
    print('connected')
    status = wlan.ifconfig()
    print( 'ip = ' + status[0] )
    oled.text("connected", 0, 0)
    oled.text(status[0], 0, 10)

oled.show()
oled.blit(fbCh3, 8, 0)
oled.show()
time.sleep_ms(2000)
oled.fill(0)  
#Set time from NTP
if connected == 1 :
    ntptime.settime()
    t = time.localtime()
    print("Date: %02d/%02d/%d" %(t[2],t[1],t[0]))
    print("Time: %02d:%02d:%02d" %(t[3],t[4],t[5]))
    oled.text("Date: %02d/%02d/%d" %(t[2],t[1],t[0]), 0, 0)
    oled.text("Time: %02d:%02d:%02d" %(t[3],t[4],t[5]), 0, 10)
    oled.show()
    time.sleep(3)
    oled.fill(0)
    oled.show()

#Set up DS18X20

ds = DS18X20(OneWire(Pin(owPin)))
sensors = ds.scan()

oled.fill(0)
oled.blit(fbCh2, 8, 0)
oled.show()
time.sleep_ms(2000)
oled.fill(0)

while True:
    try:
        ds.convert_temp()
        sleep_ms(900)     # mandatory pause to collect results
        for s in sensors:
            sensor=hexlify(s).decode()

        
            temp = ds.read_temp(s)
            print(sensor, ":", "%6.1f" % (ds.read_temp(s)))
            Writer.set_textpos(oled, 0, 0)  # In case a previous test has altered this
            wri.printstring(str( "%6.1f" % (ds.read_temp(s)))+"c")
            if connected == 0:
                oled.text("Not connected", 0, 0)
            oled.show()
            
            if connected == 1:
                t = time.localtime()
                print("%02d:%02d:%02d" %(t[3],t[4],t[5]))
                dsdate = ("%d-%02d-%02d" %(t[0], t[1], t[2]))
                dstime = ("%02d:%02d:%02d" %(t[3], t[4], t[5]))
                a = ("https://pico.axepat.com/temp_in.php?dt={}%20{}&temp={}&sensor={}".format(dsdate, dstime, temp, sensor))
                #print(a)      #Uncomment to see what is sent to the webpage
                try:
                    r = urequests.get(a)
                    time.sleep(3) 
                    #print(r.content) #Uncomment this to see what the webpage actually did
                    print("page status", r.status_code)
                    r.close()
                except Exception:
                    print("could not send data")
#                    connected = 0
#                     oled.fill(0)
#                     oled.show()
                    oled.text("could not send data", 0, 0)
                    oled.show()
                    time.sleep(3)
#                     oled.fill(0)
#                     oled.show()

        gc.collect()
        time.sleep(53)
        if connected == 0:
            counter = counter+1
        if counter >= 5:
            print("connection probs, rebooting")
            machine.reset()
        oled.fill(0)
        oled.show()
    except Exception as error:
        oled.text("bork! bork! bork", 0, 0)
        print("borked")
        print("An exception occurred:", type(error).__name__, "–", error)
        time.sleep(1)
        machine.reset()
    print("end of iteration")
    print()
    gc.collect()