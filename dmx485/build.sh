#!/bin/bash

mkdir build 2>/dev/null
cd build
cmake ..
make
echo "Now we are going to install the libraries and binaries. Need to sudo for this!"
sudo make install
sudo ldconfig
