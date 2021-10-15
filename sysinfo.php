<?php
  header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	header("Pragma: no-cache");

	function human_filesize($bytes, $decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}
	
	$current_time = exec("date +'%d %b %Y<br />%T %Z'");
	$frequency = exec("cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq") / 1000;
	$processor = str_replace("-compatible processor", "", explode(": ", exec("cat /proc/cpuinfo | grep model"))[1]);
	$cpu_temperature = round(exec("cat /sys/class/thermal/thermal_zone0/temp ") / 1000, 1);
	$model = trim(exec('cat /sys/firmware/devicetree/base/model'));
	//$RX = exec("ifconfig eth0 | grep 'RX bytes'| cut -d: -f2 | cut -d' ' -f1");
	//$TX = exec("ifconfig eth0 | grep 'TX bytes'| cut -d: -f3 | cut -d' ' -f1");
	list($system, $host, $kernel) = preg_split('/\s/', exec("uname -a"), 4);
	$host = exec('hostname -f');;
	
	//Uptime
	$uptime_array = explode(" ", exec("cat /proc/uptime"));
	$seconds = round($uptime_array[0], 0);
	$minutes = $seconds / 60;
	$hours = $minutes / 60;
	$days = floor($hours / 24);
	$hours = sprintf('%02d', floor($hours - ($days * 24)));
	$minutes = sprintf('%02d', floor($minutes - ($days * 24 * 60) - ($hours * 60)));
	if ($days == 0):
		$uptime = $hours . ":" .  $minutes . " (hh:mm)";
	elseif($days == 1):
		$uptime = $days . " day, " .  $hours . ":" .  $minutes . " (hh:mm)";
	else:
		$uptime = $days . " days, " .  $hours . ":" .  $minutes . " (hh:mm)";
	endif;
	
	// Load averages
	$loadavg = file("/proc/loadavg");
	if (is_array($loadavg)) {
		$loadaverages = strtok($loadavg[0], " ");
		for ($i = 0; $i < 2; $i++) {
			$retval = strtok(" ");
			if ($retval === FALSE) break; else $loadaverages .= " " . $retval;
		}
	}
	
	//Memory Utilisation
	$meminfo = file("/proc/meminfo");
	for ($i = 0; $i < count($meminfo); $i++)
	{
		list($item, $data) = preg_split('/:/', $meminfo[$i], 2);
		$item = trim(chop($item));
		$data = intval(preg_replace("/[^0-9]/", "", trim(chop($data)))); //Remove non numeric characters
		switch($item)
		{
			case "MemTotal": $total_mem = $data; break;
			case "MemFree": $free_mem = $data; break;
			case "SwapTotal": $total_swap = $data; break;
			case "SwapFree": $free_swap = $data; break;
			case "Buffers": $buffer_mem = $data; break;
			case "Cached": $cache_mem = $data; break;
			default: break;
		}
	}
	$used_mem = $total_mem - $free_mem;
	$used_swap = $total_swap - $free_swap;
	$percent_free = round(($free_mem / $total_mem) * 100);
	$percent_used = round(($used_mem / $total_mem) * 100);
	if ($total_swap) {
	    $percent_swap = round((($total_swap - $free_swap ) / $total_swap) * 100);
	    $percent_swap_free = round(($free_swap / $total_swap) * 100);
	}else{
	    $percent_swap = 0;
	    $percent_swap_free = 0;
	}
	$percent_buff = round(($buffer_mem / $total_mem) * 100);
	$percent_cach = round(($cache_mem / $total_mem) * 100);
	$used_mem = human_filesize($used_mem*1024,0);
	$used_swap = human_filesize($used_swap*1024,0);
	$total_mem = human_filesize($total_mem*1024,0);
	$free_mem = human_filesize($free_mem*1024,0);
	$total_swap = human_filesize($total_swap*1024,0);
	$free_swap = human_filesize($free_swap*1024,0);
	$buffer_mem = human_filesize($buffer_mem*1024,0);
	$cache_mem = human_filesize($cache_mem*1024,0);
	//Disk space check, with sizes reported in kB
	exec("df -T -l -BKB -x tmpfs -x devtmpfs -x rootfs", $diskfree);
	$count = 1;
	while ($count < sizeof($diskfree))
	{
		list($drive[$count], $typex[$count], $size[$count], $used[$count], $avail[$count], $percent[$count], $mount[$count]) = preg_split('/\s+/', $diskfree[$count]);
		$percent_part[$count] = str_replace( "%", "", $percent[$count]);
		$count++;
	}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>System Information</title>
		<style type="text/css">
			body {
				margin:50px 0px; padding:0px;
				text-align:center;
				}
			#Content {
				width:320px;
				margin:0px auto;
				text-align:left;
				padding:15px;
				border:1px dashed #333;
				background-color:#eee;
			}
			a {
				color:black;
				padding-top:5px;
				display:block;
			}
			a:hover {
				text-decoration:none;
			}
			td {
				font-family:"DejaVu Sans",Arial,Helvetica,sans-serif;
				font-size:12px;
				vertical-align:top;
				padding-left:2px;
				padding-right:2px;
				background:#FFFFFF;
			}
			p {
				font-family:"DejaVu Sans", Arial, Helvetica, sans-serif;
				font-size:12px;
			}
			h1 {
				font-family:"DejaVu Sans", Arial, Helvetica, sans-serif;
				font-size:20px;
				text-align:center;
			}
			td.center {
				text-align:center;
			}
			td.head {
				font-weight:bold;
				padding-top:3px;
				padding-bottom:3px;
			}
			td.right {
				text-align:right;
				padding-right:6px;
			}
			table {
				width: 320px; border-spacing:0;
				border-collapse:collapse;
			}
			html,body,.darkbackground {
				background:#CCCCCC;
			}
			body {
				color:#000000;
			}
			td.column1 {
				width:60px;
			}
			td.column3 {
				width:120px;
			}
			td.column4 {
				width:30px;
			}
			div#bar1, div#bar2, div#bar3, div#bar4, div#bar5, div#bar6 {
				height:12px;
				width:0px;
				transition:width 2s;
				<?php
					$agent = "";
					if(isset($_SERVER['HTTP_USER_AGENT']))
					{
						$agent = $_SERVER['HTTP_USER_AGENT'];
					}
					if(strlen(stristr($agent,"applewebkit")) > 0 ) echo "\n\t\t\t\t-webkit-transition:width 2s;\n";
					else if(strlen(stristr($agent,"gecko")) > 0 ) echo "\n\t\t\t\t-moz-transition:width 2s;\n";
					else if(strlen(stristr($agent,"opera")) > 0 ) echo "\n\t\t\t\t-o-transition:width 2s;\n";
				?>
			}
			div#bar1 { background-color:#D78787; }
			div#bar2 { background-color:#AFD787; }
			div#bar3 { background-color:#F7F7AF; }
			div#bar4 { background-color:#87AFD7; }
			div#bar5 { background-color:#D7AFD7; }
			div#bar6 { background-color:#AFD7D7; }
		</style>
		<script type="text/javascript">
			function updateText(objectId, text)
			{
				document.getElementById(objectId).textContent = text;
			}
			function updateHTML(objectId, html)
			{
				document.getElementById(objectId).innerHTML = html;
			}
			function updateDisplay()
			{
<?php
				echo "\n\t\t\t\tupdateText(\"host\",\"$host\");";
				echo "\n\t\t\t\tupdateText(\"model\",\"$model\");";
				echo "\n\t\t\t\tupdateHTML(\"time\",\"$current_time\");";
				echo "\n\t\t\t\tupdateText(\"kernel\",\"$system\" + \" \" + \"$kernel\");";
				echo "\n\t\t\t\tupdateText(\"processor\",\"$processor\");";
				echo "\n\t\t\t\tupdateText(\"freq\",\"$frequency\" + \"MHz\");";
				echo "\n\t\t\t\tupdateText(\"loadavg\",\"$loadaverages\");";
				echo "\n\t\t\t\tupdateHTML(\"cpu_temperature\",\"$cpu_temperature\" + \"&#x2103;\");";
				echo "\n\t\t\t\tupdateText(\"uptime\",\"$uptime\");";

				echo "\n\t\t\t\tupdateText(\"total_mem\",\"$total_mem\" );";
				echo "\n\t\t\t\tupdateText(\"used_mem\",\"$used_mem\" );";
				echo "\n\t\t\t\tupdateText(\"percent_used\",\"$percent_used%\");";
				echo "\n\t\t\t\tupdateText(\"free_mem\",\"$free_mem\" );";
				echo "\n\t\t\t\tupdateText(\"percent_free\",\"$percent_free%\");";
				echo "\n\t\t\t\tupdateText(\"buffer_mem\",\"$buffer_mem\" );";
				echo "\n\t\t\t\tupdateText(\"percent_buff\",\"$percent_buff%\");";
				echo "\n\t\t\t\tupdateText(\"cache_mem\",\"$cache_mem\" );";
				echo "\n\t\t\t\tupdateText(\"percent_cach\",\"$percent_cach%\");";

				echo "\n\t\t\t\tupdateText(\"total_swap\",\"$total_swap\" );";
				echo "\n\t\t\t\tupdateText(\"used_swap\",\"$used_swap\" );";
				echo "\n\t\t\t\tupdateText(\"percent_swap\",\"$percent_swap%\");";
				echo "\n\t\t\t\tupdateText(\"free_swap\",\"$free_swap\" );";
				echo "\n\t\t\t\tupdateText(\"percent_swap_free\",\"$percent_swap_free%\");\n";
?>
				document.getElementById("bar1").style.width = "<?php echo $percent_used; ?>px";
				document.getElementById("bar2").style.width = "<?php echo $percent_free; ?>px";
				document.getElementById("bar3").style.width = "<?php echo $percent_buff; ?>px";
				document.getElementById("bar4").style.width = "<?php echo $percent_cach; ?>px";
				document.getElementById("bar5").style.width = "<?php echo $percent_swap; ?>px";
				document.getElementById("bar6").style.width = "<?php echo $percent_swap_free; ?>px";
			}
		</script>
	</head>
	<body onload="Javascript: updateDisplay();">
	<div id="Content">
	<h1>System Information</h1>
		<table>
			<tr>
				<td colspan="4" class="head center">General Info</td>
			</tr>
			<tr>
				<td colspan="2">Hostname</td>
				<td colspan="2" id="host"></td>
			</tr>
			<tr>
				<td colspan="2">Model</td>
				<td colspan="2" id="model"></td>
			</tr>
			<tr>
				<td colspan="2">System Time</td>
				<td colspan="2" id="time"></td>
			</tr>
			<tr>
				<td colspan="2">Kernel</td>
				<td colspan="2" id="kernel"></td>
			</tr>
			<tr>
				<td colspan="2">Processor</td>
				<td colspan="2" id="processor"></td>
			</tr>
			<tr>
				<td colspan="2">CPU Frequency</td>
				<td colspan="2" id="freq"></td>
			</tr>
			<tr>
				<td colspan="2">Load Average</td>
				<td colspan="2" id="loadavg"></td>
			</tr>
			<tr>
				<td colspan="2">CPU Temperature</td>
				<td colspan="2" id="cpu_temperature"></td>
			</tr>
			<tr>
				<td colspan="2">Uptime</td>
				<td colspan="2" id="uptime"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" class="head right">Memory:</td>
				<td colspan="2" class="head" id="total_mem"><?php echo $total_mem . " kB";?></td>
			</tr>
			<tr>
				<td class="column1">Used</td>
				<td class="right" id="used_mem"></td>
				<td class="column3"><div id="bar1">&nbsp;</div></td>
				<td class="right column4" id="percent_used"></td>
			</tr>
			<tr>
				<td>Free</td>
				<td class="right" id="free_mem"></td>
				<td><div id="bar2"></div></td>
				<td class="right" id="percent_free"></td>
			</tr>
			<tr>
				<td>Buffered</td>
				<td class="right" id="buffer_mem"></td>
				<td><div id="bar3"></div></td>
				<td class="right" id="percent_buff"></td>
			</tr>
			<tr>
				<td>Cached</td>
				<td class="right" id="cache_mem"></td>
				<td><div id="bar4"></div></td>
				<td class="right" id="percent_cach"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" class="head right">Swap:</td>
				<td colspan="2" class="head" id="total_swap"></td>
			</tr>
			<tr>
				<td>Used</td>
				<td class="right" id="used_swap"></td>
				<td><div id="bar5"></div></td>
				<td class="right" id="percent_swap"></td>
			</tr>
			<tr>
				<td>Free</td>
				<td class="right" id="free_swap"></td>
				<td><div id="bar6"></div></td>
				<td class="right" id="percent_swap_free"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
		</table>
		<table id="tblDiskSpace">
			<tr>
				<td colspan="4" class="head center">Disk Usage</td>
			</tr>
<?php
	for ($i = 1; $i < $count; $i++)
	{
		$total = human_filesize(intval(preg_replace("/[^0-9]/", "", trim($size[$i])))*1024,0);
		$usedspace = human_filesize(intval(preg_replace("/[^0-9]/", "", trim($used[$i])))*1024,0);
		$freespace = human_filesize(intval(preg_replace("/[^0-9]/", "", trim($avail[$i])))*1024,0);
		echo "\n\t\t\t<tr>";
		echo "\n\t\t\t\t<td class=\"head\" colspan=\"4\">" . $mount[$i] . " (" . $typex[$i] . ")</td>";
		echo "\n\t\t\t</tr>";
		echo "\n\t\t\t<tr>";
		echo "\n\t\t\t\t<td>&nbsp;</td>";
		echo "\n\t\t\t\t<td>Total Size</td>";
		echo "\n\t\t\t\t<td class=\"right\">" . $total . "</td>";
		echo "\n\t\t\t\t<td class=\"right\">&nbsp;</td>";
		echo "\n\t\t\t</tr>";
		echo "\n\t\t\t<tr>";
		echo "\n\t\t\t\t<td>&nbsp;</td>";
		echo "\n\t\t\t\t<td>Used</td>";
		echo "\n\t\t\t\t<td class=\"right\">" . $usedspace . "</td>";
		echo "\n\t\t\t\t<td class=\"right\">" . $percent[$i] . "</td>";
		echo "\n\t\t\t</tr>";
		echo "\n\t\t\t<tr>";
		echo "\n\t\t\t\t<td>&nbsp;</td>";
		echo "\n\t\t\t\t<td>Available</td>";
		echo "\n\t\t\t\t<td class=\"right\">" . $freespace . "</td>";
		echo "\n\t\t\t\t<td class=\"right\">" . (100-(floatval($percent_part[$i]))) . "%</td>";
		echo "\n\t\t\t</tr>";
		if ($i < $count-1):
			echo "\n\t\t\t<tr><td colspan=\"4\">&nbsp;</td></tr>";
		endif;
	}
?>
		</table>
		<table>
			<tr>
				<td class="right darkbackground"><a href="javascript:location.reload(true);" title="Refresh">Refresh</a></td>
			</tr>
			<tr>
				<td class="right darkbackground"><a href="https://gist.github.com/4388108">Source</a></td>
			</tr>
		</table>
	</div>
	</body>
</html>
