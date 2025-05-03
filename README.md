
# 💊 Pharmaceutical POS & Inventory Management System

A fullstack modular POS (Point of Sale) and inventory system tailored for pharmaceutical distributors. Built using raw PHP and MySQL with a custom MVC-like structure. Fully supports product management, investor profit distribution, commission setup, multi-role access, and integration with BPOM (Indonesia’s National Agency of Drug and Food Control).

---

## 🚀 Features

- Multi-role Access: Admin & Sales
- Product Management (Stocked & By-Order)
- Categorization via BPOM Reference (Web Scraping)
- Customer & Transaction Management
- Payment Types: Cash, Bank Transfer, Installments
- Adjustable Pricing with Reason Logs
- Validated Discount System
- Commission Logic: Global, Category, Product
- Investor Profit Sharing (Based on Capital Percentage)
- Supplier Payment Management (Cash & Installment)
- Sales & Inventory Reporting
- Dashboard Monitoring
- WhatsApp & Email Notifications
- Fully Responsive Frontend (Desktop & Mobile)
- Bahasa Indonesia with Currency Format Rp (IDR)

---

## 📁 Folder Structure

```
/app
  /controllers
  /models
  /views
  /helpers
  /assets
/config
/database
/logs
/storage
/index.php        # Entry point
.htaccess         # Rewrite rule to support clean URLs
.env              # Optional config file for secrets
```

📝 **Note**: Website is served from base URL (not /public).

---

## 🔄 Development Plan (Phases)

**PHASE 1: Core System**
- Database schema
- User roles & authentication

**PHASE 2A: Inventory & Sales**
- Product, customer, transaction, commission logic

**PHASE 2B: BPOM Scraping Integration**
- Scrape data from https://cekbpom.pom.go.id/

**PHASE 3: Financial Module**
- Investor capital input and profit distribution

**PHASE 4: Dashboard & Reporting**
- Monitoring UI and exportable reports

**PHASE 5: Notifications**
- WhatsApp & email reminders, alerts

---

## ⚙️ Setup Instructions

1. Clone the project to your server
2. Set base directory as web root (index.php must be at `/`)
3. Import the SQL schema from `/database/`
4. Configure database credentials in `/config/config.php`
5. Enable mod_rewrite on Apache and use the provided `.htaccess`
6. Make sure PHP extensions for cURL and DOM are enabled (for scraping)

---

## 📌 Localization

- **Language:** Bahasa Indonesia  
- **Currency:** Rp (IDR)

---

## 📊 Sample Dashboard Widgets

- Total Penjualan Harian/Bulanan
- Jumlah Customer Aktif
- Produk Terlaris
- Status Cicilan Customer
- Laba Dibagikan ke Investor

---

## 📞 Notifications

- Reminder pembayaran via WhatsApp & Email
- Laporan laba otomatis dikirim ke investor
- Pemberitahuan stok rendah

---

## ✅ Status

Development in progress – see `log.md` for details.

---

## 👤 Developer

This system is designed with clarity and modularity in mind, enabling future extensions (mobile apps, auto-sync to BPOM API, AI analytics, etc).

For implementation, testing, or support: **contact developer or use this README as base doc for Blackbox.ai integration.**
