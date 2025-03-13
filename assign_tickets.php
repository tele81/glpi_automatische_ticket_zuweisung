<?php
// Load GLPI constants
define('GLPI_ROOT', __DIR__);
include(GLPI_ROOT . "/inc/includes.php");

if (!class_exists('DB')) {
    die('Datenbank-Klasse nicht gefunden. Überprüfe den Include-Pfad.');
}

// Technikerlisten die IDs
$regular_technicians = [ ];

$last_assigned_file = "last_assigned.json";

// Letzte Zuweisung speichern/laden
function loadLastAssigned() {
    global $last_assigned_file;
    if (file_exists($last_assigned_file)) {
        $data = json_decode(file_get_contents($last_assigned_file), true);
        return $data["last_technician"] ?? -1;
    }
    return -1;
}

function saveLastAssigned($tech_id) {
    global $last_assigned_file;
    file_put_contents($last_assigned_file, json_encode(["last_technician" => $tech_id]));
}

// Funktion zur fairen Zuweisung der Tickets
function getNextTechnician() {
    global $regular_technicians;

    // Lade den letzten zugewiesenen Techniker
    $last_tech = loadLastAssigned();

    // Finde den Index des letzten Technikers und wechsle zum nächsten
    if ($last_tech == -1) {
        $next_tech = $regular_technicians[0]; // Wenn keiner zugewiesen wurde, starte mit dem ersten Techniker
    } else {
        $last_tech_index = array_search($last_tech, $regular_technicians);
        // Wenn der letzte Techniker der letzte in der Liste war, gehe zum ersten Techniker
        $next_tech = $regular_technicians[($last_tech_index + 1) % count($regular_technicians)];
    }

    // Debug-Ausgabe des nächsten Technikers
    echo "Nächster Techniker: $next_tech\n";

    // Speichere den zuletzt zugewiesenen Techniker
    saveLastAssigned($next_tech);

    return $next_tech;
}

// Funktion zum Abrufen neuer Tickets
function getNewTickets() {
    global $DB;

    // SQL-Abfrage für neue Tickets (status = 1 bedeutet "Neu")
    $query = "SELECT id FROM glpi_tickets WHERE status = 1";
    echo "Executing query: $query\n"; // Debug-Ausgabe
    $result = $DB->query($query);

    $tickets = [];
    while ($row = $DB->fetchArray($result)) {
        $tickets[] = $row;
    }

    // Debug-Ausgabe der Tickets
    echo "Gefundene Tickets: ";
    print_r($tickets);

    return $tickets;
}

// Funktion zum Prüfen, ob bereits ein Techniker zugewiesen wurde
function isTicketAssigned($ticket_id) {
    global $DB;

    // SQL-Abfrage, um zu überprüfen, ob das Ticket bereits einem Techniker zugewiesen ist
    $query = "SELECT COUNT(*) FROM glpi_tickets_users WHERE tickets_id = $ticket_id AND type = 2";
    $result = $DB->query($query);
    $row = $DB->fetchArray($result);

    return $row[0] > 0;
}

// Funktion zum Entfernen mehrfacher Zuweisungen eines Technikers zu einem Ticket
function removeDuplicateAssignments($ticket_id) {
    global $DB;

    // SQL-Abfrage, um alle Zuweisungen für das Ticket zu holen
    $query = "SELECT id FROM glpi_tickets_users WHERE tickets_id = $ticket_id AND type = 2";
    $result = $DB->query($query);

    // Array für alle Zuweisungen speichern
    $assignments = [];
    while ($row = $DB->fetchArray($result)) {
        $assignments[] = $row['id'];
    }

    // Falls mehr als eine Zuweisung gefunden wurde, löschen wir alle bis auf die erste
    if (count($assignments) > 1) {
        // Entferne alle Zuweisungen außer der ersten
        array_shift($assignments); // Entfernt die erste Zuweisung

        foreach ($assignments as $assignment_id) {
            // Lösche alle weiteren Zuweisungen
            $delete_query = "DELETE FROM glpi_tickets_users WHERE id = $assignment_id";
            $DB->query($delete_query);
            echo "Duplikat-Zuweisung mit ID $assignment_id für Ticket $ticket_id entfernt.\n";
        }
    }
}

// Funktion zum Zuweisen eines Tickets an einen Techniker
function assignTicket($ticket_id, $technician_id) {
    global $DB;

    // Zuerst entfernen wir alle Duplikate, wenn mehrere Techniker zugewiesen sind
    removeDuplicateAssignments($ticket_id);

    // Wenn noch kein Techniker zugewiesen wurde, weise einen neuen zu
    if (!isTicketAssigned($ticket_id)) {
        // SQL-Abfrage zum Hinzufügen des Technikers
        $query = "INSERT INTO glpi_tickets_users (tickets_id, users_id, type, use_notification) 
                  VALUES ($ticket_id, $technician_id, 2, 1)";

        // Führe die Abfrage aus
        $result = $DB->query($query);

        if ($result) {
            // Debug-Ausgabe der Antwort von der Datenbank
            echo "Ticket $ticket_id wurde Techniker $technician_id zugewiesen.\n";
        } else {
            echo "Fehler beim Zuweisen von Ticket $ticket_id an Techniker $technician_id.\n";
        }
    } else {
        echo "Ticket $ticket_id ist bereits einem Techniker zugewiesen.\n";
    }
}

// Hauptfunktion zum Bearbeiten neuer Tickets
function assignNewTickets() {
    $tickets = getNewTickets();

    if (empty($tickets)) {
        echo "Keine neuen Tickets vorhanden.\n";
        return;
    }

    foreach ($tickets as $ticket) {
        if (isset($ticket["id"])) {
            $technician_id = getNextTechnician();
            assignTicket($ticket["id"], $technician_id);
        }
    }
}

// Skript ausführen
assignNewTickets();
?>
