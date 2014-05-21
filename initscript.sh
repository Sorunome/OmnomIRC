#!/bin/bash
echo "0" > /run/omnomirc_curid
chown sorunome:sorunome /run/omnomirc_curid
chmod 777 /run/omnomirc_curid
cd /home/sorunome/omnomirc
su -c "./MrOmnomIRC &> /dev/null &" sorunome
su -c "./MrTopicBot &> /dev/null &" sorunome
