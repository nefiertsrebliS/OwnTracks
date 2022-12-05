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
	<h3>Daten-Instanz</h3>
	<ul>
		<li>Eine OwnTracks-Data-Instanz anlegen</li>
		<li>Topic <i>owntracks/UserID/DeviceID</i> festlegen</li>
		<li>Festlegen, ob Positionsdaten übernommen werden sollen</li>
		<li>Wenn noch keine OwnTracks-Hook-Instanz existiert, wird diese automatisch im Bereich der I/O-Instanzen angelegt</li>
		<li>Die OwnTracks-Hook-Instanz mit Hookname, UserID und Password aus der App konfigurieren</li>
		<li><b>Der Hookname darf noch nicht in Verwendung sein, muss sich also von allen bisherigen Hooknamen unterscheiden</b></li>
		<li>In der OwnTracks-App unter <i>Einstellungen</i> die Taste <i>Sende Regionen</i> drücken.</li>
	</ul> 
	<h3>externe Daten-Instanz</h3>
	<ul>
		<li>Eine OwnTracks-external-Data-Instanz anlegen</li>
		<li>VariablenID für der Positionsdaten wählen. Die Daten müssen im Format <i>{"lat":52, "lon": 10}</i> abgelegt sein.</li>
		<li>Zu überwachende Orte mit <i>Name, Koordinaten und Überwachungsradius</i> festlegen.</li>
	</ul> 
	<h3>Map-Instanz</h3>
	Zusätzlich zur Data-Instanz kann eine Map-Instanz angelegt werden. Auch diese wird mit <i>Hookname</i>, <i>UserID</i> und <i>Password</i> konfiguriert. Der Hookname darf wie oben noch nicht in Verwendung sein, muss sich also von allen bisherigen Hooknamen unterscheiden. Darüber hinaus muss eingestellt werden, wie groß die Karte im WebFront erscheinen soll. In der Konfigurationstabelle können dann alle Daten-Instanzen, die auf der Karte angezeigt werden sollen, konfiguriert werden. Dabei können neben Anzeigename und -farbe auch ein persönliches Icon gewählt und dessen Darstellungsgröße eingestellt werden. Mit der Reihenfolge wird festgelegt, welches Icon auf der Karte zu oberst liegt, wenn diese sich überschneiden. Zusätzlich zu den Daten-Instanzen von OwnTracks kann auch die Location aus den Kern-Instanzen als "zuhause" angezeigt werden.
	<h3>Sicherung des Zugangs</h3>
	Da die Instanzen über WebHook auch aus dem Internet erreichbar sein müssen, sind diese mit <i>Benuternamen</i> und <i>Passwort</i> gesichert. Gibt man dreimal die Zugangsdaten falsch an, so wird der Zugang für diese IP gesperrt. Nach insgesamt 10 Fehlversuchen wird der Zugang für alle IPs für 24 Stunden gesperrt. Um den Zugang wieder zu entsperren, bitte in der Instanz die <i>Sperre zurücksetzen</i>-Taste drücken. 
	<h3>Alternativer Zugang von außen mit Secret-Code statt Username und Passwort</h3>
	Die Zugangsadresse mit Secret-Code lautet:<br> 
	<i>https://meineAdresse.net/hook/Hookname?Secret-Code</i><br>
	Der Secret-Code kann mittels<br>
	<i>OTR_getSecret(12345);</i><br>
	ermittelt werden.
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
	  <tr>
		<td>V1.04 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Fix: Bereinigung des Quellcodes</td>
	  </tr>
	  <tr>
		<td>V1.05 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Neu: persönliche Icon für die Kartendarstellung<br>
			Neu: Anzeige der Location als "zuhause"</td>
	  </tr>
	  <tr>
		<td>V1.06 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Fix: Bei gesperrtem Zugang werden Daten verarbeitet</td>
	  </tr>
	  <tr>
		<td>V1.07 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Neu: Instanz für externe Positionsdaten<br>
			Neu: Besondere Orte auf der Karte konfigurierbar<br>
			Fix: Formatierung HTML-Box</td>
	  </tr>
	  <tr>
		<td>V1.08 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Fix: Device-Namen ausblenden<br>
			Fix: Farbe transparent</td>
	  </tr>
	</table>
  </body>
</html>
