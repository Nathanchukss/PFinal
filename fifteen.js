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

  const winMessage = document.getElementById("win-message");
  const playAgainBtn = document.getElementById("play-again-btn");
  const closeWinBtn = document.getElementById("close-win-btn");

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

        if (animationsEnabled) {
          tile.style.transition = "left 0.3s, top 0.3s";
        }

        puzzleArea.appendChild(tile);
        tiles.push(tile);
        count++;
      }
    }
  }

  // Tile interaction
  tiles.forEach(tile => {
    tile.addEventListener("click", () => {
      if (isMovable(tile)) {
        moveTile(tile);
      }
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

  // Play Again / Close buttons
  if (playAgainBtn) {
    playAgainBtn.addEventListener("click", () => {
      if (soundEnabled) againSound.play();
      hideWinMessage();
      shuffle();
    });
  }

  if (closeWinBtn) {
    closeWinBtn.addEventListener("click", () => {
      hideWinMessage();
    });
  }

  // --- Game Logic ---
  function isMovable(tile) {
    const x = parseInt(tile.style.left);
    const y = parseInt(tile.style.top);
    const dx = Math.abs(x - blankX);
    const dy = Math.abs(y - blankY);
    return (dx + dy === tileSize);
  }

  function moveTile(tile) {
    const x = parseInt(tile.style.left);
    const y = parseInt(tile.style.top);

    tile.style.left = blankX + "px";
    tile.style.top = blankY + "px";

    blankX = x;
    blankY = y;

    moveCount++;
    if (soundEnabled) moveSound.play();
    checkIfSolved();
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
    winMessage.style.display = "none";
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
      winMessage.style.display = "flex";
      if (soundEnabled) winSound.play();

      const timeTaken = Math.floor((Date.now() - startTime) / 1000);
      const bgDropdown = document.getElementById("bg-select");
      const backgroundPath = bgDropdown ? bgDropdown.value : "";
      sendGameStats(timeTaken, moveCount, backgroundPath);
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
    body: JSON.stringify({
      time_taken: timeTaken,
      moves: movesCount,
      win: true,
      background: backgroundPath
    })
  });
}

function hideWinMessage() {
  document.getElementById("win-message").style.display = "none";
}

window.shuffle = shuffle;
window.hideWinMessage = hideWinMessage;