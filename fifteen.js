"use strict";

window.onload = function () {
  const size = (typeof USER_PREFS !== "undefined" && USER_PREFS.size) || "4x4";
  const gridSize = parseInt(size.split("x")[0]);
  const tileSize = 400 / gridSize;

  let moveCount = 0;
  let startTime = null;
  const puzzleArea = document.getElementById("puzzlearea");
  const tiles = [];
  let blankX = 400 - tileSize;
  let blankY = 400 - tileSize;
  let gameStarted = false;
  let timerInterval = null;

  // HUD Elements
  const timeDisplay = document.getElementById("time-counter");
  const moveDisplay = document.getElementById("move-counter");
  const winMessage = document.getElementById("win-message");
  const playAgainBtn = document.getElementById("play-again-btn");
  const closeWinBtn = document.getElementById("close-win-btn");
  const cheatBtn = document.getElementById("cheat-button");

  // Audio
  const soundEnabled = (typeof USER_PREFS !== "undefined" && USER_PREFS.sound) || false;
  const animationsEnabled = (typeof USER_PREFS !== "undefined" && USER_PREFS.animations) || false;
  const moveSound = new Audio("sounds/move.mp3");
  const winSound = new Audio("sounds/win.mp3");
  const againSound = new Audio("sounds/again.mp3");

  // Create tiles
  let count = 1;
  for (let row = 0; row < gridSize; row++) {
    for (let col = 0; col < gridSize; col++) {
      if (count <= gridSize * gridSize - 1) {
        const tile = document.createElement("div");
        tile.className = "puzzlepiece";
        tile.textContent = count;

        const x = col * tileSize;
        const y = row * tileSize;

        tile.style.left = x + "px";
        tile.style.top = y + "px";
        tile.style.width = tileSize + "px";
        tile.style.height = tileSize + "px";
        tile.style.backgroundSize = "400px 400px";
        tile.style.backgroundPosition = `-${x}px -${y}px`;
        tile.style.backgroundImage = "url('img/background.jpg')";
        tile.style.position = "absolute";
        tile.style.cursor = "pointer";

        puzzleArea.appendChild(tile);
        tiles.push(tile);
        count++;
      }
    }
  }

  applyAnimationSetting();

  function applyAnimationSetting() {
    tiles.forEach(tile => {
      if (animationsEnabled) {
        tile.style.transition = "left 0.3s, top 0.3s";
      } else {
        tile.style.transition = "none";
      }
    });
  }

  // Tile interaction
  tiles.forEach(tile => {
    tile.addEventListener("click", () => {
      if (isMovable(tile)) moveTile(tile);
    });
    tile.addEventListener("mouseover", () => {
      if (isMovable(tile)) tile.classList.add("movablepiece");
    });
    tile.addEventListener("mouseout", () => {
      tile.classList.remove("movablepiece");
    });
  });

  document.getElementById("shufflebutton").addEventListener("click", shuffle);

  // Background selector
  const dropdown = document.getElementById("bg-select");
  if (dropdown) {
    dropdown.addEventListener("change", function () {
      changeBackground(this.value);
    });
    changeBackground(dropdown.value);
  }

  // Play Again / Close / Cheat button
  if (playAgainBtn) {
    playAgainBtn.addEventListener("click", () => {
      if (soundEnabled) againSound.play();
      hideWinMessage();
      const oldStats = document.getElementById("stats-block");
      if (oldStats) oldStats.remove();
      shuffle();
    });
  }

  if (closeWinBtn) {
    closeWinBtn.addEventListener("click", hideWinMessage);
  }

  if (cheatBtn) {
    cheatBtn.addEventListener("click", () => {
      // Arrange all tiles into solved positions
      for (let i = 0; i < tiles.length; i++) {
        const tile = tiles[i];
        const correctX = (i % gridSize) * tileSize;
        const correctY = Math.floor(i / gridSize) * tileSize;
        tile.style.left = correctX + "px";
        tile.style.top = correctY + "px";
      }

      // Place blank in bottom-right
      blankX = 400 - tileSize;
      blankY = 400 - tileSize;

      // Stop timer
      clearInterval(timerInterval);

      // Reset game state
      gameStarted = false;
      moveCount = 0;
      moveDisplay.textContent = 0;
      timeDisplay.textContent = 0;

      // Hide win message and cheat button
      winMessage.style.display = "none";
      cheatBtn.style.display = "none";

      alert("Puzzle solved using cheat mode (stats won't be recorded.)");
    });
  }

  // --- Game Logic ---

  // Check if tile is in same row or column as the blank tile
  function isMovable(tile) {
    const x = parseInt(tile.style.left);
    const y = parseInt(tile.style.top);
    return (x === blankX || y === blankY);
  }

  // Slide all tiles in line toward the blank space
  function moveTile(tile) {
    const x = parseInt(tile.style.left);
    const y = parseInt(tile.style.top);
    const tilesToMove = [];

    if (y === blankY) {
      const dir = x < blankX ? 1 : -1;
      for (let i = blankX - dir * tileSize; dir === 1 ? i >= x : i <= x; i -= dir * tileSize) {
        const t = getTileAt(i, y);
        if (t) tilesToMove.push(t);
      }
    } else if (x === blankX) {
      const dir = y < blankY ? 1 : -1;
      for (let i = blankY - dir * tileSize; dir === 1 ? i >= y : i <= y; i -= dir * tileSize) {
        const t = getTileAt(x, i);
        if (t) tilesToMove.push(t);
      }
    }

    tilesToMove.forEach(t => {
      const tempX = parseInt(t.style.left);
      const tempY = parseInt(t.style.top);
      t.style.left = blankX + "px";
      t.style.top = blankY + "px";
      blankX = tempX;
      blankY = tempY;
    });

    moveCount += tilesToMove.length;
    moveDisplay.textContent = moveCount;
    if (soundEnabled) moveSound.play();
    checkIfSolved();
  }

  // Return tile at a specific (x,y) position
  function getTileAt(x, y) {
    return tiles.find(t =>
      parseInt(t.style.left) === x &&
      parseInt(t.style.top) === y
    );
  }

  function shuffle() {
    let moves = 300;
    while (moves > 0) {
      const movableTiles = tiles.filter(isMovable);
      if (movableTiles.length > 0) {
        const randomTile = movableTiles[Math.floor(Math.random() * movableTiles.length)];
        moveTile(randomTile);
      }
      moves--;
    }

    gameStarted = true;
    moveCount = 0;
    startTime = Date.now();
    moveDisplay.textContent = 0;
    winMessage.style.display = "none";
    cheatBtn.style.display = "inline-block";

    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      const elapsed = Math.floor((Date.now() - startTime) / 1000);
      timeDisplay.textContent = elapsed;
    }, 1000);

    if (dropdown) changeBackground(dropdown.value);
  }

  function checkIfSolved() {
    if (!gameStarted) return;

    let isSolved = true;
    tiles.forEach((tile, index) => {
      const correctX = (index % gridSize) * tileSize;
      const correctY = Math.floor(index / gridSize) * tileSize;
      const x = parseInt(tile.style.left);
      const y = parseInt(tile.style.top);
      if (x !== correctX || y !== correctY) isSolved = false;
    });

    if (isSolved && blankX === 400 - tileSize && blankY === 400 - tileSize) {
      clearInterval(timerInterval);
      winMessage.style.display = "flex";
      cheatBtn.style.display = "none";
      if (soundEnabled) winSound.play();

      const timeTaken = Math.floor((Date.now() - startTime) / 1000);
      const bgDropdown = document.getElementById("bg-select");
      const backgroundPath = bgDropdown ? bgDropdown.value : "";
      sendGameStats(timeTaken, moveCount, backgroundPath);

      fetch(`get_global_best.php`)
        .then(res => res.json())
        .then(data => {
          const currentStats = `⏱️ Time: ${timeTaken}s<br>🔢 Moves: ${moveCount}`;
          const bestStats = data && data.best_time !== null
            ? `🏆 Best Time: ${data.best_time}s<br>📉 Best Moves: ${data.best_moves}<br>👤 Player: ${data.best_user}`
            : `No winning records yet.`;

          const winContent = document.querySelector("#win-message .win-content");
          const statsBlock = document.getElementById("stats-block");
          if (statsBlock) statsBlock.remove(); // Clear old stats

          winContent.insertAdjacentHTML("beforeend", `
            <div id="stats-block" style="margin-top: 20px; text-align: center;">
              <p><strong>Your Stats:</strong><br>${currentStats}</p>
              <p><strong>All-Time Best:</strong><br>${bestStats}</p>
            </div>
          `);
        });
    } else {
      winMessage.style.display = "none";
    }
  }
};

// Helper to change tile background
function changeBackground(imagePath) {
  const tiles = document.querySelectorAll(".puzzlepiece");
  const gridSize = Math.sqrt(tiles.length + 1);
  const tileSize = 400 / gridSize;

  tiles.forEach((tile, index) => {
    const x = (index % gridSize) * tileSize;
    const y = Math.floor(index / gridSize) * tileSize;

    tile.style.backgroundImage = imagePath ? `url('${imagePath}')` : "url('img/background.jpg')";
    tile.style.backgroundSize = "400px 400px";
    tile.style.backgroundPosition = `-${x}px -${y}px`;
  });
}

function sendGameStats(timeTaken, movesCount, backgroundPath) {
  fetch("save_game_stats.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ time_taken: timeTaken, moves: movesCount, win: true, background: backgroundPath })
  });
}

function hideWinMessage() {
  document.getElementById("win-message").style.display = "none";
}

window.shuffle = shuffle;
window.hideWinMessage = hideWinMessage;