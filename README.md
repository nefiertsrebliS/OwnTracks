<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width">
  </head>

  <body>
	<h1>OwnTracks-Bibliothek für die Nutzung von Owntracks-Informationen zur Hausautomation mit IP-Symcon</h1>
	<h2>Grundsätzliches</h2>
	Die Bibliothek besteht aus einem Webhook-Modul, dass die Kommunikation mit der OwnTracks-App regelt und einem Datenmodul, mit dem für jedes Mobilgerät eine Instanz angelegt werden kann. 
	<h2>Einstellungen in der App</h2>
	Die Einstellungen der App werden <a href="https://owntracks.org/booklet/">hier</a> beschrieben. Die Kommunikation läuft über den <b>HTTP-Endpoint</b>. 
	Unter Einstellungen sind folgende Festlegungen zu treffen:
	<ul>
		<li>Den Modus auf <i>HTTP</i> einstellen</li>
		<li>Als Adresse ist <i>https://meineURL.de/hook/owntracks</i> einzutragen.</li>
		<li>Der Zugang sollte über <i>UserID</i> und <i>Password</i> geschützt werden</li>
		<li>Die <i>DeviceID</i> festlegen</li>
		<li>Zu überwachende Region(en) festlegen</li>
		<li>Unter Karte das Sendeverhalten einstellen. Bei mir hat sich die Einstellung <i>Wesentlich</i> bewährt</li>
	</ul> 
	<h2>Installation und Einstellung in IP-Symcon</h2>
	Die Installation der Bibliothek wird <a href="https://www.symcon.de/service/dokumentation/komponenten/verwaltungskonsole/module-store/">hier</a> beschrieben.
	<ul>
		<li>Im Bereich der I/O-Instanzen eine OwnTracks-Hook-Instanz anlegen</li>
		<li><i>UserID</i> und <i>Password</i> aus der App eintragen</li>
		<li>Eine OwnTracks-Daten-Instanz anlegen.
		<li>Topic <i>owntracks/UserID/DeviceID</i> festlegen</li>
		<li>Festlegen, ob Positionsdaten übernommen werden sollen</li>
	</ul> 
	<h2>Lizenz</h2>
	<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/"><img alt="Creative Commons Lizenzvertrag" style="border-width:0" src="https://i.creativecommons.org/l/by-nc-sa/4.0/88x31.png" /></a><br />Dieses Werk ist lizenziert unter einer <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International Lizenz</a>
	<h2>Changelog</h2>
	<table>
	  <tr>
		<td>V1.00 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Grundversion</td>
	  </tr>
	</table>
  </body>
</html>
