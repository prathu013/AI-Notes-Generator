# 🧠 AI Notes Generator

<div align="center">

### 🚀 AI-Powered Smart Study Notes Platform

Transform raw text, PDFs, lecture notes, and study materials into structured AI-generated notes using **Google Gemini 1.5 Flash**.

![PHP](https://img.shields.io/badge/PHP-8.1+-blue?style=for-the-badge)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange?style=for-the-badge)
![Gemini AI](https://img.shields.io/badge/Gemini-AI-purple?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

</div>

---

# ✨ Overview

Modern students waste hours manually creating notes.

**AI Notes Generator** automates the entire workflow by converting unstructured content into clean, organized, and intelligent study notes instantly.

### ✅ What It Can Do
- Generate AI-powered summaries
- Extract important concepts
- Create structured study notes
- Auto-generate titles & tags
- Organize notes smartly
- Search notes instantly
- Track productivity analytics

Built with **PHP + MySQL + Gemini AI**, featuring a modern glassmorphism dashboard UI.

---

# 🚀 Features

| Feature | Description |
|---|---|
| 🧠 AI Generation | Generate summaries, key points & concepts |
| 🔐 Authentication | Secure login/register system |
| 📂 Categories | Organize notes using folders |
| 📌 Pin & Archive | Manage important notes easily |
| 🔍 Search System | Fast MySQL FULLTEXT search |
| 📊 Analytics | Track note generation statistics |
| 📱 Responsive UI | Works on desktop & mobile |
| 🎨 Modern Design | Glassmorphism dark interface |

---

# 📸 Modules

## 🔐 Authentication
- Secure Login/Register
- Session-based authentication
- bcrypt password hashing
- Session protection

---

## 🧠 AI Note Generator

Paste:
- Raw text
- Lecture content
- Research material
- Study topics

Gemini AI automatically generates:
- Smart titles
- Summaries
- Key points
- Important concepts
- AI tags

---

## 📂 Dashboard
- Organized notes
- Category folders
- Search & filters
- Pinned notes
- Archive system

---

## 📊 Analytics
Track:
- Total notes generated
- Words processed
- AI token usage
- Productivity stats

---

# 🏗️ Project Structure

```bash
AI-Notes-Generator/
│
├── index.html
├── dashboard.html
├── database.sql
├── .env.example
│
├── config/
├── includes/
├── api/
└── assets/
```

---

# ⚡ Tech Stack

| Technology | Usage |
|---|---|
| PHP 8.1+ | Backend |
| MySQL 8 | Database |
| Gemini 1.5 Flash | AI Processing |
| HTML/CSS/JS | Frontend |
| PDO | Secure Queries |
| cURL | API Communication |

---

# 🔌 API Endpoints

## Authentication APIs

| Method | Endpoint |
|---|---|
| POST | `/api/auth/register.php` |
| POST | `/api/auth/login.php` |
| POST | `/api/auth/logout.php` |
| GET | `/api/auth/me.php` |

---

## Notes APIs

| Method | Endpoint |
|---|---|
| POST | `/api/notes/generate.php` |
| GET | `/api/notes/read.php` |
| PUT | `/api/notes/update.php` |
| DELETE | `/api/notes/delete.php` |

---

# 🛡️ Security Features

✔ bcrypt password hashing  
✔ PDO prepared statements  
✔ SQL injection protection  
✔ Session security  
✔ CSRF-resistant architecture  
✔ Server-side validation  

---

# ⚙️ Installation

## 1️⃣ Clone Repository

```bash
git clone https://github.com/your-username/AI-Notes-Generator.git
cd AI-Notes-Generator
```

---

## 2️⃣ Database Setup

```bash
mysql -u root -p < database.sql
```

---

## 3️⃣ Configure Environment

Copy `.env.example` → `.env`

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ai-notes-generator
DB_USER=root
DB_PASS=your_password

GEMINI_API_KEY=your_api_key
GEMINI_MODEL=gemini-1.5-flash
```

---

## 4️⃣ Run Project

### PHP Built-in Server

```bash
php -S localhost:8080
```

Open:

```bash
http://localhost:8080
```

---

# 🎨 UI Design

### Modern Glassmorphism Interface
- Dark theme UI
- Animated gradients
- Mobile responsive layout
- Smooth dashboard interactions

---

# 📈 Future Improvements

- 📄 PDF Upload Support
- ✍ OCR Handwriting Recognition
- 🎤 Voice-to-Notes
- 🧪 AI Quiz Generator
- 📥 Export to PDF
- 🌍 Multi-language Support
- 🧠 AI Flashcards
- 📅 Study Planner

---

# 💡 Ideal For

🎓 Students  
📚 Researchers  
🧠 Self-Learners  
🏫 Educational Institutes  
💼 Professionals  

---

# 🤝 Contributing

Contributions are welcome.

### Steps
1. Fork Repository
2. Create Feature Branch
3. Commit Changes
4. Push Changes
5. Open Pull Request

---

# 📜 License

MIT License — Free to use and modify.

---

# ⭐ Support

If you found this project useful:

⭐ Star the repository  
🔄 Share with developers  
🤝 Contribute improvements  

---

# 👨‍💻 Developer

## Prathamesh Sakoji

BCA Student • AI Developer • Full Stack Developer

> Building practical AI-powered solutions for modern education.
