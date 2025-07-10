# PasteForge

🔥 **PasteForge** is a sleek, modern, user-focused pastebin platform built for today’s developers, creators, and collaborators. Designed with an engaging UI and packed with features that go far beyond traditional pastebins — all while maintaining a clean, responsive, and secure experience.

---

## 🚀 Key Features for End Users

### ✍️ **Paste Creation & Management**
- **Syntax highlighting** for 200+ languages
- **Expiration control**:
  - Burn-after-read
  - Time-limited pastes
  - Permanent pastes
- **Public/private visibility** options
- **Tags system** for organizing pastes
- **Clone and Fork**:
  - Clone for quick reuse
  - Fork with lineage tracking and attribution
- **Chain Continuation**:
  - Seamlessly continue work from any paste, creating an interconnected chain of related pastes
- **Edit support**:
  - Edit your pastes anytime
  - View version history
- **Zero Knowledge Pastes**:
  - End-to-end encryption
  - The server cannot see your content
- **Raw view & Download as file**
- **Line numbers & copy-to-clipboard** enhancements

### 👤 **User Profiles & Social Features**
- Customizable profiles with avatar support
- Recent Pastes tab on profiles
- Achievements and points system to reward engagement
- Follow other users and view their activity
- Profile statistics (paste count, views)

### 💬 **Discussions & Comments**
- **Comment system** supporting anonymous and registered users
- **Threaded replies**
- **Edit/delete your own comments**
- **Discussion threads** with full participation by registered and anonymous users
- **Registered user controls**:
  - Edit/delete your own discussion threads/posts

### 🌐 **Content Discovery & Engagement**
- View tracking and popularity metrics
- Related pastes recommendations
- Language-based grouping for easy discovery

### 🎨 **Modern UI/UX**
- Responsive design (desktop & mobile)
- Dark/light theme toggle with persistent preference
- Smooth animations and transitions
- Accessible, user-friendly interface

---

## 🔐 **Security & Privacy**
- Edit/delete restrictions for user-owned content
- Spam protection (rate limiting)
- Secure session handling
- Full privacy for Zero Knowledge pastes

---

## 🔧 Installation

1️⃣ Clone the repository:
```bash
git clone https://github.com/yourusername/pasteforge.git
cd pasteforge
```

2️⃣ Setup environment:
- PHP 8.x
- SQLite 3
- Composer (optional for dependencies):
  ```bash
  composer install
  ```

3️⃣ Initialize database:
```bash
sqlite3 pastebin.db < schema.sql
```

4️⃣ Deploy locally:
- Place the repo under your XAMPP/htdocs
- Visit `http://localhost/pasteforge`

---

## 🤝 Contributing

We welcome contributions!

- Fork the repo
- Create your branch:
  ```bash
  git checkout -b feature/your-feature
  ```
- Commit with clear messages
- Submit a pull request!

---

## 🐛 Bug Tracking

We use **Mantis** for detailed bug and feature tracking.

👉 Submit bugs here: [https://pasteforge.io/bugs/](https://pasteforge.io/bugs/)

---

## 📜 License

MIT License.

---

✨ **PasteForge is built to redefine what a modern pastebin experience should look like — join us and contribute to making it even better!**
