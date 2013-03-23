<?PHP	
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45

    This file is part of OmnomIRC.

    OmnomIRC is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OmnomIRC is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.
*/
	include("/path/to/omnomirc_www/folder/Source/sign.php");
	//Set the channels here.
	//$channels[]=Array("#channel",AlwaysShow);
	$channels=Array();
	$channels[]=Array("#chan1",true);//Default chan, 'cause it's the first in the array.
	$channels[]=Array("#chan2",true);
	$exChans=Array();
	$exChans[]=Array("#chan",false);
?>
<?PHP
	
	if (!isset($IRCBOT))
	{
		//Output for Javascript.
		header('Content-type: text/javascript');
		$chanStr = "";
		foreach ($channels as $chan)
			if ($chan[1])$chanStr = $chanStr . '"'.base64_url_encode($chan[0]).'",';
		$chanStr = substr($chanStr,0,-1);
		
		echo "var channels=[";
		echo $chanStr;
		echo "];";
		
		$exChanStr = "";
		foreach ($channels as $chan)
			if (!$chan[1])$exChanStr = $exChanStr . '"'.base64_url_encode($chan[0]).'",';
		$exChanStr = substr($exChanStr,0,-1);
		
		echo "var exChannels=[";
		echo $exChanStr;
		echo "];";
	}
	else
	{
		//Output for PHP
		$chanStr = "";
		foreach ($exChans as $chan)
			$channels[] = $chan;
		foreach ($channels as $chan)
			$chanStr = $chanStr .$chan[0].',';
		
		$chanStr = substr($chanStr,0,-1);
	}
?>


