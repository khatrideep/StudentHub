# Student Hub

## Run locally with XAMPP

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Import `student_hub.sql` in phpMyAdmin, or run:

   ```powershell
   & $env:ComSpec /c '"C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\student Hub\student_hub.sql"'
   ```

   The script creates or updates the Student Hub tables and inserts starter events, FAQs, and notices. It is safe to run again; it does not delete application data.
3. Open `http://localhost/student%20Hub/`.
4. Register a student account, then log in to open the database-backed dashboard.

The local database defaults are `localhost`, user `root`, an empty password, and database `student hub`. Set the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` environment variables for a different database. Do not use the local empty-password defaults in a production environment.

## Administrator access

New registrations always receive the student role. To grant administrator access, register a trusted account and promote it directly in MySQL:

```sql
UPDATE students
SET role = 'admin'
WHERE email = 'trusted.account@example.com';
```

Sign out and sign back in after changing the role. Administrator pages and notice publishing are protected by the server-side role check.

## Database-backed features

Registration, login, sessions, profiles, event listings and RSVPs, FAQs, feedback, contact messages, notices, and the administrator overview use MySQL. User-submitted forms are validated on the server and protected with session-backed CSRF tokens. Passwords are stored as password hashes.
