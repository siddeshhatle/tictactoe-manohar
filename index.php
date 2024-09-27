<?php
    // Include database connection
    require('./dbCon.php');

    try {
        // Fetch all records from the game_record table
        $allRecord = $conn->prepare("SELECT * FROM game_record");
        $allRecord->execute();
        $allResult = $allRecord->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Handle any errors that may occur during the query
        echo "Error: " . $e->getMessage();
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tic Tac Toe Game</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body, html {
                height: 100%;
            }
            .center-container {
                height: 100vh;
                border-radius: 15px;
                padding: 20px;
            }
            td {
                width: 100px;
                height: 100px;
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    </head>
    <body>
        <div class="d-flex justify-content-center align-items-center">
            <!-- Tic Tac Toe Board -->
            <div class="center-container col-md-6">
                <h1 class="text-center mb-4">Tic Tac Toe</h1>
                <div id="message" class="text-center mb-3"></div>
                <table class="table table-bordered text-center">
                    <?php 
                    $count = 1;
                    for($i = 1; $i <= 3; $i++) { ?>
                        <tr>
                            <?php for($j = 1; $j <= 3; $j++) { ?>
                                <td><input type="text" class="form-control text-center" id="field<?php echo $count; ?>" maxlength="1" onclick="makeMove('<?php echo $count; ?>')"></td>
                            <?php $count++; } ?>
                        </tr>
                    <?php } ?>
                </table>
            </div>

            <!-- Game Records -->
            <div class="center-container col-md-6">
                <table class="table table-bordered text-center">
                    <tr>
                        <th>Sr. No.</th>
                        <th>Name</th>
                        <th>Winner</th>
                        <th>Created On</th>
                        <th>Action</th>
                    </tr>
                    <?php 
                    $countSr = 1;
                    foreach($allResult as $resultVal) { ?>
                        <tr>
                            <td><?php echo $countSr; ?></td>
                            <td><?php echo $resultVal['record_name']; ?></td>
                            <td><?php echo $resultVal['winner']; ?></td>
                            <td><?php echo date('d M, Y', strtotime($resultVal['created_at'])); ?></td>
                            <td>
                                <?php 
                                // Fetch steps associated with each record
                                $allSteps = $conn->prepare("SELECT * FROM record_step WHERE gr_id = :gr_id ORDER BY box_count ASC");
                                $allSteps->bindParam(':gr_id', $resultVal['gr_id'], PDO::PARAM_INT);
                                $allSteps->execute();
                                $allStepsResult = $allSteps->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <table class="table table-bordered">
                                    <?php $countRecord = 0;
                                    for($i = 1; $i <= 3; $i++) { ?>
                                        <tr>
                                            <?php for($j = 1; $j <= 3; $j++) { ?>
                                                <td><?php echo $allStepsResult[$countRecord]['box_value'] ?? ''; ?></td>
                                            <?php $countRecord++; } ?>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </td>
                        </tr>
                    <?php $countSr++; } ?>
                </table>
            </div>
        </div>

        <script>
            // Class to handle Tic Tac Toe game logic
            class TicTacToe {
                constructor() {
                    this.board = ['', '', '', '', '', '', '', '', ''];
                    this.currentPlayer = 'X';
                    this.gameActive = true;
                    this.gameHistory = []; // Stores game history
                }

                makeMove(position) {
                    if (!this.gameActive || this.board[position - 1] !== '') {
                        document.getElementById('message').textContent = "Invalid move! Try again.";
                        return;
                    }

                    this.board[position - 1] = this.currentPlayer;
                    document.getElementById('field' + position).value = this.currentPlayer;
                    document.getElementById('field' + position).disabled = true;

                    this.checkWinner();
                    if (this.gameActive) {
                        this.currentPlayer = this.currentPlayer === 'X' ? 'O' : 'X';
                        document.getElementById('message').textContent = "Player " + this.currentPlayer + "'s turn";
                    }
                }

                checkWinner() {
                    const winConditions = [
                        [0, 1, 2], [3, 4, 5], [6, 7, 8],
                        [0, 3, 6], [1, 4, 7], [2, 5, 8],
                        [0, 4, 8], [2, 4, 6]
                    ];

                    let roundWon = false;
                    for (let condition of winConditions) {
                        const [a, b, c] = condition;
                        if (this.board[a] && this.board[a] === this.board[b] && this.board[a] === this.board[c]) {
                            roundWon = true;
                            break;
                        }
                    }

                    if (roundWon) {
                        document.getElementById('message').textContent = "Player " + this.currentPlayer + " wins!";
                        this.gameActive = false;
                        this.storeGameResult(`Player ${this.currentPlayer} wins`);
                        this.startConfetti();
                    } else if (!this.board.includes('')) {
                        document.getElementById('message').textContent = "It's a draw!";
                        this.gameActive = false;
                        this.storeGameResult('Draw');
                    }
                }

                storeGameResult(result) {
                    const moves = [...this.board];
                    const winner = this.currentPlayer;
                    this.gameHistory.push({ result, moves, winner });

                    // Delay the function call by 5 seconds
                    setTimeout(() => {
                        sendDataToPHP(moves, winner);
                    }, 5000);
                }

                startConfetti() {
                    this.confettiInterval = setInterval(() => {
                        confetti({ particleCount: 100, spread: 200, origin: { y: 0.6 } });
                    }, 1000);
                }

                stopConfetti() {
                    clearInterval(this.confettiInterval);
                }

                resetGame() {
                    this.board = ['', '', '', '', '', '', '', '', ''];
                    this.currentPlayer = 'X';
                    this.gameActive = true;
                    document.getElementById('message').textContent = "Player X's turn";

                    for (let i = 1; i <= 9; i++) {
                        document.getElementById('field' + i).value = '';
                        document.getElementById('field' + i).disabled = false;
                    }

                    this.stopConfetti();
                }
            }

            // Initialize game
            const game = new TicTacToe();

            // Functions to attach to buttons and fields
            function makeMove(position) { game.makeMove(position); }
            function resetGame() { game.resetGame(); }

            // Send data to PHP
            function sendDataToPHP(data, winner) {
                const payload = { gameData: data, gameWinner: winner };
                fetch('tictactoe.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => console.log('Success:', data))
                .catch(error => console.error('Error:', error));
                location.reload();
            }

            // Initialize game message
            document.getElementById('message').textContent = "Player X's turn";
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
