# 🧩 Fifteen Puzzle

A classic sliding tile puzzle built with **PHP**, **HTML**, **CSS**, and **JavaScript**.  
The objective is to arrange 15 numbered tiles into sequential order by sliding them into the empty space — with smooth animations, a custom background image, and win detection.

---

## 🎯 Features

- ✅ 4×4 puzzle grid with 15 sliding tiles  
- ✅ Fully styled tiles with background image slicing  
- ✅ Shuffling logic that always produces a solvable board  
- ✅ Tile hover highlights for movable pieces  
- ✅ Smooth animated movement using CSS transitions  
- ✅ End-of-game notification when the puzzle is solved  

---

## 🚀 How to Run

1. Clone or download this repository  
2. Place your assets in the structure below:

```bash
/fifteen-puzzle/
├── admin.php
├── db.php
├── fifteen.js
├── fifteen.php
├── fifteen.js
├── login.php
├── logout.php
├── register.php
├── style.css
├── upload_image.php
├── uploads/
└── img/
    ├── background.jpg
    ├── valid-xhtml11.png
    ├── valid-css.png
    └── valid-jslint.png
```

3. Open `login.php` in any modern web browser.

---

## 🧠 How It Works

- Each tile is positioned using `absolute` layout and updated via JavaScript  
- The shuffle algorithm simulates 300 legal moves to ensure solvability  
- The game tracks the blank tile’s position and compares all tiles to their correct positions to determine win state  
- CSS transitions animate tile movement smoothly
- 

---

## 📚 Technologies Used

- HTML5  
- CSS3 (Flexbox, background slicing, transitions)  
- JavaScript (DOM manipulation, event handling)
- PHP  

---

## ✅ Extra Features Implemented

- 🎉 End-of-game notification — Displays a message when the puzzle is solved  
- 💨 Tile movement animations — CSS-based smooth transitions  
- 👨🏽‍💻 Database management ( Currently Under implementation )
---
