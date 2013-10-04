THW Prüfungskoordination
========================

Dieses PHP-Script erleichtert die Koordination von Stationen und Teilnehmern während einer THW Grundausbildungsprüfung. 

![Screenshot](https://github.com/rwolke/thw-pruefungskoordination/raw/master/images/screenshot.png)

Dieses Script managed die Belegung von Prüfungsstationen bei der THW-Grundausbildung. Mehrere
Teilnehmer können zu einer Station geschickt werden, womit diese belegt ist. Anschließend wird
vermerkt wenn der Kamerad wieder zurück ist. Bestimmte Stationen können auch mehrfach belegt
werden (Teamprüfung).
 
## Benutzung

### Voraussetzung: 

* Webserver mit PHP5 
* PDO-Kompatibler DB-Server

### Inbetriebnahme

1. structure.sql in Datenbank laden
2. Teilnehmer und Stationen über externes Programm einpflegen (können im Betrieb ergänzt, jedoch nicht gelöscht werden)
3. Eintragen der DB-Zugangsdaten in dieses Script

### Funktionen
- mehrere Stationen je Aufgabe
- Erfassung der Prüfungszeit je Station um Engpässe abzuschätzen
- Erfassung der Pausenzeit von Prüflingen um diese gleichmäßig zu gestalten (Prüfling mit größter Pause oben in Liste)
- Prüflinge können in die Station "Mittagspause" bzw. "Pause" geschickt werden (nur je max. ein mal).  
- Aktuelle Belegung der Station ist leicht ersichtlich (2. Tabelle)
- Weitere Stationen und Prüflinge können jeder Zeit nachgetragen werden.
- Aufenthaltsdauer eines Prüflings kann an einer Station wird anhand der Durchschnittsgeschwindigkeit des Prüfers abgeschätzt
  (untere Tabelle) gelbe Markierung bei Überschreitung der Durchschnittszeit

### ToDo / fehlt
- Pflege der Datensätze für Stationen und Teilnehmer über das Script
- sobald ein Prüfling als von Station zurückgekehrt / absolviert markiert ist, kann dies über das Programm nicht rückgängig
  gemacht werden. Diese Datensatz muss manuell aus der Tabelle jobs gelöscht werden.
- Stationen/Prüfer können nicht als in Pause markiert werden.
- Einbeziehung der Prüflingsgeschwindigkeit zur Abschätzung der Prüfungsdauer (z.B. Prüfling brauchte an 3 vorherigen 
  Stationen 70%, 80% und 90% der durchschnittlichen Prüfungszeit, folglich braucht er an der nächsten Station wohl nur 
  80% der durchschnittlichen Prüfungszeit dieser Station)

## Bemerkung

Das Skript ist unter hohen Zeitdruck entstanden und sollte auch nur einmalig vom Autor verwendet werden. Aufgrund dessen enthält es keine Fehlerbehandlung bzw. Optimierungen oder gar eine Trennung von Code und Layout. Aufgrund von Nachfragen stelle ich es aber gerne online. Nutzung ohne jedwede Garantie!

## Lizenz

<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/de/" class="ui-link"><img alt="Creative Commons Lizenzvertrag" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-sa/3.0/de/88x31.png"></img></a><br />
<span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">THW Prüfungskoordination</span> von <span xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName">Robert Wolke</span> steht unter einer <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/de/" class="ui-link">Creative Commons Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 3.0 Deutschland Lizenz</a>.
Auf Grundlage dieser Lizenz kann das Script gerne weiterentwickelt und auch selbst gehostet werden. Verbesserungsvorschläge und Pull Requests können gerne über <a href="http://github.com/rwolke/thw-pruefungskoordination">GitHub</a> eingereicht werden.

