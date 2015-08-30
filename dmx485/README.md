dmx485
======

This consists of 3 parts.

1. libdmx485 is a shared library for reading and writing DMX over a RS485 USB interface such as the FTDIChip USB-COM485.
The library is a port of dmx485 written by Mike Bourgeous http://blog.mikebourgeous.com/2010/06/06/dmx485-a-dmx-512-interface-library/
but with a few minor changes.

2. dmx485out is a binary which provides dmx output to the specified interface. A shared memory segment and semaphore is setup to allow
other applications to directly modify the data being sent for each of up to 512 channels.

3. dmx485_test.php is a PHP program to test the writing of values to shared memory, in order to control what dmx485out is sending.


Example invocation of dmx485out:

	dmx485out /dev/serial/by-id/usb-FTDI_USB-COM485_Plus4_FTTB8J8W-if00-port0 0x0000ff30 0x0000ff40 512 0
	

Usage:

	Usage: dmx485out /dev/ttyUSBx SHM_KEY SEM_KEY Channels DEBUG_LEVEL
	SHM_KEY is a shared memory key and SEM_KEY is a semaphore key and both are specified as 32bit hex numbers e.g. 0xDEADBEEF
	Channels is the number of DMX channels to broadcast
	DEBUG_LEVEL: 0=None, 1=Setup Info Only, 2=Data Display
