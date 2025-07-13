# Who Wants to Be a Millionaire? — Web Quiz Game

A web-based version of the classic game show **Who Wants to Be a Millionaire?**  
Built using **PHP**, **HTML**, and **CSS**.

---
## 👥 Developers

- Gowtham Patchigolla  
- Silvia Juyal  

---

## Features

- Multiple-choice quiz with 5 questions of increasing difficulty.
- Game show-inspired design with glowing answer effects.
- Audio effects and animated backgrounds for an immersive experience.
- "Quit" option at each stage — take your winnings or risk it all!
- Leaderboard to track top scores.
- Responsive and visually engaging UI.

---

## Highlights

- **Game Show Aesthetics**: Gold-themed glowing UI inspired by the original TV show.
- **Confetti Animation**: Visual celebration effect when the user wins.
- **Immersive Sound Design**: Built-in sound effects for correct answers, wrong answers, and suspenseful transitions.
- **Feedback Mechanism**: Real-time visual feedback (green/red glow) for user answers.
- **Mobile-Responsive Design**: CSS media queries ensure optimal play on mobile and tablet.
- **Persistent Leaderboard**: Stores player scores and ranks using text files via PHP.


---

## How to Run

1. **Clone the repository:**
   ```sh
   git clone https://github.com/Gowtham0436/webpro.git
   ```
2. **Place the project in your web server directory** (e.g., `htdocs` for XAMPP, `public_html` for cPanel, or your server's `www`).
3. **Make sure PHP is installed and running.**
4. **Access the game in your browser:**  
   ```
   http://localhost/<your-folder>/index.html
   ```
5. **Register a player and start the game!**

---

## Project Structure

- `/data/` — Stores player data, audio, and background GIFs.
- `/css/` — Stylesheets for the quiz and info pages.
- `questionone.php` to `questionfive.php` — Main quiz questions.
- `final.php`, `wrong.php` — Endgame/feedback pages.
- `leaderboard.php` — Leaderboard display.

---



## Demo & Resources

- [Demo (Codd Server)](https://codd.cs.gsu.edu/~gpatchigolla1/webpro/Pw/whowantstobemillionare/index.html)
- [YouTube Presentation](https://www.youtube.com/)
- [GitHub Repo](https://github.com/Gowtham0436/webpro/tree/project)
- [PowerPoint Presentation](https://docs.google.com/presentation/d/1gyTcUPaBsz2uDh2pqFsoSZ-peWQibwkh/edit?usp=sharing&ouid=103320529633586249533&rtpof=true&sd=true)

---

## Testing Strategy

- All game logic and transitions tested on Chrome, Firefox, and Edge.
- CSS validated using W3C Validator (minor exceptions for animations).
- PHP tested with local XAMPP server.
- Mobile responsiveness tested on iOS Safari and Android Chrome.

## License

This project is for educational purposes.