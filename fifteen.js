"use strict";

window.onload = function () {
  // After shuffle, cheat is allowed until the first manual move
  let canUseCheat   = false;
  let cheatUsed     = false; // block fake wins

  // Puzzle prefs
  const size      = (typeof USER_PREFS !== "undefined" && USER_PREFS.size) || "4x4";
  const gridSize  = parseInt(size.split("x")[0], 10);
  const tileSize  = 400 / gridSize;

  // Game state
  let moveCount      = 0;
  let startTime      = null;
  let gameStarted    = false;
  let timerInterval  = null;
  let shuffleHistory = [];

  // DOM refs
  const puzzleArea   = document.getElementById("puzzlearea");
  const timeDisplay  = document.getElementById("time-counter");
  const moveDisplay  = document.getElementById("move-counter");
  const winMessage   = document.getElementById("win-message");
  const playAgainBtn = document.getElementById("play-again-btn");
  const closeWinBtn  = document.getElementById("close-win-btn");
  const cheatBtn     = document.getElementById("cheat-button");

  // Audio & UI prefs
  const soundEnabled      = (typeof USER_PREFS !== "undefined" && USER_PREFS.sound)      || false;
  const animationsEnabled = (typeof USER_PREFS !== "undefined" && USER_PREFS.animations) || false;
  const moveSound  = new Audio("sounds/move.mp3");
  const winSound   = new Audio("sounds/win.mp3");
  const againSound = new Audio("sounds/again.mp3");

  // Blank tile coords
  let blankX = 400 - tileSize;
  let blankY = 400 - tileSize;

  // Build initial solved puzzle
  const tiles = [];
  let count = 1;
  for (let row = 0; row < gridSize; row++) {
    for (let col = 0; col < gridSize; col++) {
      if (count <= gridSize * gridSize - 1) {
        const tile = document.createElement("div");
        tile.className   = "puzzlepiece";
        tile.textContent = count++;

        const x = col * tileSize;
        const y = row * tileSize;

        Object.assign(tile.style, {
          left:            x + "px",
          top:             y + "px",
          width:           tileSize + "px",
          height:          tileSize + "px",
          backgroundSize:  "400px 400px",
          backgroundPosition: `-${x}px -${y}px`,
          backgroundImage: USER_PREFS?.background
            ? `url('${USER_PREFS.background}')`
            : "url('img/background.jpg')",
          position:        "absolute",
          cursor:          "pointer"
        });

        if (animationsEnabled) {
          tile.style.transition = "left 0.3s, top 0.3s";
        }

        puzzleArea.appendChild(tile);
        tiles.push(tile);
      }
    }
  }

  // --- NEW: Multi-Tile Slide Helper ---
  function getTilesToSlide(tile) {
    const x  = parseInt(tile.style.left, 10);
    const y  = parseInt(tile.style.top,  10);
    const dx = x - blankX;
    const dy = y - blankY;

    // must share a row or column
    if (dx !== 0 && dy !== 0) return [];

    // how many steps away?
    const steps = dx !== 0
      ? Math.abs(dx / tileSize)
      : Math.abs(dy / tileSize);
    if (!Number.isInteger(steps) || steps === 0) return [];

    const dirX = dx === 0 ? 0 : dx > 0 ? 1 : -1;
    const dirY = dy === 0 ? 0 : dy > 0 ? 1 : -1;
    const path = [];

    // collect each tile from blank → clicked
    for (let i = 1; i <= steps; i++) {
      const px = blankX + dirX * tileSize * i;
      const py = blankY + dirY * tileSize * i;
      const t = tiles.find(t =>
        parseInt(t.style.left, 10) === px &&
        parseInt(t.style.top,  10) === py
      );
      if (!t) return []; // gap in line blocks the move
      path.push(t);
    }
    return path;
  }

  // Core move & check helpers
  function isMovable(tile) {
    // still used for shuffle logic
    const x  = parseInt(tile.style.left, 10);
    const y  = parseInt(tile.style.top, 10);
    const dx = Math.abs(x - blankX);
    const dy = Math.abs(y - blankY);
    return dx + dy === tileSize;
  }

  function performMove(tile, { count, sound }) {
    const x = parseInt(tile.style.left, 10);
    const y = parseInt(tile.style.top, 10);

    tile.style.left = blankX + "px";
    tile.style.top  = blankY + "px";

    blankX = x;
    blankY = y;

    if (count) {
      moveCount++;
      moveDisplay.textContent = moveCount;
    }
    if (sound && soundEnabled) {
      moveSound.play();
    }
    if (count) checkIfSolved();
  }

  function shuffleMove(tile) {
    performMove(tile, { count: false, sound: false });
  }

  // Tile event listeners now support multi-slide
  tiles.forEach(tile => {
    tile.addEventListener("click", () => {
      const path = getTilesToSlide(tile);
      if (!path.length) return;

      if (canUseCheat) {
        canUseCheat = false;
        disableCheatBtn();
      }

      // slide each in sequence (300ms apart if animated)
      path.forEach((t, i) => {
        setTimeout(() => {
          const isLast = i === path.length - 1;
          performMove(t, { count: isLast, sound: isLast });// count every click only
          //performMove(t, { count: true,  sound: true  });// if want to count every tile move
        }, i * (animationsEnabled ? 300 : 0));
      });
    });

    tile.addEventListener("mouseover", () => {
      if (getTilesToSlide(tile).length) {
        tile.classList.add("movablepiece");
      }
    });
    tile.addEventListener("mouseout", () => {
      tile.classList.remove("movablepiece");
    });
  });

  // Shuffle button
  document.getElementById("shufflebutton")
          .addEventListener("click", shuffle);

  // Play Again
  if (playAgainBtn) {
    playAgainBtn.addEventListener("click", () => {
      if (soundEnabled) againSound.play();
      hideWinMessage();
      shuffle();
    });
  }

  // Close Win Dialog
  if (closeWinBtn) {
    closeWinBtn.addEventListener("click", hideWinMessage);
  }

  // Cheat: undo-shuffle
  if (cheatBtn) {
    disableCheatBtn();
    cheatBtn.addEventListener("click", () => {
      if (!gameStarted || shuffleHistory.length === 0 || !canUseCheat) return;
      cheatUsed = true;
      disableCheatBtn();

      let idx = shuffleHistory.length - 1;
      (function step() {
        if (idx < 0) {
          enableCheatBtn();
          return;
        }
        shuffleMove(shuffleHistory[idx--]);
        setTimeout(step, 100);
      })();
    });
  }

  // Main shuffle routine
  function shuffle() {
    shuffleHistory = [];
    gameStarted    = true;
    moveCount      = 0;
    moveDisplay.textContent = "0";
    startTime      = Date.now();
    clearInterval(timerInterval);
    timerInterval = setInterval(updateTimer, 1000);

    cheatUsed   = false;
    canUseCheat = true;
    enableCheatBtn();

    winMessage.style.display = "none";
    const oldStats = winMessage.querySelector(".stats-block");
    if (oldStats) oldStats.remove();

    let moves = 300;
    while (moves--) {
      const movable = tiles.filter(isMovable);
      const pick    = movable[Math.floor(Math.random() * movable.length)];
      shuffleHistory.push(pick);
      shuffleMove(pick);
    }
  }

  function updateTimer() {
    timeDisplay.textContent = Math.floor((Date.now() - startTime) / 1000);
  }

  function checkIfSolved() {
    if (!gameStarted || cheatUsed) return;

    let solved = tiles.every((tile, i) => {
      const correctX = (i % gridSize) * tileSize;
      const correctY = Math.floor(i / gridSize) * tileSize;
      return parseInt(tile.style.left, 10) === correctX &&
             parseInt(tile.style.top,  10) === correctY;
    });

    if (solved && blankX === 400 - tileSize && blankY === 400 - tileSize) {
      clearInterval(timerInterval);
      winMessage.style.display = "flex";
      cheatBtn.style.display   = "none";
      if (soundEnabled) winSound.play();

      const timeTaken      = Math.floor((Date.now() - startTime) / 1000);
      const bgDropdown     = document.getElementById("bg-select");
      const backgroundPath = bgDropdown ? bgDropdown.value : "";
      sendGameStats(timeTaken, moveCount, backgroundPath);

      const winContent = winMessage.querySelector(".win-content");
      if (winContent.querySelector(".stats-block"))
        winContent.querySelector(".stats-block").remove();

      fetch("get_global_best.php")
        .then(res => res.json())
        .then(data => {
          const yourStats = `⏱ Time: ${timeTaken}s<br>🔢 Moves: ${moveCount}`;
          const bestStats = data.best_time !== null
            ? `🏆 Best Time: ${data.best_time}s<br>📉 Best Moves: ${data.best_moves}<br>👤 Player: ${data.best_user}`
            : `No winning records yet.`;

          winContent.insertAdjacentHTML("beforeend", `
            <div class="stats-block" style="margin-top:20px; text-align:center;">
              <p><strong>Your Stats:</strong><br>${yourStats}</p>
              <p><strong>🌟 All-Time Best:</strong><br>${bestStats}</p>
            </div>
          `);
        });
    } else {
      winMessage.style.display = "none";
    }
  }

  // Cheat-button styling
  function disableCheatBtn() {
    cheatBtn.disabled     = true;
    cheatBtn.style.opacity = "0.5";
    cheatBtn.style.cursor  = "not-allowed";
  }
  function enableCheatBtn() {
    cheatBtn.disabled     = false;
    cheatBtn.style.opacity = "1";
    cheatBtn.style.cursor  = "pointer";
    cheatBtn.style.display = "";
  }

  // Expose for HTML buttons
  window.shuffle        = shuffle;
  window.hideWinMessage = hideWinMessage;
};

// Helpers outside onload
function changeBackground(imagePath) {
  const tiles    = document.querySelectorAll(".puzzlepiece");
  const gridSize = Math.sqrt(tiles.length + 1);
  const tileSize = 400 / gridSize;

  tiles.forEach((tile, i) => {
    const x = (i % gridSize) * tileSize;
    const y = Math.floor(i / gridSize) * tileSize;
    tile.style.backgroundImage    = imagePath
      ? `url('${imagePath}')`
      : "url('img/background.jpg')";
    tile.style.backgroundSize     = "400px 400px";
    tile.style.backgroundPosition = `-${x}px -${y}px`;
  });
}

function sendGameStats(timeTaken, movesCount, backgroundPath) {
  fetch("save_game_stats.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      time_taken: timeTaken,
      moves:      movesCount,
      win:        true,
      background: backgroundPath
    })
  })
  .then(res => {
    if (!res.ok) throw new Error(res.statusText);
    return res.json();
  })
  .catch(err => console.error("Stats save failed:", err));
}

function hideWinMessage() {
  document.getElementById("win-message").style.display = "none";
}