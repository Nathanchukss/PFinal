# 🧩 Fifteen Puzzle

A classic sliding tile puzzle built with **PHP**, **HTML**, **CSS**, and **JavaScript**.  
The objective is to arrange 15 numbered tiles into sequential order by sliding them into the empty space — with smooth animations, a custom background image, user accounts, and admin management.

---

## 🎯 Features

- ✅ 4×4, 5×5, or 6×6 puzzle grid (based on user preferences)
- ✅ Fully styled tiles with background image slicing
- ✅ Shuffling logic that always produces a solvable board
- ✅ Tile hover highlights for movable pieces
- ✅ Smooth animated movement using CSS transitions
- ✅ End-of-game notification with Play Again / Close options
- ✅ Sound effects (move, win, again) toggled via preferences

---

## 👤 User Functionality

- 🔐 Secure Registration & Login (password hashing)
-  Remember Me (auto-login with cookies)
-  Preferences panel for:
  - Puzzle size
  - Background image
  - Enable/disable sound
  - Enable/disable animations
-  Upload your own puzzle background image
-  Game stats recorded (time, moves, win status, background, date)

---

## 🛠 Admin Panel

- 👤 Manage all players:
  - View usernames, emails, registration and login dates
  - Promote/demote players to admin (except self)
  - Activate/deactivate accounts
- 🖼️ Manage uploaded images:
  - Preview
  - Toggle active/inactive
  - Delete image
- 📊 Monitor game statistics:
  - View recent plays by user, date, win status, background used

---

## 🚀 How to Run

1. Upload the project folder to your PHP server 
2. Import the required database schema (`users`, `background_images`, `game_stats`, `user_preferences`)
3. Update `db.php` with your DB credentials
4. Open `register.php` to create a user (first user can be set to admin in DB)
5. Log in, shuffle the puzzle, and enjoy!

---

## 📂 Project Structure

```bash
/fifteen-puzzle/
├── admin.php
├── db.php
├── fifteen.php
├── fifteen.js
├── login.php
├── logout.php
├── preferences.php
├── register.php
├── save_game_stats.php
├── upload_image.php
├── style.css
├── uploads/          
├── sounds/ 
    ├── again.mp3
    ├── move.mp3
    └── win.mp3
└── img/
    ├── background.jpg  # Default puzzle background
    ├── valid-css.png
    ├── valid-xhtml11.png
    └── valid-jslint.png
```

⸻

## 📚 Technologies Used
	•	HTML5 + CSS3
	•	JavaScript (DOM manipulation)
	•	PHP (Sessions, database, file uploads)
	•	MariaDB (user management, preferences, game stats)
	•	JSON (used for AJAX stats logging)

⸻

## ✅ Extra Features Implemented
	•	🎉 Win popup with Play Again & Close options
	•	💨 CSS-based animation for tile movement
	•	🔊 Sound effects 
	•	👤 Secure login, persistent auth, and role-based access
	•	🧩 Puzzle resizing logic (dynamic 4x4, 5x5, 6x6 support)
	•	🌆 User image uploads
	•	👨‍💼 Admin panel with user + image + stat management
