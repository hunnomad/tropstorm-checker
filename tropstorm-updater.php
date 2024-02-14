<?php

$databaseFile = 'file_monitor.db';

// SQLite adatbázis inicializálása
$db = new SQLite3($databaseFile);

// Tábla létrehozása, ha még nem létezik
$db->exec('CREATE TABLE IF NOT EXISTS file_data (filename TEXT, modified_time INTEGER, size INTEGER)');

$filePath = '/elérés/a/távoli/szerveren/fájl.txt';

while (true) {
    // Ellenőrizze a fájl módosítását és méretét
    clearstatcache(); // Frissíti a fájl státusz cache-t
    $currentModified = filemtime($filePath);
    $currentSize = filesize($filePath);

    // Lekérdezés az adatbázisból az aktuális adatokhoz
    $query = $db->prepare('SELECT * FROM file_data WHERE filename = :filename');
    $query->bindValue(':filename', $filePath, SQLITE3_TEXT);
    $result = $query->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if (!$row) {
        // Ha a fájl még nincs az adatbázisban, beszúrjuk
        $insertQuery = $db->prepare('INSERT INTO file_data (filename, modified_time, size) VALUES (:filename, :modified_time, :size)');
        $insertQuery->bindValue(':filename', $filePath, SQLITE3_TEXT);
        $insertQuery->bindValue(':modified_time', $currentModified, SQLITE3_INTEGER);
        $insertQuery->bindValue(':size', $currentSize, SQLITE3_INTEGER);
        $insertQuery->execute();
    } else {
        // Ha már van az adatbázisban, ellenőrizzük a változásokat
        if ($row['modified_time'] != $currentModified || $row['size'] != $currentSize) {
            // A fájl megváltozott, futtasd a metódust
            runMethod();

            // Frissítsd az adatokat az adatbázisban
            $updateQuery = $db->prepare('UPDATE file_data SET modified_time = :modified_time, size = :size WHERE filename = :filename');
            $updateQuery->bindValue(':filename', $filePath, SQLITE3_TEXT);
            $updateQuery->bindValue(':modified_time', $currentModified, SQLITE3_INTEGER);
            $updateQuery->bindValue(':size', $currentSize, SQLITE3_INTEGER);
            $updateQuery->execute();
        }
    }

    // Várakozás az újabb ellenőrzés előtt (pl. 1 másodperc)
    sleep(1);
}

function runMethod() {
    // Ide írd be a kívánt metódust vagy kódokat
    // például:
    // exec('php /elérés/a/futtatandó/fájl.php');
    echo "A fájl megváltozott, metódus futtatva!\n";
}

// Ne felejtsd el lezárni az adatbázis kapcsolatot a folyamat végén vagy a kivételkezelést!
$db->close();

?>