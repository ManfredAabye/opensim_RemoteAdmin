<?php

// Hier ist ein Beispiel, das zeigt, wie man die aktualisierte RemoteAdmin.php-Klasse verwendet, um eine Nachricht zu senden und den OpenSimulator herunterzufahren:

// Dieses Beispiel sendet eine Nachricht an alle Regionen und fährt dann den OpenSimulator herunter.

// admin_broadcast ist das Kommando für Nachrichten
// $params enthält die Nachricht, die gesendet werden soll.

// Das Kommando admin_shutdown fährt den OpenSimulator herunter.

include('RemoteAdmin.php'); // RemoteAdmin.php ist der Name der PHP-Klasse

// Instanzieren der Klasse (IP-Adresse, Port, Passwort)
$myRA = new RemoteAdmin('127.0.0.1', 9000, 'secret');

// RemoteAdmin-Befehle ausführen
$params = ['message' => 'Diese Nachricht wird an alle Regionen des OpenSimulators gesendet!'];
$myRA->SendCommand('admin_broadcast', $params);

// Wenn ein RemoteAdmin-Befehl keine Parameter benötigt, 
// kann der zweite Parameter bei der Funktion SendCommand weggelassen werden.
$myRA->SendCommand('admin_shutdown');
?>
