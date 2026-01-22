# OSAS-SIS  
 **OSAS - Student Affairs System**  
 A web-based system that supports **Sport Inventory System** and **Cabinet Management System** for St. Peter’s College (DSA-OSAS).
 
 ---
 
 ## Overview
 
 **OSAS-SIS** is designed to help the Office of Student Affairs manage two major operational areas:
 
 - **Sport Inventory System (SIS)**  
   Tracks items, borrowing transactions, approvals, returns, and history.
 
 - **Cabinet Management System (CMS)**  
   Manages cabinets, stored documents/files, and dashboard reporting for document management.
 
 This project runs on **PHP + MySQL (XAMPP)** and uses **Vite + TailwindCSS** for frontend assets and styling.
 
 ---
 
 ## Key Modules
<<<<<<< HEAD
=======
 
>>>>>>> e38245eebded5a5ef35275c2dda3cab30131b073
 ### Sport Inventory System (SIS)
 - Dashboard overview (charts, statistics)
 - Item Inventory listing
 - Item Management (add/edit/delete items)
 - Borrowing workflow
 - Transaction history
 
 ### Cabinet Management System (CMS)
 - CMS Dashboard (stats + charts)
 - Cabinet pages (view/manage cabinets)
 - Papers / Documents pages
 - API endpoints under `backend/CMS/api/`
 
 ---
 
 ## Tech Stack
 
 - **Backend:** PHP
 - **Database:** MySQL (XAMPP / phpMyAdmin)
 - **Frontend Styles:** TailwindCSS
 - **Asset Bundling / Dev Server:** Vite
 - **Charts:** Chart.js (and dashboard charts depending on page)
 
 ---
 
 ## Project Structure (high level)
 
 - `index.php`  
   Login page / entry point.
 
 - `frontend/`  
   UI pages, templates, styles, images.
 
 - `frontend/pages/`  
   Sport Inventory System pages (example: `dashboard.php`, `borrow.php`, `history.php`).
 
 - `frontend/CMS/`  
   Cabinet Management System pages (example: `dashboard.php`, `pages/...`).
 
 - `backend/`  
   JavaScript entry points and server-side logic.
 
 - `backend/CMS/api/`  
   CMS API endpoints.
 
 - `config/db.php`  
   Database connection configuration.
 
 - `vite.config.js`, `package.json`  
   Vite/Tailwind build setup.
 
 ---
 
 ## Requirements
 
 - **Windows** + **XAMPP** (Apache + MySQL)
 - **Node.js** (for Vite/Tailwind)
 - **Git**
 - Browser (Chrome/Edge/Firefox)
 
 ---
 
 ## Setup & Installation (Step-by-step)
 
 ### 1) Clone the repository
 
 Place this inside your XAMPP `htdocs` folder:
 
 ```bash
 cd C:\\xampp\\htdocs
 git clone https://github.com/markjordanugtongspc/OSAS-SIS.git
 cd OSAS-SIS
 ```
 
 ### 2) Install Node dependencies
 
 ```bash
 npm install
 ```
 
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
 
 > Note: In this repo snapshot, the main `database/` folder is empty.  
 > There is an SQL schema in: `TRANSFER/dsa-project/database/schema.sql`  
 > You can import that if it matches your current system tables.
 
 ### 5) Run the Vite dev server (for live styling + auto reload)
 
 Open a terminal in the project root:
 
 ```bash
 npm run dev
 ```
 
 Vite dev server runs on:
 
 - `http://localhost:5173`
 
 ### 6) Open the system in your browser
 
 Main login page:
 
 - `http://localhost/OSAS-SIS/`
 
 ---
 
 ## Build for Production (optional)
 
 If you want to build static assets to `/dist`:
 
 ```bash
 npm run build
 ```
 
 When built, PHP will load assets using `dist/.vite/manifest.json`.
 
 ---
 
 ## Common Troubleshooting
 
 ### Database error: “database does not exist”
 
 - Create `osas_db` in phpMyAdmin
 - Or update DB name in `config/db.php`
 
 ### Styles not updating / Tailwind not applying
 
 - Make sure `npm run dev` is running
 - Refresh the page
 - Confirm Vite is reachable at `http://localhost:5173`
 
 ### Apache URL path issues
 
 - Ensure your folder is exactly: `C:\\xampp\\htdocs\\OSAS-SIS`
 - Access it via: `http://localhost/OSAS-SIS/`
 
 ---
 
 ## Screenshots / Feature Previews (placeholders)
 
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
 
 ### Cabinet Management Dashboard
 
 ![CMS Dashboard](docs/images/cms-dashboard.png)
 
 ### Cabinet View / Document Management
 
 ![Cabinet View](docs/images/cabinet-view.png)
 
 > Add your screenshots inside `docs/images/` and update filenames as needed.
 
 ---
 
 ## Authors / Contributors
 
 - Add your team members here:
<<<<<<< HEAD
   - Name 1
   - Name 2
   - Name 3
=======
   - @markjordanugtongspc
   - @yansanity1998
   - @casanmacaan
>>>>>>> e38245eebded5a5ef35275c2dda3cab30131b073
 
 ---
 
 ## License
 
<<<<<<< HEAD
 Specify your license here (MIT / Proprietary / School Project).
=======
 Specify your license here (MIT / Proprietary / School Project).
>>>>>>> e38245eebded5a5ef35275c2dda3cab30131b073
