"use strict";

window.onload = function () {
  let moveCount = 0;
  let startTime = null;
  const puzzleArea = document.getElementById("puzzlearea");
  const tiles = [];
  let blankX = 300;
  let blankY = 300;
  let gameStarted = false;
  const userPrefs = typeof USER_PREFS !== 'undefined' ? USER_PREFS : {
    size: "4x4",
    background: "",
    sound: true,
    animations: true
  };

  const winMessage = document.getElementById("win-message");
  const playAgainBtn = document.getElementById("play-again-btn");
  const closeWinBtn = document.getElementById("close-win-btn");

  // Sound support
  const moveSound = new Audio("sounds/move.mp3");
  const winSound = new Audio("sounds/win.mp3");
  let soundEnabled = true;
  const soundToggle = document.getElementById("sound-toggle");
  if (soundToggle) {
    soundEnabled = soundToggle.checked;
    soundToggle.addEventListener("change", () => {
      soundEnabled = soundToggle.checked;
    });
  }

  let count = 1;
  for (let row = 0; row < 4; row++) {
    for (let col = 0; col < 4; col++) {
      if (count <= 15) {
        const tile = document.createElement("div");
        tile.className = "puzzlepiece";
        tile.textContent = count;

        const x = col * 100;
        const y = row * 100;

        tile.style.left = x + "px";
        tile.style.top = y + "px";
        tile.style.backgroundPosition = `-${x}px -${y}px`;
        tile.style.backgroundSize = "400px 400px";
        tile.style.backgroundImage = "url('img/background.jpg')";
        tile.style.position = "absolute";
        tile.style.cursor = "pointer";

        puzzleArea.appendChild(tile);
        tiles.push(tile);

        count++;
      }
    }
  }

  tiles.forEach(tile => {
    tile.addEventListener("click", () => {
      if (isMovable(tile)) {
        moveTile(tile);
      }
    });

    tile.addEventListener("mouseover", () => {
      if (isMovable(tile)) {
        tile.classList.add("movablepiece");
      }
    });

    tile.addEventListener("mouseout", () => {
      tile.classList.remove("movablepiece");
    });
  });

  document.getElementById("shufflebutton").addEventListener("click", shuffle);

  const dropdown = document.getElementById("bg-select");
  if (dropdown) {
    dropdown.addEventListener("change", function () {
      changeBackground(this.value);
    });
    changeBackground(dropdown.value);
  }

  if (playAgainBtn) {
    playAgainBtn.addEventListener("click", () => {
      hideWinMessage();
      shuffle();
    });
  }

  if (closeWinBtn) {
    closeWinBtn.addEventListener("click", () => {
      hideWinMessage();
    });
  }

  function isMovable(tile) {
    const x = parseInt(tile.style.left);
    const y = parseInt(tile.style.top);
    const dx = Math.abs(x - blankX);
    const dy = Math.abs(y - blankY);
    return (dx + dy === 100);
  }

  function moveTile(tile) {
    const x = parseInt(tile.style.left);
    const y = parseInt(tile.style.top);

    tile.style.left = blankX + "px";
    tile.style.top = blankY + "px";

    blankX = x;
    blankY = y;

    checkIfSolved();
    moveCount++;

    if (soundEnabled) moveSound.play();
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
      const correctX = (index % 4) * 100;
      const correctY = Math.floor(index / 4) * 100;
      const x = parseInt(tile.style.left);
      const y = parseInt(tile.style.top);

      if (x !== correctX || y !== correctY) {
        isSolved = false;
      }
    });

    if (isSolved && blankX === 300 && blankY === 300) {
      winMessage.style.display = "flex";

      const timeTaken = Math.floor((Date.now() - startTime) / 1000);
      const bgDropdown = document.getElementById("bg-select");
      const backgroundPath = bgDropdown ? bgDropdown.value : "";

      sendGameStats(timeTaken, moveCount, backgroundPath);
      if (soundEnabled) winSound.play();
    } else {
      winMessage.style.display = "none";
    }
  }
};

function changeBackground(imagePath) {
  const tiles = document.querySelectorAll(".puzzlepiece");

  tiles.forEach((tile, index) => {
    const x = (index % 4) * 100;
    const y = Math.floor(index / 4) * 100;

    tile.style.backgroundImage = imagePath ? `url('${imagePath}')` : "url('img/background.jpg')";
    tile.style.backgroundSize = "400px 400px";
    tile.style.backgroundPosition = `-${x}px -${y}px`;
  });
}

function sendGameStats(timeTaken, movesCount, backgroundPath) {
  fetch("save_game_stats.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      time_taken: timeTaken,
      moves: movesCount,
      win: true,
      background: backgroundPath
    })
  });
}

function hideWinMessage() {
  const winMessage = document.getElementById("win-message");
  if (winMessage) winMessage.style.display = "none";
}