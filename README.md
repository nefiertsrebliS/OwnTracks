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
		<li>In der OwnTracks-App unter <i>Einstellungen</i> die Taste <i>Sende Regionen</i> drücken.</li>
	</ul> 
	<h2>Lizenz</h2>
	MIT License<br>
	Copyright (c) 2021 nefiertsrebliS<br>
	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:<br>
	The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.<br>
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.<br>
	<h2>Changelog</h2>
	<table>
	  <tr>
		<td>V1.00 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Grundversion</td>
	  </tr>
	  <tr>
		<td>V1.01 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Fix: Android missing RID and INRID</td>
	  </tr>
	  <tr>
		<td>V1.02 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Fix: Optimierung ReceiveData<br>
			Neu: Unterstützung von NextTracks</td>
	  </tr>
	  <tr>
		<td>V1.03 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Neu: Map zur Anezeige der Gerätepositionen<br>
			Neu: Einloggen per Secret-Code<br>
			Neu: Sperren des Zugangs nach 3 Fehlversuchen in 24h auf einer IP-Adresse und 10 Fehlversuchen in 24h insgesamt<br>
			Neu: OTR_GetSecret(12345) zum Anzeigen des Secret-Codes<br>
			Neu: mehrere Hooks mit unterschiedlichem Zugang möglich</td>
	  </tr>
	</table>
  </body>
</html>
