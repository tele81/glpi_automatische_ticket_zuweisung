# Automatische Ticketzuweisung in GLPI

Dieses PHP-Skript automatisiert die Zuweisung neuer Tickets in einem GLPI-System an eine festgelegte Liste von Technikern. Dabei wird eine faire, rotierende Verteilung der Tickets sichergestellt. Es überprüft zudem doppelte Zuweisungen und entfernt sie gegebenenfalls.

## 📌 Funktionsweise

### 1️⃣ Laden der GLPI-Konstanten und Datenbankverbindung
- Das Skript lädt die GLPI-Umgebung mit `includes.php`, um auf die Datenbank und Funktionen zugreifen zu können.
- Es stellt sicher, dass die `DB`-Klasse existiert, um Fehler zu vermeiden.

### 2️⃣ Technikerliste und Speichermechanismus
- Eine Liste fester Techniker-IDs wird definiert.
- Die letzte Ticket-Zuweisung wird in einer JSON-Datei (`last_assigned.json`) gespeichert, um eine faire Rotation der Techniker zu ermöglichen.

### 3️⃣ Hauptfunktionen
| Funktion | Beschreibung |
|----------|-------------|
| `getNewTickets()` | Ruft alle neuen Tickets aus der Datenbank ab. |
| `isTicketAssigned($ticket_id)` | Prüft, ob ein Ticket bereits einem Techniker zugewiesen wurde. |
| `removeDuplicateAssignments($ticket_id)` | Löscht doppelte Zuweisungen, falls ein Ticket mehreren Technikern zugewiesen wurde. |
| `getNextTechnician()` | Bestimmt den nächsten Techniker in der Liste basierend auf der letzten Zuweisung. |
| `assignTicket($ticket_id, $technician_id)` | Weist ein Ticket einem Techniker zu, wenn es noch nicht zugewiesen wurde. |
| `assignNewTickets()` | Durchläuft alle neuen Tickets und weist sie nacheinander den Technikern zu. |

### 4️⃣ Ablauf des Skripts
- Das Skript wird gestartet und ruft `assignNewTickets()` auf.
- Neue Tickets werden abgefragt und bearbeitet.
- Die Techniker werden fair im Wechsel zugewiesen.
- Debug-Informationen werden in der Konsole ausgegeben.

## 🔧 Voraussetzungen
- Ein funktionierendes GLPI-System mit MySQL-Datenbank.
- Ein Webserver mit PHP-Unterstützung.
- Schreibrechte für die Datei `last_assigned.json` im Skriptverzeichnis.

## 🚀 Installation
1. Kopiere das Skript in das GLPI-Verzeichnis oder einen passenden Ort mit Zugriff auf die Datenbank.
2. Stelle sicher, dass PHP die Datei `last_assigned.json` schreiben kann.
3. Führe das Skript manuell aus oder richte einen Cron-Job ein.

## 🛠️ Nutzung
Das Skript kann per CLI oder Webserver aufgerufen werden:
```sh
php assign_tickets.php
```

