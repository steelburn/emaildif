<?php
/**
* This file reads from an LDIF file with struct as sampled below:-
* ---------------
* version: 1
*
* dn: cn=B0507001,ou=B105,ou=student,o=BJ
* changetype: modify
* givenName: Alen
* fullname: Alen Kiu Seng Yong
* sn: Kiu
* cn: B0507001
* ---------------
* ... and reformat to a new LDIF as sampled below:-
* ---------------
* version: 1
*
* dn: cn=B0507001,ou=B105,ou=student,o=BJ
* changetype: modify
* add: mail
* mail: Alen_Kiu_B105@student.imu.edu.my
* ---------------
*
* author = Mohd Khairulnizam Hasan
* email = khairulnizam@zen.com.my and/or steelburn@gmail.com
* creationdate = July 20, 2005
* lastmodify = August 01, 2005
*
* SPECIAL DISCLAIMER:
* -----------
* The script is provided as-is. By using this script, the author
* disclaims any form of liabilibity that may be caused directly
* or indirectly by this script.
* By general consensus, it is known that this script
* does not kill your pet, electrocute your boss, or ruin your love
* life. If it does, you've been warned.
* ----------
* END OF SPECIAL DISCLAIMER
**/
?>
<html>
<head>
<title>LDIF reformatter</title>
<style>
body {
	margin-top: 32px;
	background: #ffffff;
	font-family: arial;
	}
table {
	align: centre;
	background: #ffffee;
	}
.head {
	font-size: large;
	color: #eeffff;
	background: #000000;
	}
</style>
</head>
<body>
<!-- input form -->
<form enctype="multipart/form-data" method="POST">
<table align="center">
<tr><td colspan=2 class="head"><strong>IMU LDIF reformatter</strong></td></tr>
<tr><td colspan=2><em>Note: Mandatory fields: <strong>dn, givenName, sn</strong><br>
				This script will not process records without those mandatory fields.</em></td></tr>
<tr><td>Original LDIF file:</td><td><input type="file" name="inputfile"></td></tr>
<tr><td>givenName reformat type:</td><td>
		<select name="givenNameReformat">
			<option value="nospace" default>Remove spaces</option>
			<option value="_">Change spaces to underscores</option>
			<option value=".">Change spaces to dots</option>
			<option value="noformat">Leave intact</option>
		</select>
	</td></tr>
<tr><td>sn reformat type:</td><td>
		<select name="snReformat">
			<option value="nospace" default>Remove spaces</option>
			<option value="_">Change spaces to underscores</option>
			<option value=".">Change spaces to dots</option>
			<option value="noformat">Leave intact</option>
		</select>
	</td></tr>
<tr><td>Extension value:</td><td><input type="text" name="extension" value=""></td></tr>
<tr><td>Intelligent Extension Feature</td><td><input type="checkbox" name="intel" value="1" checked></td></tr>
<tr><td>givenName+sn+Extension connector:</td><td>
		<select name="connector">
			<option value="_" default>Underscore (_)</option>
			<option value="+">Plus (+)</option>
			<option value=".">Dot (.)</option>
			<option value="nospace">No Connector</option>
		</select>
	</td></tr>
<tr><td>Domain (e.g: student.imu.edu.my):</td><td><input type="text" name="domain" value="student.imu.edu.my"></td></tr>
<!-- // extras -->
<tr><td colspan="2">
	<table border="1px" width="100%">
	<tr><thead colspan="2">Extra options:</thead></tr>
	<tr><td>Do character removal?</td>
		<td><input type="radio" name="chardelete" value="1" checked>Yes
			<input type="radio" name="chardelete" value="0">No
		</td></tr>
	<tr><td>Character set to remove</td><td><input type="text" name="delcharset" value="@'-.,\/"></td></tr>
	<tr><td>Tab-delimited word replacement table</td><td><input type="file" name="wordfile"></td></tr>
	</table>
</td></tr>
<!-- // end extras -->
<tr><td colspan=2><input type="submit" value="Process"></td></tr>
</table>
<input type="hidden" name="step" value="read">
</form>

<?php
function csvldflist() {
//list *.ldf and *.csv for download/deletion:-
$ls = scandir('./');
echo "<hr>\n";
echo "<table>\n";
foreach ($ls as $item)
	{
	if (substr($item,strlen($item)-4)==".ldf") { echo "<tr><td><a href=\"$item\">$item</a></td><td>[<a href=\"?step=del&fn=$item\">Del</a>]</td></tr>\n"; };
	if (substr($item,strlen($item)-4)==".csv") { echo "<tr><td><a href=\"$item\">$item</a><br></td><td>[<a href=\"?step=del&fn=$item\">Del</a>]</td></tr>\n"; };
	}
echo "</table>";
}; //end of csvldflist();

/**
* function delcharset($str as string, $charset as string)
* return string
* Process $str and do removal of characters specified in $charset
*/
function delcharset($str, $charset) {
$chars=str_split($charset);	//change $charset into a string of characters
foreach ($chars as $char)
	{
	$str = str_replace($char,"",$str);
	};
return $str;
};	//end of delcharset();

/**
* function wordreplace($str as string, $tabstring as string)
* return string
* Do word replacement, based on tab-delimited word replacement table supplied by the user.
*/
function wordreplace($str,$tabstring) {
$tabline = explode("\n",trim($tabstring));
foreach($tabline as $line)
	{
	$tabdata[]=explode("\t",trim($line));
	};
foreach($tabdata as $atom)
	{
	$str=str_replace($atom[0],$atom[1],$str);
	};
	//debug: echo $str;
return $str;
};	//end of wordreplace();


//if ($_REQUEST['step']=='') { csvldflist(); exit(); };	//stop the script here if there's no file to process.
if ($_REQUEST['step']=='del') //do file deletion and stop script if necessary.
	{
	if ((is_file($_REQUEST['fn'])) && ((substr($_REQUEST['fn'],strlen($_REQUEST['fn'])-4)==".ldf") || (substr($_REQUEST['fn'],strlen($_REQUEST['fn'])-4)==".csv")))
		{
		if (unlink($_REQUEST['fn']))
			{
			echo "File ".$_REQUEST['fn']." has been deleted succesfully.<br>\n";
			}
			else
			{
			echo "Error: Invalid filename/File does not exist.";
			};
		}
	csvldflist();
	exit();
	};
// check if tab-delimited word replacement table is there,
// if so, populate $wordtable, otherwise empty it.
if (trim($_FILES['wordfile']['name'])!="")
	//if (is_file($_FILES['wordfile']['tmp_name'])
		$wordtable = file_get_contents($_FILES['wordfile']['tmp_name']);

if (!($file = fopen($_FILES['inputfile']['tmp_name'],'r')))
	exit();
//reset counter
$counter = 0;
while (!feof($file)) {
	$buffer=trim(fgets($file));
	if ((substr($buffer,0,1)=="#") || (trim($buffer)==""))
		{ //do nothing.
		}
	elseif (substr($buffer,0,8)=="version:")
		{
		$ldif_version = substr($buffer,strlen($buffer)-1);
		}
	else {
		// store fetched values into arrays and do some processing:
		$data = substr($buffer,strpos($buffer,": ")+2);
		if (strlen(strstr($buffer."\n","dn: "))) { $dcount++; $record["dn"][$dcount]= $data; };
		if (strlen(strstr($buffer."\n","cn: "))) $record["cn"][$dcount]= $data;
		if (strlen(strstr($buffer."\n","sn: ")))
			{
			if (trim($_FILES['wordfile']['name'])!="")
				$data=wordreplace($data,$wordtable);
			if ($_REQUEST["snReformat"]=="nospace") $data=str_replace(" ","",$data);
			if ($_REQUEST["snReformat"]=="_") $data=str_replace(" ","_",$data);
			if ($_REQUEST["snReformat"]==".") $data=str_replace(" ",".",$data);
			if ($_REQUEST["snReformat"]=="noformat") $data=str_replace(" "," ",$data);
			if (($_REQUEST["chardelete"]=="1") && (strlen(trim($_REQUEST["delcharset"]))>0)) $data=delcharset($data,trim($_REQUEST["delcharset"]));
			$record["sn"][$dcount]= $data;
			};
		if (strlen(strstr($buffer."\n","changetype: "))) $record["changetype"][$dcount]= $data;
		if (strlen(strstr($buffer."\n","givenName: ")))
			{
			if (trim($_FILES['wordfile']['name'])!="")
				$data=wordreplace($data,$wordtable);
			//debug:	echo "<pre>$wordtable</pre>";
			//debug:	echo $_FILES['wordfile']['name'].": $data<br>";
			if ($_REQUEST["givenNameReformat"]=="nospace") $data=str_replace(" ","",$data);
			if ($_REQUEST["givenNameReformat"]=="_") $data=str_replace(" ","_",$data);
			if ($_REQUEST["givenNameReformat"]==".") $data=str_replace(" ",".",$data);
			if ($_REQUEST["givenNameReformat"]=="noformat") $data=str_replace(" "," ",$data);
			if (($_REQUEST["chardelete"]=="1") && (strlen(trim($_REQUEST["delcharset"]))>0)) $data=delcharset($data,trim($_REQUEST["delcharset"]));
			//debug: echo $_REQUEST["chardelete"].",".$_REQUEST["delcharset"].",".$data."<br>";
			$record["givenName"][$dcount]= $data;
			};
		if (strlen(strstr($buffer."\n","fullname: "))) $record["fullname"][$dcount]= $data;
		}; //end if
	}; //eof reached
fclose($file);
//check for setting a little bit:
if ($_REQUEST["connector"]=="nospace") { $connector=""; } else { $connector=$_REQUEST["connector"]; };
$extension=$_REQUEST["extension"];
//do some processing:
$filename = "mail-".time().".ldf";
$file = fopen($filename,"w");
$filename2 = "text-".time().".csv";
$csv = fopen($filename2,"w");
$filename3 = "nds-".time().".ldf";
$nds = fopen($filename3,"w");
fwrite($file,"#LDIF processed by emaildif.php script. Edition: 2005, Ref: khairulnizam@zen.com.my\n");
fwrite($file,"version: ".$ldif_version."\n\n");
fwrite($csv,"\"#\",\"cn\",\"givenName\",\"sn\",\"email\"\n");
fwrite($nds,"#LDIF processed by emaildif.php script. Edition: 2005, Ref: khairulnizam@zen.com.my\n");
fwrite($nds,"#NDS alias creation.\n");
fwrite($nds,"version: ".$ldif_version."\n\n");

while ($counter<count($record["dn"]))
	{
	if ((strlen(trim($record["givenName"][$counter]))>0) && (strlen(trim($record["sn"][$counter]))>0))
		{
		$processcount++;
		fwrite($file,"dn: ".$record["dn"][$counter]."\n");
		fwrite($file,"changetype: modify"."\n");
		fwrite($file,"replace: mail"."\n");
		if ($_REQUEST["intel"]=="1") 	// intelligent extension option selected
			{
			$p = strpos($record["dn"][$counter],",ou=");
			$s = substr($record["dn"][$counter],$p+4);
			$p = strpos($s,",");
			$s = substr($s,0,$p);
			$extension = $s;
			};
		if (strlen(trim($extension))>0) $mailprefix=$record["givenName"][$counter].$connector.$record["sn"][$counter].$connector.$extension;
		else $mailprefix=$record["givenName"][$counter].$connector.$record["sn"][$counter];
		$email = $mailprefix."@".trim($_REQUEST["domain"]);
		fwrite($file,"mail: ".$email."\n");
		fwrite($file,"#givenName=".$record["givenName"][$counter]." ,sn=".$record["sn"][$counter]."\n");
		fwrite($file,"\n");
		fwrite($csv,"\"".$processcount."\",\"".$record["cn"][$counter]."\",\"".$record["givenName"][$counter]."\",\"".$record["sn"][$counter]."\",\"".$email."\"\n");
		$nds_dn = "dn: cn=$mailprefix".substr($record["dn"][$counter],strpos($record["dn"][$counter],","));
		$nds_aliased = "aliasedObjectName: ".substr($record["dn"][$counter],strpos($record["dn"][$counter],":"));
		fwrite($nds,$nds_dn."\n");
		fwrite($nds,"changetype: add\n");
		fwrite($nds,"objectClass: alias\n");
		fwrite($nds,"objectClass: top\n");
		fwrite($nds,"cn: $mailprefix\n");
		fwrite($nds,$nds_aliased."\n");
		fwrite($nds,"\n");
		};	//end if
	$counter++;
	};	//end while (count)
// cleanup. close open files:-
fclose($file);
fclose($csv);
fclose($nds);
echo "<hr>$counter records processed.<br> $processcount records goes in.";
echo "<br>Done. You may get the output <a href=\"$filename\">here.</a>";
echo "<br> CSV formatted file can be found <a href=\"$filename2\">here</a>.";
echo "<br> LDF formatted file for NDS aliasing can be found <a href=\"$filename3\">here</a>.";

csvldflist();


?>
</body></html>