# StudentHub Portal

StudentHub is a PHP and MySQL student portal with a responsive, grayscale interface. It includes public campus pages, student registration and login, a role-protected student dashboard, and an AdminLTE-based administration area.

## Features

- Public Home, About, Events, Notices, Student Directory, FAQs, Feedback, and Contact pages.
- Student registration, password-hashed login, session management, logout, and profile editing.
- Student dashboard with profile, notices, attendance, assignments, and event-registration information.
- Directory and FAQ content loaded from JSON, with search and other client-side interactions.
- MySQL-backed events, student event RSVPs, notices, feedback, and contact messages.
- AdminLTE 4 / Bootstrap 5 admin dashboard for student and event management, notice publishing, message moderation, statistics, and audit history.
- Server-side role checks: student accounts cannot access admin pages, actions, or the event API.
- CSRF protection for state-changing forms and API requests.
- Event poster upload validation and executable-upload protection.
- Responsive black, white, and gray styling, with a light/dark theme preference.

## Technology

| Technology | Use |
| --- | --- |
| HTML5 and CSS3 | Public pages, layouts, responsive and grayscale styling |
| JavaScript | Client-side interactions, form security, JSON rendering, and admin event management |
| PHP 8.1+ | Authentication, sessions, server-side forms, authorization, and page rendering |
| MySQL / MariaDB | Accounts, events, notices, registrations, messages, and audit history |
| Bootstrap 5, Bootstrap Icons, AdminLTE 4 | Responsive administrator interface |
| JSON and Fetch API | Public directory, FAQ content, and sample event data |

Bootstrap and AdminLTE are referenced from public CDNs for the admin interface, so an internet connection is needed for those assets unless they are hosted locally.

## Project structure

```text
Student Hub/
├── index.html                 # Public landing page
├── calculator.html            # Standalone project/practice page
├── js.html                    # Standalone project/practice page
├── logo.jpeg
├── student_hub.sql            # Database schema and seed data
├── README.md
├── data/
│   ├── events.json            # Sample JSON events
│   ├── faqs.json              # FAQ data
│   └── students.json          # Public student directory data
├── docs/
│   └── project-guide.md       # Setup guide, sitemap, data model, feature map
├── js/
│   ├── admin-events.js        # Admin event CRUD and API requests
│   ├── faqs.js                # FAQ loading, search, and interaction
│   ├── form-security.js       # CSRF token setup for forms
│   ├── home-stats.js          # Home-page summary data
│   ├── script.js              # Shared page interactions
│   ├── student-profile.js     # Student profile interactions
│   ├── students.js            # Directory loading, filtering, sorting, and pagination
│   └── theme.js               # Theme preference
├── pages/
│   ├── about.html
│   ├── actions.php            # Form action handler
│   ├── admin.html             # Legacy redirect to admin.php
│   ├── admin.php              # Admin overview (admin role required)
│   ├── admin-events.php       # Event management (admin role required)
│   ├── admin-students.php     # Student management (admin role required)
│   ├── api.php                # Admin-only event API
│   ├── auth-db.php            # Shared database, session, CSRF, and role helpers
│   ├── contact.html
│   ├── csrf.php               # CSRF-token endpoint
│   ├── dashboard.html         # Legacy redirect to dashboard.php
│   ├── dashboard.php          # Student dashboard (login required)
│   ├── events.html            # Legacy redirect to events.php
│   ├── events.php
│   ├── faqs.html              # Legacy redirect to faqs.php
│   ├── faqs.php
│   ├── feedback.html
│   ├── home.html
│   ├── login.html
│   ├── login.php
│   ├── logout.php
│   ├── notices.php
│   ├── register.html
│   ├── register.php
│   ├── stats.php              # Home-page summary API
│   └── students.html
├── styles/
│   ├── about.css
│   ├── admin.css
│   ├── adminlte-overrides.css
│   ├── contact.css
│   ├── dashboard.css
│   ├── events.css
│   ├── faqs.css
│   ├── feedback.css
│   ├── home.css
│   ├── index.css
│   ├── login.css
│   ├── notices.css
│   ├── register.css
│   ├── site.css
│   └── students.css
└── uploads/
    └── events/
        └── .htaccess         # Prevents execution of uploaded files
```

## Run locally with XAMPP

1. Put the project folder inside XAMPP's `htdocs` directory. For this project, the folder is `C:\xampp\htdocs\student Hub`.
2. In the XAMPP Control Panel, start **Apache** and **MySQL**.
3. Open phpMyAdmin and import `student_hub.sql`. The script creates the `student hub` database, tables, sample events/notices, and student records.
4. Open the project at [http://localhost/student%20Hub/](http://localhost/student%20Hub/).

Use Apache to run the project. VS Code Live Server or a static file server will not execute PHP or provide MySQL sessions.

### Database connection

By default, the app uses:

| Setting | Default |
| --- | --- |
| Host | `localhost` |
| Port | `3306` |
| Database | `student hub` |
| User | `root` |
| Password | Empty |

To use different credentials, set the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` environment variables for the PHP/Apache process.

### Set up the seeded administrator

The SQL seed assigns the `admin` role to `admin@studenthub.edu`, but deliberately does not provide a shared plaintext password. Set a private local password before signing in:

1. Generate a password hash from a terminal with PHP:

   ```powershell
   C:\xampp\php\php.exe -r "echo password_hash('YOUR-PRIVATE-PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
   ```

2. In phpMyAdmin, select the `student hub` database and run the following query, replacing the placeholder with the generated hash:

   ```sql
   UPDATE students
   SET password_hash = 'PASTE_GENERATED_HASH_HERE'
   WHERE email = 'admin@studenthub.edu' AND role = 'admin';
   ```

3. Sign in at `pages/login.html` using `admin@studenthub.edu` and the private password you chose. Admin users are redirected to `pages/admin.php`; student users are redirected to `pages/dashboard.php`.

Do not use local XAMPP default database credentials or a shared demo password in a deployment. Seeded inactive sample student accounts cannot log in until an administrator sets their password and activates them.

## Page and access map

| Page / endpoint | Purpose | Access |
| --- | --- | --- |
| `index.html` | Home and public navigation | Public |
| `pages/about.html`, `contact.html`, `feedback.html` | About, contact, and feedback | Public |
| `pages/events.php`, `notices.php`, `faqs.php`, `students.html` | Events, notices, FAQs, student directory | Public |
| `pages/register.html` | Create a student account | Public |
| `pages/login.html` | Sign in | Public |
| `pages/dashboard.php` | Student dashboard and profile | Active account required |
| `pages/admin.php` | Admin overview and message/notice tools | Active admin account required |
| `pages/admin-students.php` | Student account management | Active admin account required |
| `pages/admin-events.php`, `pages/api.php` | Event management and event API | Active admin account required |
| `pages/logout.php` | End the current session | Signed-in account |

The Admin link is only displayed for an admin session on pages that show it, but hiding the link is not the security boundary: PHP checks the account role before serving admin pages, actions, or API data.

## Data sources

- MySQL is the source of truth for accounts, event RSVPs, notices, contact messages, feedback, and administrator activity.
- `data/students.json` supplies the public student directory.
- `data/faqs.json` supplies the public FAQ list.
- `data/events.json` is retained as sample JSON data; the live event page and admin event manager use MySQL.

## Verification

After setting up XAMPP, verify:

1. Public pages open from the home page and their navigation links.
2. A student can register, sign in, reach the dashboard, and sign out.
3. An admin can sign in and open the admin overview, student manager, and event manager.
4. A student or logged-out visitor is redirected or denied when trying to open admin pages directly.
5. Event RSVP, admin event create/edit/delete, notice publishing, and message status changes persist in MySQL.

The browser checks above are manual smoke-test steps; this repository does not currently include an automated test runner.

## Project guide

See [docs/project-guide.md](docs/project-guide.md) for the sitemap, database relationship model, detailed feature map, and additional XAMPP notes.

## Academic use

StudentHub is an educational semester project. It is a starting point for coursework and local demonstrations, not a hardened production deployment. Production use requires environment-specific database credentials, deployment security review, reliable backups, and operational monitoring.
