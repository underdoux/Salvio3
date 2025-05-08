# Pharmacy POS System Development Log

## Comprehensive Phase Development Plan Review and Execution Strategy

🕒 Timestamp: 2025-05-04 10:00 WIB

### Overview of Phase Development Plan

#### PHASE 1: SYSTEM FOUNDATION & CORE ARCHITECTURE
- Goals:
    * Base project structure without /public folder.
    * MVC-style architecture with clean URL routing.
    * Secure login system with role-based redirection.
- Status: Completed successfully with base app accessible via root domain, role-based login, and dashboard.

#### PHASE 2: MASTER DATA MANAGEMENT
- Goals:
    * Manage users, customers, categories, and medicines.
- Key Tasks:
    * CRUD operations for Users (Admin), Customers (Sales/Admin).
    * Category & Product Type management.
    * Product master with stock tracking and BPOM category assignment.
    * Input validation, soft-delete, search/filter support.
- Deliverable: Full master data module.

#### PHASE 2B: BPOM Data Scraping Integration
- Goals:
    * Admins/Sales can retrieve and auto-fill product info from BPOM Indonesia site.
- Key Tasks:
    * Develop PHP scraper using cURL and DOMDocument.
    * Input handling for product name or registration number.
    * Parse and store BPOM data in `bpom_reference_data` table.
    * Provide JSON API and HTML preview.
    * Integrate auto-fill in product creation form.
- Deliverable: Working search & auto-fill tool.

#### PHASE 3: SALES & TRANSACTIONS
- Goals:
    * Track sales transactions linked to customers and products.
- Key Tasks:
    * Sales form with product selection, quantity, discounts.
    * Customer selection or creation.
    * Multiple payment types.
    * Save sales, discounts, payments.
    * Printable invoices.
- Deliverable: Sales module.

#### PHASE 4: PRICE ADJUSTMENT & COMMISSIONS
- Goals:
    * Manage pricing and commission logic.
- Key Tasks:
    * Log price changes.
    * Validate discounts per role.
    * Commission setup and calculation.
    * Sales reports.
- Deliverable: Pricing and commission module.

#### PHASE 5: PROFIT SHARING & INVESTOR MANAGEMENT
- Goals:
    * Automate profit distribution.
- Key Tasks:
    * CRUD investors.
    * Track net profit.
    * Distribute profit by capital share.
    * Profit logs.
- Deliverable: Investor management module.

#### PHASE 6: SUPPLIER & INVENTORY PURCHASING
- Goals:
    * Manage purchases and supplier payments.
- Key Tasks:
    * CRUD suppliers.
    * Purchase orders and payments.
    * Stock auto-adjustment.
    * Monitor dues.
- Deliverable: Supplier and purchasing module.

#### PHASE 7: REPORTING & ANALYTICS
- Goals:
    * Provide detailed reports.
- Key Tasks:
    * Sales, commission, investor, product trend reports.
    * Export to PDF/Excel.
- Deliverable: Reporting module.

#### PHASE 8: EMAIL & WHATSAPP NOTIFICATIONS
- Goals:
    * Alert stakeholders on events.
- Key Tasks:
    * Setup PHPMailer and WhatsApp API.
    * Notifications for low stock, dues, profit updates.
- Deliverable: Notification module.

---

### Execution Strategy to Continue Development

1. **Prioritize Phase 2 Development:**
   - Begin with master data management modules (Users, Customers, Categories, Products).
   - Implement CRUD with validation, soft-delete, and search features.
   - Ensure UI consistency by reusing existing layout and components.

2. **Integrate BPOM Data Scraping (Phase 2B):**
   - Develop scraper as a helper module.
   - Create controller and views for search and results.
   - Store scraped data in dedicated table.
   - Integrate auto-fill in product forms with fallback to manual entry.

3. **Plan for Phases 3 to 8:**
   - Design database schema and UI wireframes for sales, pricing, profit sharing, suppliers, reporting, and notifications.
   - Implement modules incrementally, ensuring testing and security at each step.
   - Use modular coding practices to maintain scalability.

4. **Development Workflow:**
   - Use version control with feature branches.
   - Write unit and integration tests.
   - Set up CI/CD pipelines for automated testing and deployment.
   - Maintain detailed documentation and update logs regularly.

5. **Security and Performance:**
   - Enforce input validation and sanitization.
   - Implement CSRF and XSS protections.
   - Optimize database queries and caching.
   - Monitor application logs and performance metrics.

---

### Updated Folder Structure (Planned)

/your-project-root
│
├── index.php                      <-- Front controller
├── .htaccess                      <-- URL rewriting
├── config/
│   ├── config.php                 <-- Global config
│   ├── routes.php                 <-- Route definitions
│   └── bpom_config.php            <-- BPOM scraper config
│
├── core/
│   ├── Controller.php
│   ├── Model.php
│   ├── View.php
│   ├── Database.php
│   ├── Session.php
│   └── Auth.php
│
├── controllers/
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── ProductController.php
│   ├── CustomerController.php
│   ├── SalesController.php
│   ├── CommissionController.php
│   ├── InvestorController.php
│   ├── SupplierController.php
│   ├── ReportController.php
│   └── BpomController.php
│
├── models/
│   ├── User.php
│   ├── Product.php
│   ├── Customer.php
│   ├── Sale.php
│   ├── Category.php
│   ├── Commission.php
│   ├── Investor.php
│   ├── Supplier.php
│   ├── Payment.php
│   └── BpomReference.php
│
├── views/
│   ├── layout/
│   ├── auth/
│   ├── dashboard/
│   ├── products/
│   ├── customers/
│   ├── sales/
│   ├── commissions/
│   ├── investors/
│   ├── suppliers/
│   ├── reports/
│   └── bpom/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
│
├── helpers/
│   ├── utils.php
│   └── bpom_scraper.php
│
├── logs/
│   └── error.log
│
└── uploads/
    └── invoice_pdfs/

---

### Summary

The project has a solid foundation from Phase 1. The next focus is on Phase 2 master data management and BPOM integration, followed by sales, pricing, profit sharing, suppliers, reporting, and notifications. A modular, test-driven, and secure development approach is recommended to ensure maintainability and scalability.

---

This detailed plan and execution strategy have been added to the development log for reference and tracking.
