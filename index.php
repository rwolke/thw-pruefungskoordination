<?php
	/*
	 *  Einfaches Skript um die Koordination bei einer THW-Grundausbildungsprüfung zu vereinfachen
	 * 
	 *  Dieses Script managed die Belegung von Prüfungsstationen bei der THW-Grundausbildung. Mehrere
	 *  Teilnehmer können zu einer Station geschickt werden, womit diese belegt ist. Anschließend wird
	 *  vermerkt wenn der Kamerad wieder zurück ist. Bestimmte Stationen können auch mehrfach belegt
	 *  werden (Teamprüfung).
	 *  
	 *  Benutzung:
	 *    Voraussetzung: Webserver mit PHP5 & PDO-Kompatibler DB-Server
	 *    Inbetriebnahme: 
	 *      1. structure.sql in Datenbank laden
	 *      2. Teilnehmer und Stationen über externes Programm einpflegen (können im Betrieb ergänzt, jedoch nicht gelöscht werden)
	 *      3. Eintragen der DB-Zugangsdaten in dieses Script
	 *    Funktionen:
	 *      - mehrere Stationen je Aufgabe
	 *      - Erfassung der Prüfungszeit je Station um Engpässe abzuschätzen
	 *      - Erfassung der Pausenzeit von Prüflingen um diese gleichmäßig zu gestalten (Prüfling mit größter Pause oben in Liste)
	 *      - Prüflinge können in die Station "Mittagspause" bzw. "Pause" geschickt werden (nur je max. ein mal).  
	 *      - Aktuelle Belegung der Station ist leicht ersichtlich (2. Tabelle)
	 *      - Weitere Stationen und Prüflinge können jeder Zeit nachgetragen werden.
	 *      - Aufenthaltsdauer eines Prüflings kann an einer Station wird anhand der Durchschnittsgeschwindigkeit des Prüfers abgeschätzt
	 *        (untere Tabelle) gelbe Markierung bei Überschreitung der Durchschnittszeit
	 *    ToDo / fehlt:
	 *      - Pflege der Datensätze für Stationen und Teilnehmer über das Script
	 *      - sobald ein Prüfling als von Station zurückgekehrt / absolviert markiert ist, kann dies über das Programm nicht rückgängig
	 *        gemacht werden. Diese Datensatz muss manuell aus der Tabelle jobs gelöscht werden.
	 *      - Stationen/Prüfer können nicht als in Pause markiert werden.
	 *      - Einbeziehung der Prüflingsgeschwindigkeit zur Abschätzung der Prüfungsdauer (z.B. Prüfling brauchte an 3 vorherigen 
	 *        Stationen 70%, 80% und 90% der durchschnittlichen Prüfungszeit, folglich braucht er an der nächsten Station wohl nur 
	 *        80% der durchschnittlichen Prüfungszeit dieser Station)
	 * 
	 *  @author   Robert Wolke <r.wolke+ga@thw-rostock.de>
	 *  @licence  CC BY-NC-SA 3.0 DE     http://creativecommons.org/licenses/by-nc-sa/3.0/de/
	 */
	
	date_default_timezone_set('Europe/Berlin');
	
	$db = new PDO('mysql:unix_socket=/opt/local/var/run/mariadb/mysqld.sock;dbname=thw', 'thw', 'thw');
	$db->exec('SET NAMES "UTF8";');

	function getTeilnehmer() {
		global $db;
		static $result = false;
		if($result != false) return $result;
		
		$res = $db->query('SELECT t.teilnehmerID,MAX(j.end) AS end FROM teilnehmer t LEFT JOIN job j ON (t.teilnehmerID = j.teilnehmer) GROUP BY t.teilnehmerID ORDER BY end ASC;')->fetchAll(PDO::FETCH_CLASS);
		$order = array();
		foreach($res AS $row)
			$order[$row->teilnehmerID] = $row->end ? strtotime($row->end) : 0;
		
		asort($order);
		
		$res = $db->query('SELECT t.*,j.jobID,j.station FROM teilnehmer t LEFT JOIN job j ON (t.teilnehmerID = j.teilnehmer AND j.end IS NULL)')->fetchAll(PDO::FETCH_CLASS);
		$result = array();
		foreach($order AS $i => $last)
			foreach($res AS $row)
				if($i == $row->teilnehmerID)
				{
					$row->last = $last;
					$result[$row->teilnehmerID] = $row;
				}
		
		return $result;
	}
	
	function getStationen() {
		global $db;
		static $result = false;
		if($result != false) return $result;

		$res = $db->query('SELECT s.*,COUNT(j.jobID) AS num FROM station s LEFT JOIN job j ON (s.stationID = j.station AND j.end IS NULL) GROUP BY s.stationID ORDER BY s.type, s.stationID ASC;')->fetchAll(PDO::FETCH_CLASS);
		$result = array();
		foreach($res AS $row)
			$result[$row->stationID] = $row;
		return $result;
	}

	function getBelegung() {
		global $db;
		static $result = false;
		if($result != false) return $result;
		
		$res = $db->query('SELECT s.*,j.* FROM station s LEFT JOIN job j ON (s.stationID = j.station) WHERE end IS NULL AND start IS NOT NULL ORDER BY stationID ASC')->fetchAll(PDO::FETCH_CLASS);
		$result = array();
		foreach($res AS $row)
			$result[] = $row;
		return $result;
	}

	function getDone() {
		global $db;
		static $result = false;
		if($result != false) return $result;
		
		$res = $db->query('SELECT j.teilnehmer,j.station,s.type,IF(end IS NOT NULL,1,0) finished FROM job j INNER JOIN station s ON (j.station = s.stationID) WHERE start IS NOT NULL;')->fetchAll(PDO::FETCH_CLASS);
		$result = array();
		foreach($res AS $row)
			if(!isset($result[$row->teilnehmer]))
				$result[$row->teilnehmer] = array($row->type => $row->finished);
			else
				$result[$row->teilnehmer][$row->type] = $row->finished;
		return $result;
	}
	
	function getDauer() {
		global $db;
		static $result = false;
		if($result != false) return $result;
		
		$result = array();
		foreach(getStationen() AS $s)
			$result[$s->stationID] = $s->dauer ? 60 * $s->dauer : array();

		$res = $db->query('SELECT station, start, end FROM job WHERE start IS NOT NULL AND end IS NOT NULL')->fetchAll(PDO::FETCH_CLASS);
		foreach($res AS $row)
			if(is_array($result[$row->station]))
				$result[$row->station][] = strtotime($row->end) - strtotime($row->start);
			
		foreach($result AS $i => $r)
			if(is_array($r) && count($r))
				$result[$i] = array_sum($r) / count($r);
			elseif(is_array($r))
				$result[$i] = 30 * 60;
		
		return $result;
	}
	
	if(isset($_GET['action']))
	{
		$stat = getStationen();

		$action = $_GET['action'];
		$db->beginTransaction();
		if($action == 'new')
		{
			$s = $stat[$_GET['station']];
			$db->exec(
				'INSERT INTO job (station, teilnehmer, start, end, counts) '.
				'VALUES ('.intval($_GET['station']).', '.intval($_GET['teilnehmer']).', NOW(), NULL, '.$s->pauseReq.');'
			);
		}
		elseif($action == 'del' && isset($_GET['job']))
			$db->exec('DELETE FROM job WHERE jobID = '.intval($_GET['job']).';');
		elseif($action == 'end' && isset($_GET['job']))
			$db->exec('UPDATE job SET end = NOW() WHERE jobID = '.intval($_GET['job']).' AND end IS NULL;');
		$db->commit();
		
		header('Location: /thw/');
		exit;
	}

	$teil = getTeilnehmer();
	$stat = getStationen();
	
	$belegung = getBelegung();
	
	$done = getDone();
	$dauer = getDauer();

	if(isset($_GET['action']))
	{
		$stat = getStationen();

		$action = $_GET['action'];
		$db->beginTransaction();
		if($action == 'new')
		{
			$s = $stat[$_GET['station']];
			$db->exec(
				'INSERT INTO job (station, teilnehmer, start, end, counts) '.
				'VALUES ('.intval($_GET['station']).', '.intval($_GET['teilnehmer']).', NOW(), NULL, '.$s->pauseReq.');'
			);
		}
		elseif($action == 'del' && isset($_GET['job']))
			$db->exec('DELETE FROM job WHERE jobID = '.intval($_GET['job']).';');
		elseif($action == 'end' && isset($_GET['job']))
			$db->exec('UPDATE job SET end = NOW() WHERE jobID = '.intval($_GET['job']).' AND end IS NULL;');
		$db->commit();
		
		header('Location: /thw/');
		exit;
	}

	$teil = getTeilnehmer();
	$stat = getStationen();
	
	$belegung = getBelegung();
	
	$done = getDone();
	$dauer = getDauer();

?><!DOCTYPE html>
<html lang="de">
	<head>
		<meta charset="utf-8">
		<title>THW Prüfungskoordination</title>
		<link href="css/jquery.dataTables.css" rel="stylesheet">
		<link href="css/jquery-ui-1.10.3.custom.css" rel="stylesheet">
		<style tyle="text/css">
			th span.sml { font-size:8pt; font-style:italic; }
			body { font-size:14px; }
			h2 { margin-bottom:-25px; }
			.status_red { background:#F00; }
			.status_yellow { background:#FF0; }
			.status_green { background:#0F0; }
			.even .status_red { background:#F22; color:white; font-weight:bold; }
			.even .status_yellow { background:#FF2; }
			.even .status_green { background:#2F2; }
			.odd .status_red { background:#D00; color:white; font-weight:bold; }
			.odd .status_yellow { background:#DD0; }
			.odd .status_green { background:#0D0; }
			.even.overtime.fixedTime td { background:#F22; }
			.even.overtime td { background:#FF2; }
			.odd.overtime.fixedTime td { background:#D00; color:white; }
			.odd.overtime td { background:#DD0; }
			.ui-button-text-only .ui-button-text { padding: .2em .4em; }
			table.dataTable { border:1px solid #888; border-collapse:collapse; }
			table.dataTable thead th { padding: 3px; }
			table.dataTable tbody td { padding: 2px; height:26px; }
		</style>
		<script type="text/javascript" language="javascript" src="js/jquery.min.js"></script>
		<script type="text/javascript" language="javascript" src="js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" language="javascript" src="js/jquery-ui-1.10.3.custom.min.js"></script>
		<script type="text/javascript" charset="utf-8">
			function countDown() {
				var d = (new Date()).getTime() / 1000; 
				$('.timer').each(function(i,e){
					var el = $(e);
					
					if(el.data('asc') == '-1')
						el.text('');
					else if(el.data('asc'))
					{
						var t = d - $(e).data('asc');
						var m = parseInt(t / 60);
						var s = parseInt(t % 60);
						s = s < 10 ? '0' + s : s;
						$(e).text(m + ':' + s);
					}
					else
						$(e).text('- nie -');
					
					if(el.data('time'))
					{
						var t = $(e).data('time') - d;
						var neg = t < 0;
						if(neg)
							$(e).parent().parent().addClass('overtime');
						t = t * (neg ? -1 : 1);
						var m = parseInt(t / 60);
						var s = parseInt(t % 60);
						s = s < 10 ? '0' + s : s;
						$(e).text((neg ? '-' : '') + m + ':' + s);
					}
				});
			}
		
			$(document).ready(function() {
				$('#weg,#da').dataTable({
					"bPaginate": false,
					"bInfo" : false,
					"bSort": false,
					"bLengthChange" : false
				});
				
				$("a.button").each(function(i,e){
					var opt = {}
					var el = $(e);
					if(el.data('icon')) opt.icons = {primary:'ui-icon-'+$(e).data('icon')};
					if(el.hasClass('icon-only')) opt.text = false;
					el.button(opt);
				});
				
				$(".radioset").buttonset();
				
				setInterval('countDown();', 1000);
			});
		</script>
	</head>
	<body>
		<h2>Verfügbar</h2>
		<table cellpadding="0" cellspacing="0" border="1" id="da" class="display" width="100%">
			<thead>
				<tr>
					<th width="10">Prio</th>
					<th width="150">Teilnehmer</th>
					<th width="30">OV</th>
					<th width="60">Pause</th>
<?php
	foreach($stat AS $s)
	{
		$status = $s->num == 0 ? 'green' : ($s->num < $s->max ? 'yellow' : 'red');
?>
					<th width="70" class="status_<?=$status?>">Station <?=$s->krzl?><br /><span class="sml"><?=$s->name?></span></th>
<?php
	}
?>
				</tr>
			</thead>
			<tbody>
<?php
	$n = 0;
	foreach($teil AS $i => $t)
	{
		$n++;
?>
				<tr class="">
					<td><?=$n?></td>
					<td class="<?=($t->jobID ? 'status_red' : '')?>"><?=$t->name?></td>
					<td class="<?=($t->jobID ? 'status_red' : '')?>"><?=$t->ov?></td>
					<td align="center"><span class="timer" data-asc="<?=($t->jobID ? -1 : $t->last)?>"></span></td>
<?php
		foreach($stat AS $j => $s)
		{
			$status = '';
			if(isset($done[$i][$s->type]) && $done[$i][$s->type])
				$status = 'green';
			elseif($t->jobID != null)
			{
				if($t->station == $j)
					$status = 'red';
				else
					$status = 'yellow';
			}
			elseif($s->num == $s->max)
				$status = 'yellow';
?>
						<td class="center status_<?=$status?>">
<?php
			if($status == '')
				echo '<a class="button" href="?action=new&amp;teilnehmer='.$i.'&amp;station='.$j.'">N</a>';
			elseif($status == 'red')
			{
				echo '<a class="button icon-only" data-icon="cancel" href="?action=del&amp;job='.$t->jobID.'" onclick="return confirm(\'Wirklich abbrechen?\');"></a>';
				echo '<a class="button icon-only" data-icon="check"  href="?action=end&amp;job='.$t->jobID.'"></a>';
			}
?>
					</td>
<?php
		}
?>
				</tr>
<?php
	}

?>
			</tbody>
		</table>
		
		
		
		<hr />
<?php
	$dauer = getDauer();
?>
		<h2>In Aktion</h2>
		<table cellpadding="0" cellspacing="0" border="1" id="weg" class="display" width="100%">
			<thead>
				<tr>
					<th>Station</th>
					<th>Kapazität</th>
					<th>Teilnehmer</th>
					<th>OV</th>
					<th>Dauer</th>
					<th>seit</th>
					<th width="400px">Aktion</th>
				</tr>
			</thead>
			<tbody>
<?php
	$n = 0;
	foreach($belegung AS $b)
	{
		$s = $stat[$b->stationID];
		$d = $dauer[$b->stationID];
		$t = 0;
		if($b->teilnehmer)
			$t = $teil[$b->teilnehmer];
?>
				<tr class="<?=($s->dauer?'fixedTime':'')?>">
					<td>Station <?=$s->krzl.' <i>'.$s->name.'</i>'?></td>
					<td><?=$s->max?></td>
					<td><?=($t ? $t->name : '<i>- frei -</i>')?></td>
					<td class="center"><?=($t ? $t->ov : '')?></td>
					<td class="center">
<?php
						echo intval($d / 60).':'.str_pad($d % 60, 2, '0', STR_PAD_LEFT);
?>
					</td>
					<td class="center">
						<span class="timer" data-time="<?=($d+strtotime($b->start))?>"></span>
					</td>
					<td class="center">
<?php
						if($t)
						{
?>
						<a class="button" href="?action=del&amp;job=<?=$b->jobID?>">abbrechen</a>
						<a class="button" href="?action=end&amp;job=<?=$b->jobID?>">abgeschlossen</a>
<?php
						}
?>
					</td>
				</tr>
<?php
	}

?>
			</tbody>
		</table>
		<hr />

		<h2>Legende</h2>
		<br />
		<table cellpadding="5" cellspacing="0" border="0" width="100%">
			<tr>
				<td width="50%">
					<h4>Tabelle &quot;Verfügbar&quot;</h4>
					<table cellpadding="2" cellspacing="0" border="0" width="100%">
						<tbody>
							<tr class="even">
								<td class="" style="height:10px;width:10px"></td>
								<td rowspan="2" style="padding-left:5px">Kombination Prüfling/Station frei. Prüfling kann dort hin geschickt werden (N)</td>
							</tr>
							<tr class="odd">
								<td class="" style="background:#e2e4ff;height:10px;width:10px"></td>
							</tr>
							<tr><td colspan="2" style="height:4px"></td></tr>
							<tr class="even">
								<td class="status_red" style="height:10px;width:10px"></td>
								<td rowspan="2" style="padding-left:5px">Station (vollständig) belegt, bzw. Prüfling gerade an dieser Station</td>
							</tr>
							<tr class="odd">
								<td class="status_red" style="height:10px;width:10px"></td>
							</tr>
							<tr><td colspan="2" style="height:4px"></td></tr>
							<tr class="even">
								<td class="status_yellow" style="height:10px;width:10px"></td>
								<td rowspan="2" style="padding-left:5px">Aktion nicht möglich da Prüfling oder Station belegt</td>
							</tr>
							<tr class="odd">
								<td class="status_yellow" style="height:10px;width:10px"></td>
							</tr>
							<tr><td colspan="2" style="height:4px"></td></tr>
							<tr class="even">
								<td class="status_green" style="height:10px;width:10px"></td>
								<td rowspan="2" style="padding-left:5px">Prüfling hat diese Station absolviert</td>
							</tr>
							<tr class="odd">
								<td class="status_green" style="height:10px;width:10px"></td>
							</tr>
						</tbody>
					</table>	
				</td>
				<td width="50%" style="border-left:2px solid black;">
					<h4>Tabelle &quot;In Aktion&quot;</h4>
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
							<tr class="even">
								<td class="" style="height:10px;width:10px"></td>
								<td rowspan="2" style="padding-left:5px">Prüfling in Aktion</td>
							</tr>
							<tr class="odd">
								<td class="" style="background:#e2e4ff;height:10px;width:10px"></td>
							</tr>
							<tr><td colspan="2" style="height:4px"></td></tr>
							<tr class="even">
								<td class="status_yellow" style="height:10px;width:10px"></td>
								<td rowspan="2" style="padding-left:5px">Prüfling schon länger an Station als Lehrgangsdurchschnitt</td>
							</tr>
							<tr class="odd">
								<td class="status_yellow" style="height:10px;width:10px"></td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<hr />
		<p>
			<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/de/" class="ui-link"><img alt="Creative Commons Lizenzvertrag" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-sa/3.0/de/88x31.png"></img></a><br />
			<span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">THW Prüfungskoordination</span> von <span xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName">Robert Wolke</span> steht unter einer <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/de/" class="ui-link">Creative Commons Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 3.0 Deutschland Lizenz</a>.
			Auf Grundlage dieser Lizenz kann das Script gerne weiterentwickelt und auch selbst gehostet werden. Verbesserungsvorschläge und Pull Requests können gerne über <a href="http://github.com/rwolke/thw-pruefungskoordination">GitHub</a> eingereicht werden.
		</p>
	</body>
</html>
