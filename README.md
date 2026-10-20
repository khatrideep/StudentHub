# StudentHub Portal

A responsive student portal developed using **HTML5, CSS3, JavaScript, Bootstrap 5, JSON, and Git/GitHub**.

StudentHub provides a centralized interface for students to access campus information and services such as events, notices, FAQs, student profiles, feedback, and contact services.

---

## 📌 Project Overview

**StudentHub** is a modern academic portal designed to bring commonly used student services into one place.

The current version is primarily a **client-side web application**. It uses JavaScript for UI interactions, form validation, theme switching, local storage, and dynamic rendering of JSON-based data.

### Main Objectives

* Provide a centralized student portal.
* Create a clean, modern, and responsive user interface.
* Provide easy navigation between different student services.
* Display dynamic student, event, notice, and FAQ information.
* Implement client-side form validation.
* Demonstrate practical use of HTML, CSS, JavaScript, Bootstrap, JSON, and Git.
* Build a project structure that can be extended into a full-stack application.

---

## ✨ Features

### 🏠 Home Page

* StudentHub branding and navigation.
* Hero section with portal introduction.
* Quick access to major student services.
* Notification/announcement section.
* Responsive navigation.
* Light/Dark theme switcher.

### 🔐 Login

* Student login interface.
* Form handling and validation.
* Client-side demo authentication behaviour.

### 📝 Registration

* Student registration form.
* Full name validation.
* Email validation.
* Mobile number validation.
* Course and academic year selection.
* Gender selection.
* Terms & Conditions validation.
* Password confirmation.
* Password strength indicator.
* Real-time validation feedback.

### 📊 Dashboard

* Student dashboard interface.
* Student-related information.
* Quick access to important portal sections.
* Interactive UI components.

### 👨‍🎓 Student Directory

* Student information loaded dynamically from `students.json`.
* Dynamic rendering using JavaScript.
* Search/filter functionality.
* Responsive student cards/layout.

### 📅 Events

* Upcoming events read from MySQL and displayed in responsive event cards.
* Student event RSVP with registration-capacity checks.
* Admin-only asynchronous event create, edit, delete, category/status management, and poster uploads.
* `data/events.json` is a sample dataset for the JSON practical.

### 📢 Notices

* Current notices are read from MySQL.
* Administrators can publish notices from the dashboard.

### ❓ FAQ

* Frequently Asked Questions section.
* Interactive FAQ components.
* FAQ data loaded dynamically from `data/faqs.json` using Fetch.

### 👤 Profile

* Authenticated profile data read from and saved to MySQL.

### 💬 Feedback

* Feedback form.
* Client-side validation.
* Success/error feedback messages.

### 📧 Contact

* Contact form.
* User input validation.
* Interactive form handling.

### 🛠️ Admin Panel

* AdminLTE 4 and Bootstrap 5 dashboard in a grayscale theme.
* Student and event management, notice publishing, message moderation, statistics, and audit history.

### 🎨 Theme Switcher

* Light and dark theme support.
* Theme preference stored using `localStorage`.
* Theme remains available after page reload.

### 📱 Responsive Design

* Desktop responsive layout.
* Tablet responsive layout.
* Mobile responsive layout.
* Bootstrap grid system.
* Custom CSS media queries.

---

## 🧰 Technologies Used

| Technology            | Purpose                                                            |
| --------------------- | ------------------------------------------------------------------ |
| **HTML5**             | Page structure and semantic markup                                 |
| **CSS3**              | Styling, layouts, animations, and responsiveness                   |
| **JavaScript (ES6+)** | Interactivity, validation, DOM manipulation, and dynamic rendering |
| **Bootstrap 5.3**     | Responsive layouts and UI components                               |
| **Bootstrap Icons**   | Interface icons                                                    |
| **JSON**              | Static data storage                                                |
| **Fetch API**         | Loading JSON data dynamically                                      |
| **LocalStorage**      | Client-side data and theme persistence                             |
| **Git**               | Version control                                                    |
| **GitHub**            | Source code hosting                                                |

---

## 📂 Project Structure

```text
Student Hub/
│
├── index.html
├── README.md
│
├── assets/
│   ├── icons/
│   └── images/
│
├── css/
│   ├── about.css
│   ├── admin.css
│   ├── contact.css
│   ├── dashboard.css
│   ├── events.css
│   ├── faq.css
│   ├── feedback.css
│   ├── index.css
│   ├── login.css
│   ├── profile.css
│   └── register.css
│
├── data/
│   ├── events.json
│   ├── faqs.json
│   ├── notices.json
│   └── students.json
│
├── js/
│   ├── about.js
│   ├── admin.js
│   ├── contact.js
│   ├── dashboard.js
│   ├── events.js
│   ├── faq.js
│   ├── feedback.js
│   ├── index.js
│   ├── login.js
│   ├── notices.js
│   ├── profile.js
│   ├── register.js
│   └── students.js
│
└── pages/
    ├── about.html
    ├── admin.html
    ├── contact.html
    ├── dashboard.html
    ├── events.html
    ├── faq.html
    ├── feedback.html
    ├── login.html
    ├── profile.html
    └── register.html
```

---

## 📄 JSON Data Files

JSON is used for public directory/FAQ data and as a sample event dataset. Server-managed events and notices use MySQL.

### `students.json`

Stores student directory information that is dynamically loaded and displayed on the Student Directory page.

### `events.json`

Contains sample event records for the JSON practical. The live Events page reads MySQL so registrations, availability, and administrator changes stay consistent.

### `faqs.json`

Stores frequently asked questions and their answers.

Using JSON keeps the data separate from the HTML and makes the project easier to maintain and update.

---

## 🔄 Fetch API & Dynamic Rendering

The project uses the **JavaScript Fetch API** to retrieve JSON data.

The general flow is:

```text
JSON File
    ↓
Fetch API
    ↓
JavaScript
    ↓
DOM Manipulation
    ↓
Dynamic HTML Rendering
    ↓
User Interface
```

This approach loads student-directory and FAQ content without manually modifying the HTML. MySQL is the live source of truth for events and notices.

---

## 📝 Form Validation

The registration and other forms include client-side validation.

### Registration Validation

* Required field validation.
* Name validation.
* Email validation.
* Mobile number validation.
* Password validation.
* Confirm password matching.
* Course selection validation.
* Academic year validation.
* Gender selection validation.
* Terms & Conditions validation.
* Real-time validation feedback.

### Password Requirements

The password validation can require:

* Minimum 8 characters.
* At least one uppercase letter.
* At least one lowercase letter.
* At least one number.
* At least one special character.

---

## 🎨 Theme Switcher

StudentHub includes a Light/Dark theme switcher.

The selected theme is stored using:

```javascript
localStorage
```

This allows the user's selected theme to remain available after refreshing or reopening the page.

---

## 📱 Responsive Design

The website is designed to work across different screen sizes:

* 💻 Desktop
* 💻 Laptop
* 📱 Tablet
* 📱 Mobile

Responsive design is implemented using:

* Bootstrap grid system.
* Bootstrap responsive utilities.
* CSS media queries.
* Flexible layouts.
* Responsive cards and navigation.

---

## 🎯 UI/UX Design

The project focuses on:

* Clean and modern academic portal design.
* Consistent navigation.
* Responsive layouts.
* Clear form validation messages.
* Consistent typography and spacing.
* Interactive components.
* Light/Dark mode.
* User-friendly navigation.
* Mobile-friendly interface.

---

## ⚙️ How to Run the Project

### XAMPP (PHP and MySQL)

1. Install XAMPP and start **Apache** and **MySQL**.
2. Import `student_hub.sql` in phpMyAdmin.
3. Place the repository folder under XAMPP's `htdocs` directory.
4. Open `http://localhost/student%20Hub/`.
5. Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` for non-default database credentials.

The PHP authentication, event RSVP, notices, student administration, event management, and API features need Apache and MySQL. JSON-only pages can be viewed through any local HTTP server.

---

## 🧪 Testing

The project can be tested for:

### Functional Testing

* Navigation links.
* Registration form.
* Login form.
* Form validation.
* Theme switching.
* JSON data loading.
* Dynamic rendering.
* FAQ interaction.
* Event display.
* Notice display.
* Student directory display.

### Responsive Testing

The interface should be checked on:

* Desktop.
* Tablet.
* Mobile.

### Browser Testing

The project can be tested using modern browsers such as:

* Google Chrome
* Microsoft Edge
* Mozilla Firefox

---

## 🔒 Current Limitations

The current version is an academic project, not a production deployment. It uses server-side MySQL for authentication and account operations, plus JSON for selected public directory/FAQ content. It does not currently include email verification, self-service password recovery, a production mail service, or deployment hardening and monitoring.

---

## 🚀 Future Scope

Possible next steps include expanding the existing portal into a production-ready student management platform.

### Backend

* Python Flask / Node.js backend.
* REST API integration.
* SQL Server / MySQL / PostgreSQL database.
* Server-side validation.
* Secure authentication.
* Password hashing.

### Student Features

* Attendance management.
* Academic performance tracking.
* Assignment submission.
* Course management.
* Timetable management.
* Exam schedule.
* Digital notices.
* Student notifications.

### Admin Features

* Student CRUD operations.
* Event management.
* Notice management.
* FAQ management.
* User management.
* Dashboard analytics.

### Advanced Features

* Role-based access control.
* Real-time notifications.
* Student communication.
* File/document uploads.
* Email notifications.
* Cloud deployment.
* AI-powered student assistance.

---

## 🔧 Development Practices

The project follows a modular front-end structure:

* HTML files are organized inside `pages/`.
* CSS files are separated according to pages/modules.
* JavaScript files are separated according to functionality.
* JSON data is stored inside `data/`.
* Images and icons are organized inside `assets/`.
* Git is used for version control.
* GitHub is used for remote source-code hosting.

---

## 📚 Academic Concepts Demonstrated

This project demonstrates practical knowledge of:

* HTML5
* CSS3
* Responsive Web Design
* JavaScript
* DOM Manipulation
* Event Handling
* Form Validation
* Fetch API
* JSON
* LocalStorage
* Bootstrap
* CSS Media Queries
* Git
* GitHub

---

## 📌 Academic Project

StudentHub is developed as part of a **Web Designing Frameworks / Web Development practical project**.

The project demonstrates how modern front-end technologies can be combined to create a responsive and interactive student portal.

---

## 👨‍💻 Author

**StudentHub Semester Project**

Developed for academic and educational purposes.

---

## 📜 License

This project is intended for **educational and academic use**.

You may modify and extend the project for learning and demonstration purposes.

## Current full-stack implementation

The current XAMPP version includes PHP/MySQL authentication, role-protected Student Hub administration, student and event management, event RSVP, notices, audit logging, and protected event-poster uploads. Public student and FAQ content is loaded from JSON. The MySQL setup and project sitemap/database model are documented in [docs/project-guide.md](docs/project-guide.md).

Run the application through Apache and MySQL, then import `student_hub.sql`. The older Live Server and Python HTTP server instructions above apply only to the static pages; PHP-backed features require a PHP server and MySQL. Reset the seeded administrator password before use, and do not deploy with XAMPP's default database credentials.