# Finance Tracker

A web-based personal finance management application that allows users to track income, expenses, budgets, savings goals, and generate financial reports with visualizations.

## Features

- **Income Tracking** - Log earnings, recurring income, and sources
- **Expense Tracking** - Record spending, receipts, and recurring bills
- **Budgeting Tools** - Set monthly or category-wise limits with visual tracking
- **Savings & Goals** - Track savings accounts and financial goals visually
- **Dashboard** - Monthly snapshot with income, expenses, and balance
- **Reports** - Visual reports with Chart.js (pie/bar/line charts)
- **User Authentication** - Secure login with rate limiting and CSRF protection

## Tech Stack

- **Backend:** PHP 8+ (PDO for database)
- **Database:** SQLite (default, portable) or MySQL
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Libraries:** Font Awesome 6.5.0, Chart.js

## Quick Start (Easy Deployment)

1. **Download and extract** the project to your web server directory (e.g., `htdocs` for XAMPP)

2. **Configure environment** (optional - defaults to SQLite):
   - Copy `.env.example` to `.env`
   - Edit `.env` if you want to use MySQL instead of SQLite

3. **Access the application** via your web browser:
   ```
   http://localhost/Finance-Tracker/
   ```

That's it! The SQLite database will be created automatically.

## Database Options

### SQLite (Default - Portable, No Server Needed)
- No database server required
- Database file created automatically
- Perfect for shared hosting or simple deployment
- Just works out of the box

### MySQL (Optional)
If you prefer MySQL, update your `.env` file:
```env
DB_TYPE=mysql
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=finance_tracker_db
```

Then import `database/schema.sql` to your MySQL database.

## Security Features

- **CSRF Protection** - All forms protected with tokens
- **Rate Limiting** - Login attempts limited (5 attempts, 15-min lockout)
- **Password Policy** - Minimum 8 chars, uppercase, number, special character
- **Session Security** - Session regeneration on login
- **File Upload Security** - Validates file type, size, and sanitizes filenames
- **SQL Injection Protection** - Uses prepared statements throughout

## File Structure

```
Finance-Tracker/
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── images/avatars/ (create your own avatars)
├── database/
│   ├── schema.sql (MySQL)
│   └── schema_sqlite.sql (SQLite)
├── includes/
│   ├── db.php (database connection)
│   ├── auth_functions.php (authentication)
│   ├── header.php
│   ├── footer.php
│   └── hero_section.php (reusable component)
├── uploads/receipts/ (created automatically)
├── .env (create from .env.example)
└── [PHP pages: index.php, dashboard.php, income.php, expenses.php, etc.]
```

## Default Behavior

- New users can register with email verification not required
- Expenses and income entries support recurring transactions
- Receipt uploads limited to 5MB (JPG, PNG, GIF, PDF)
- Budgets can be created with category-wise limits
- Financial goals track progress automatically

## Deployment Notes

### For Shared Hosting:
1. Upload files via FTP
2. No database setup needed (SQLite works in most environments)
3. Ensure `uploads/` directory is writable

### For VPS/Dedicated Server:
- You can use either SQLite or MySQL
- Consider setting up HTTPS
- Configure proper file permissions

## License

MIT License - Feel free to modify and use for personal or commercial projects.

## Contributing

Contributions welcome! Please feel free to submit a Pull Request.
