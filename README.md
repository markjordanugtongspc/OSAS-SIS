# OSAS-SIS  
**OSAS - Student Affairs System**  
A web-based system that supports **Sport Inventory System** and **Storage Management System** for St. Peter's College (DSA-OSAS).

---

## Overview

**OSAS-SIS** is designed to help the Office of Student Affairs manage two major operational areas:

- **Sport Inventory System (SIS)**  
  Tracks sports equipment items, borrowing transactions, approvals, returns, and history.

- **Storage Management System (SMS)**  
  Manages cabinets, stored documents/files, and dashboard reporting for document management.

This project runs on **PHP + MySQL (XAMPP)** and uses **Vite + TailwindCSS** for frontend assets and styling.

---

## Key Modules

### Sport Inventory System (SIS)
- Dashboard overview (charts, statistics)
- Item Inventory listing with search and filtering
- Item Management (add/edit/delete items)
- Borrowing workflow with approval system
- Transaction history and logs
- User management (Admin only)

### Storage Management System (SMS)
- SMS Dashboard (stats + charts)
- Cabinet management (view/manage cabinets)
- Papers / Documents pages
- Advanced search and export functionality
- API endpoints under `backend/CMS/api/`

### Additional Features
- User authentication and session management
- Responsive sidebar navigation with SPA-like transitions
- Real-time notifications system
- About page with team information and contact form
- FormSubmit API integration for contact forms
- SweetAlert2 for modern UI notifications

---

## Tech Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL (XAMPP / phpMyAdmin)
- **Frontend Styles:** TailwindCSS
- **Asset Bundling / Dev Server:** Vite
- **Charts:** Chart.js
- **UI Components:** Flowbite
- **Notifications:** SweetAlert2
- **Form Handling:** FormSubmit API

---

## Project Structure (high level)

```
OSAS-SIS/
├── index.php                    # Login page / entry point
├── frontend/
│   ├── pages/                   # Sport Inventory System pages
│   │   ├── dashboard.php
│   │   ├── item_inventory.php
│   │   ├── item_management.php
│   │   ├── borrow.php
│   │   ├── history.php
│   │   ├── logs.php
│   │   ├── about.php            # Team information & contact
│   │   ├── navbar.php           # Sidebar navigation
│   │   └── ...
│   ├── CMS/                     # Storage Management System pages
│   │   ├── dashboard.php
│   │   └── pages/
│   │       └── papers.php
│   ├── images/                  # Images and assets
│   └── css/                     # Stylesheets
├── backend/
│   ├── js/                      # JavaScript entry points
│   │   └── main.js
│   ├── CMS/
│   │   └── api/                 # CMS API endpoints
│   ├── notifications/           # Notification system
│   └── profile/                 # Profile management
├── config/
│   └── db.php                   # Database connection
├── notification/                # Notification pages
├── vite.config.js               # Vite configuration
├── tailwind.config.js           # TailwindCSS configuration
└── package.json                 # Node.js dependencies
```

---

## Requirements

- **Windows** + **XAMPP** (Apache + MySQL)
- **Node.js** (v14+ for Vite/Tailwind)
- **Git**
- **Modern Browser** (Chrome/Edge/Firefox)
- **PHP** 7.4 or higher (included in XAMPP)

---

## Setup & Installation (Step-by-step)

### 1) Clone the repository

Place this inside your XAMPP `htdocs` folder:

```bash
cd C:\xampp\htdocs
git clone https://github.com/markjordanugtongspc/OSAS-SIS.git
cd OSAS-SIS
```

### 2) Install Node dependencies

```bash
npm install
```

This will install all required dependencies including Vite, TailwindCSS, and other packages.

### 3) Start XAMPP

- Open **XAMPP Control Panel**
- Start:
  - **Apache**
  - **MySQL**

### 4) Create the database

By default, the system connects to:

- Host: `localhost`
- User: `root`
- Password: *(empty)*
- Database: `osas_db`

You can see it in:

- `config/db.php`

Create it in phpMyAdmin:

1. Go to: `http://localhost/phpmyadmin`
2. Create a database named:
   - `osas_db`
3. Import your SQL schema/data (if you have an SQL file)

> **Note:** The main `database/` folder may be empty.  
> Check for SQL schema files in: `TRANSFER/dsa-project/database/schema.sql`  
> Import that if it matches your current system tables.

### 5) Run the Vite dev server (for live styling + auto reload)

Open a terminal in the project root:

```bash
npm run dev
```

Vite dev server runs on:

- `http://localhost:5173`

**Important:** Keep this terminal running while developing. The dev server provides:
- Hot module replacement (HMR)
- Live CSS updates
- Auto-reload on file changes

### 6) Open the system in your browser

Main login page:

- `http://localhost/OSAS-SIS/`

Default credentials (if applicable):
- Check with your system administrator for login credentials

---

## Build for Production (optional)

If you want to build static assets to `/dist`:

```bash
npm run build
```

When built, PHP will load assets using `dist/.vite/manifest.json`.

**Note:** In production, you don't need to run `npm run dev`. The built assets will be served directly.

---

## Common Troubleshooting

### Database error: "database does not exist"

- Create `osas_db` in phpMyAdmin
- Or update DB name in `config/db.php`
- Check database connection credentials in `config/db.php`

### Styles not updating / Tailwind not applying

- Make sure `npm run dev` is running
- Refresh the page (Ctrl+F5 for hard refresh)
- Confirm Vite is reachable at `http://localhost:5173`
- Check browser console for errors
- Verify Vite helper is working: `backend/vite_helper.php`

### Apache URL path issues

- Ensure your folder is exactly: `C:\xampp\htdocs\OSAS-SIS`
- Access it via: `http://localhost/OSAS-SIS/`
- Check Apache `httpd.conf` if using custom ports

### Session/login issues

- Clear browser cookies and cache
- Check PHP session configuration
- Verify `session_start()` is called in PHP files

### FormSubmit contact form not working

- Check internet connection (FormSubmit requires external API)
- Verify email addresses in `about.php` JavaScript
- Check browser console for JavaScript errors

---

## Features

### User Interface
- Modern, responsive design with TailwindCSS
- Smooth page transitions (SPA-like navigation)
- Collapsible sidebar navigation
- Real-time notifications
- Interactive charts and dashboards

### Security
- Session-based authentication
- Password hashing
- SQL injection protection (prepared statements)
- XSS protection (input sanitization)

### Performance
- Vite for fast development and optimized builds
- Lazy loading for better performance
- Optimized database queries

---

## Screenshots / Feature Previews

### Login Page
![Login Page](docs/images/login.png)

### Sport Inventory System Dashboard
![SIS Dashboard](docs/images/sis-dashboard.png)

### Item Inventory
![Item Inventory](docs/images/item-inventory.png)

### Borrowing Module
![Borrow Module](docs/images/borrow.png)

### History / Transactions
![History](docs/images/history.png)

### Storage Management Dashboard
![SMS Dashboard](docs/images/cms-dashboard.png)

### Cabinet View / Document Management
![Cabinet View](docs/images/cabinet-view.png)

> Add your screenshots inside `docs/images/` and update filenames as needed.

---

## Development Team

### Lead Developer
- **Jesper Ian Barilla** - Lead Full-Stack Developer
  - GitHub: [@yansanity1998](https://github.com/yansanity1998)
  - Built the entire Sports Equipment CRUD system, enhanced login frontend design, improved Storage Management with cabinet UI/UX enhancements, advanced search functionality, export features, and overall system improvements

### Team Members
- **Mark Jordan Ugtong** - Storage Management Foundation Developer
  - GitHub: [@markjordanugtongspc](https://github.com/markjordanugtongspc)
  - Created the login form backend, folder structure, database configuration, and Storage Management foundation

- **Casan Macaan** - Database Architect
  - GitHub: [@Macaan2024](https://github.com/Macaan2024)
  - Designed the entire database structure, ERD, table schemas, and system flow architecture

- **Stefen Harvey Alonzo** - QA Tester & System Analyst
  - Tested the entire system, reported bugs, added items to the system, and contributed to CRUD operations

---

## Documentation

For detailed user documentation and wiki, visit:

- **GitHub Wiki:** [https://github.com/markjordanugtongspc/OSAS-SIS/wiki](https://github.com/markjordanugtongspc/OSAS-SIS/wiki)

---

## License

This project is developed for St. Peter's College (DSA-OSAS) as a school project.

---

## Contributing

This is a school project. For contributions or questions, please contact the development team through the About page in the system.

---

## Support

For issues, questions, or support:
- Check the [GitHub Wiki](https://github.com/markjordanugtongspc/OSAS-SIS/wiki)
- Contact the development team via the About page in the system
- Open an issue on GitHub (if repository is public)

---

**Last Updated:** 2024
