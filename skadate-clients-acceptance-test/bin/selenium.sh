#!/bin/bash

unameOut="$(uname -s)"
case "${unameOut}" in
    Linux*)     machine=Linux;;
    Darwin*)    machine=Mac;;
    CYGWIN*)    machine=Cygwin;;
    MINGW*)     machine=MinGw;;
    *)          machine="UNKNOWN:${unameOut}"
esac

if [ ${machine} = "Mac" ]; then
    java -Dwebdriver.chrome.driver=./selenium/chromedriver_mac -jar ./selenium/selenium-server-standalone.jar -port 4444
else
    java -Dwebdriver.chrome.driver=./selenium/chromedriver_linux -jar ./selenium/selenium-server-standalone.jar -port 4444
fi
