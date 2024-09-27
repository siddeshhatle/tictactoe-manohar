<?php
require('./dbCon.php'); // Include the database connection

// Fetch the raw POST data
$json = file_get_contents('php://input');

// Decode the JSON data into a PHP associative array
$dataArray = json_decode($json, true);

// Extract game steps and the winner from the data array
$gameSteps = $dataArray['gameData'];
$winner = $dataArray['gameWinner'];

try {
    // 1. Get the count of rows in the `game_record` table
    $countRecord = $conn->prepare("SELECT COUNT(*) as total FROM game_record");
    $countRecord->execute();

    // Fetch the result to get the total number of records
    $countResult = $countRecord->fetch(PDO::FETCH_ASSOC);
    $newRecordName = "Record " . ($countResult['total'] + 1); // Generate record name

    // 2. Insert a new game record into the `game_record` table
    $recordInsert = $conn->prepare("
        INSERT INTO game_record (record_name, winner) 
        VALUES (:record_name, :winner)
    ");
    $recordInsert->bindParam(':record_name', $newRecordName);
    $recordInsert->bindParam(':winner', $winner['0']);
    $recordInsert->execute();

    // Get the ID of the last inserted record
    $last_id = $conn->lastInsertId();

    // 3. Insert each game step into the `record_step` table
    $count = 1;
    foreach ($gameSteps as $value) {
        $stepInsert = $conn->prepare("
            INSERT INTO record_step (gr_id, box_count, box_value) 
            VALUES (:gr_id, :box_count, :box_value)
        ");

        // Bind parameters
        $stepInsert->bindParam(':gr_id', $last_id);
        $stepInsert->bindParam(':box_count', $count);
        $stepInsert->bindParam(':box_value', $value);
        $stepInsert->execute();

        $count++;
    }

    // Return the ID of the last inserted record
    return $last_id;

} catch (PDOException $e) {
    // Handle errors gracefully
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
