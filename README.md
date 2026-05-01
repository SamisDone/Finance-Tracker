# FinPulse

A lightweight, secure personal finance management application built with PHP. Track income, expenses, budgets, and savings goals with ease.

## Features

- **Income Tracking** - Log earnings, recurring income, and manage sources
- **Expense Tracking** - Record spending, upload receipts, track recurring bills
- **Budget Management** - Create, edit, and monitor budgets with category-wise limits
- **Savings & Goals** - Track savings accounts and visualize financial goal progress
- **Dashboard** - Monthly snapshot with income, expenses, balance, and recent transactions
- **Reports** - Visual reports with Chart.js (pie/bar/line charts)
- **Search & Pagination** - Find entries quickly with search and browse with pagination
- **User Authentication** - Secure login with rate limiting and CSRF protection

## Tech Stack

- **Backend:** PHP 8+ (PDO for database access)
- **Database:** SQLite (default, portable) or MySQL
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Libraries:** Font Awesome 6.5.0, Chart.js

## Quick Start (Easy Deployment)

### Option 1: Simple Setup (Recommended)

1. Extract the project to your web server directory (e.g., `htdocs` for XAMPP)
2. Access the setup page in your browser:
   ```
   http://localhost/Finance-Tracker/setup.php
   ```
3. Choose SQLite (no database server needed) or MySQL
4. **🔒 SECURITY:** The `setup.php` file will **auto-delete** after setup
5. If it doesn't delete automatically, **manually delete** `setup.php` immediately
6. Start using the app at `http://localhost/Finance-Tracker/`

### Option 2: Manual Setup

1. Copy `.env.example` to `.env` in the project root
2. Configure your database settings (SQLite works out of the box)
3. Ensure `uploads/` directory is writable
4. Access the application via your web browser

## Database Options

### SQLite (Default - Portable)
- No database server required
- Database file auto-created on first run
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

## Project Structure

```
Finance-Tracker/
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── images/avatars/     # Place avatar images here
├── database/
│   ├── schema.sql          # MySQL schema
│   └── schema_sqlite.sql  # SQLite schema
├── includes/
│   ├── db.php             # Database connection
│   ├── auth_functions.php # Authentication & CSRF
│   ├── helpers.php        # Reusable helper functions (DRY)
│   ├── hero_section.php   # Reusable hero component (DRY)
│   ├── header.php
│   └── footer.php
├── uploads/
│   └── receipts/          # Auto-created for receipt uploads
├── .env                   # Environment configuration
├── .gitignore
├── setup.php              # One-time setup script (delete after use)
└── [Pages: index.php, dashboard.php, income.php, expenses.php, budgets.php, savings.php, profile.php, reports.php]
```

## Security Features

- **CSRF Protection** - All forms protected with tokens
- **Rate Limiting** - Login attempts limited (5 attempts, 15-min lockout)
- **Password Policy** - Minimum 8 chars, uppercase, number, and special character required
- **Session Security** - Session regeneration on login
- **File Upload Security** - Validates file type, size, and sanitizes filenames
- **SQL Injection Protection** - Uses prepared statements throughout
- **XSS Protection** - Output escaping with `htmlspecialchars()`

## Code Quality Principles

Following **DRY**, **KISS**, and **YAGNI**:

- **DRY (Don't Repeat Yourself)** - Common logic extracted to `includes/helpers.php`
  - `getOrCreateId()` - Reusable function for category/source creation
  - `renderPagination()` - Pagination HTML generator
  - `renderSearchForm()` - Search form generator
  - `insertDefaultCategories()` - Default data insertion

- **KISS (Keep It Simple, Stupid)** - Simple, readable code without over-engineering

- **YAGNI (You Aren't Gonna Need It)** - Only implemented requested features, no bloat

## Features Implemented

### Completed
- [x] User registration and login
- [x] Income tracking with categories and sources
- [x] Expense tracking with receipt uploads
- [x] Budget creation and editing
- [x] Savings accounts management
- [x] Financial goals with progress tracking
- [x] Dashboard with monthly summary
- [x] Search and pagination for income/expenses
- [x] CSRF protection on all forms
- [x] Rate limiting on login
- [x] SQLite database support (portable deployment)
- [x] Auto-setup script

### Known Limitations
- No email verification for registration
- No recurring transaction automation (schema ready)
- Reports limited to expense visualizations
- No export to PDF (button present but disabled)

## Deployment Notes

### For Shared Hosting:
1. Upload files via FTP
2. No database setup needed (SQLite works in most environments)
3. Ensure `uploads/` directory is writable
4. Run `setup.php` and then delete it

### For VPS/Dedicated Server:
- You can use either SQLite or MySQL
- Consider setting up HTTPS
- Configure proper file permissions

## Environment Variables (.env)

```env
# Database Type: sqlite (default) or mysql
DB_TYPE=sqlite

# SQLite Configuration (used when DB_TYPE=sqlite)
DB_PATH=finance_tracker.db

# MySQL Configuration (used when DB_TYPE=mysql)
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=finance_tracker_db
```

## Default Behavior

- New users can register with strong password requirements
- Expenses and income entries support recurring transactions
- Receipt uploads limited to 5MB (JPG, PNG, GIF, PDF)
- Budgets can be created with category-wise limits
- Financial goals calculate progress automatically
- Dashboard shows dynamic progress (not hardcoded)

## Estimated Values

The codebase now contains:
- ~15 PHP files (down from original bloat)
- ~300 lines in helpers.php (replacing duplicated code)
- Reusable hero section component (saves ~300 lines)
- Pagination on income/expenses (10 records per page)
- Working search across all major pages

## License

MIT License - Feel free to modify and use for personal or commercial projects.

## Contributing

Contributions welcome! Please feel free to submit a Pull Request.

## Support

For issues or questions, please check the code comments or open an issue on the project repository.
