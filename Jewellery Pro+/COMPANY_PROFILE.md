# MOTI JEWELLERS — COMPANY PROFILE & COMPREHENSIVE PROJECT ANALYSIS
**Software Suite: Jewellery Pro+ (Enterprise Edition)**  
**Version:** 2026 Stable Release  
**Last Updated:** October 2026  

---

## 1. COMPANY INFORMATION & MASTER DETAILS

<p align="center">
  <img src="logo.png" alt="Moti Jewellers Logo" width="220" style="max-height: 120px; object-fit: contain;"/>
  <br>
  <strong>MOTI JEWELLERS</strong><br>
  <em>"Trusted Tradition • Timeless Elegance"</em><br>
  <small>Precious Jewellery, Precious Relationships</small>
</p>

### 1.1 Legal & Statutory Identifiers

| Parameter | Official Details |
| :--- | :--- |
| **Legal Trade Name** | **MOTI JEWELLERS** |
| **Brand / Display Name** | **Moti Jewellers** |
| **Proprietor Constitution** | Prop: Moti Jewellers |
| **GSTIN (GST Number)** | **`19AEPPM9851A1Z4`** |
| **State Code (GST)** | **`19`** (West Bengal) |
| **Derived PAN (Chars 3–10)**| **`AEPPM9851A`** (Type: Individual / Proprietorship) |
| **Place of Supply Code** | `19` — West Bengal |
| **Legal Jurisdiction** | **Subject to Paschim Medinipur Jurisdiction** |
| **Hallmark Compliance** | **BIS Hallmark 100% Certified** with 6-digit alphanumeric HUID tracking |

---

### 1.2 Official Contact & Communication

| Channel | Contact Details |
| :--- | :--- |
| **Primary Business Mobile** | **+91 9635985848** |
| **Secondary Support Mobile** | **+91 9876543210** |
| **Billing Counter / Store Contact** | **+91 8927582290** |
| **Official Business Email** | **`motijewellers9635985848@gmail.com`** |
| **Registered Admin Email** | `supriyodas@gmail.com` |
| **Physical Address Line 1** | Hamirpur, Balichak Station Road |
| **Physical Address Line 2** | Paschim Medinipur |
| **State & Pincode** | West Bengal — **721148** |

---

### 1.3 Master HSN / SAC Code Directory

Under Indian GST regulations for jewellery and precious metals, the following HSN/SAC codes are integrated into the application's billing, inventory, and purchase modules:

| HSN / SAC Code | Description / Commodity | Applicable GST Rate | SGST | CGST | IGST |
| :--- | :--- | :---: | :---: | :---: | :---: |
| **`7113`** | **Articles of Jewellery & parts thereof (Gold, Silver, Platinum)** | **3.0%** | 1.5% | 1.5% | 3.0% |
| **`7108` / `71081200`** | **Gold Bullion / Raw Gold / Unwrought Gold bars & coins** | **3.0%** | 1.5% | 1.5% | 3.0% |
| **`7106` / `71069100`** | **Silver Bullion / Raw Silver / Unwrought & semi-manufactured** | **3.0%** | 1.5% | 1.5% | 3.0% |
| **`7102` / `71023100`** | **Cut & Polished Natural Diamonds & Precious Gemstones** | **1.5% / 3.0%** | 0.75% / 1.5% | 0.75% / 1.5% | 1.5% / 3.0% |
| **`7110` / `71101100`** | **Platinum unwrought, bullion or semi-manufactured** | **3.0%** | 1.5% | 1.5% | 3.0% |
| **`9988` / `998892`** | **Job Work / Making Charges / Manufacturing Craftsmanship** | **18.0%** *(or 3% composite)* | 9.0% | 9.0% | 18.0% |

---

### 1.4 Standard Terms, Conditions & Declarations (Invoice & Memo)

1. **Exchange & Returns:** Goods once sold will not be taken back or exchanged without the original cash memo or tax invoice.
2. **Hallmark & Purity:** Gold and silver jewellery items comply strictly with Bureau of Indian Standards (BIS) Hallmarking specifications (916 / 22K, 750 / 18K) and carry valid HUID tags.
3. **Legal Jurisdiction:** Any and all disputes arising from transactions are strictly subject to **Paschim Medinipur Jurisdiction**.
4. **Statutory Declaration:** *"We declare that this invoice/cash memo shows the actual price of the goods described and all particulars are true and correct."*

---

### 1.5 Branding Assets & Typography

- **Primary Logo:** `logo.png` (High-resolution full color shop emblem)
- **Transparent Logo:** `assets/images/moti-removebg-preview.png`
- **Invoice Background Watermark:** `assets/images/bill_bg_1.jpg` & Full Page Logo Watermark (16% opacity print-safe)
- **Visual Typography:** `Playfair Display` (Luxury serif for headings/jewelry accents) & `Poppins` (Modern geometric sans-serif for UI, numbers, and data tables)
- **Color Palette:**
  - Gold Primary: `#d68b16` / `#b3740e`
  - Deep Bronze/Accent: `#7a4e0a`
  - Emerald Success: `#059669` / `#15803d`
  - Royal Dark Slate: `#011921` to `#03373b`
  - Background Neutral: `#fffdf7` / `#fef8eb`

---

## 2. ARCHITECTURAL & TECHNICAL ANALYSIS

```
+---------------------------------------------------------------------------------------+
|                                    JEWELLERY PRO+                                     |
|                      Custom Retail & Wholesale Jewelry ERP Suite                       |
+---------------------------------------------------------------------------------------+
|  Frontend UI / Client Layer:                                                          |
|  - Vanilla HTML5 + CSS3 + TailwindCSS CDN + FontAwesome 6 + Google Fonts               |
|  - Responsive Print Stylesheet (A4 Landscape & Portrait print-color-adjust: exact)    |
|  - Client-Side Real-time Calculation Engines (Making Charges, Hallmarks, GST, Returns)|
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
|  Application Routing & Controller Layer (PHP 7.4 - 8.3):                              |
|  - Single Entry / Serverless Router (api/index.php & router.php)                      |
|  - Dedicated Feature Controllers (billing.php, stock.php, purchase.php, etc.)         |
|  - Mail Delivery via PHPMailer (Gmail SMTP over TLS Port 587)                         |
|  - Automation & Reminder Service (WhatsApp GreenAPI / Meta Cloud API)                 |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
|  Resilient Multi-Environment Database Engine (MySQL / MariaDB):                       |
|  - Database Name: `moti`                                                              |
|  - Self-Healing Auto-Migration on Connection (SHOW COLUMNS / ALTER TABLE auto-sync)   |
|  - Auto-Fallback Connection Strategy:                                                 |
|    * Priority 1: 127.0.0.1:3306 (aaPanel VPS / Hostinger Linux root)                  |
|    * Priority 2: localhost:3306 (aaPanel VPS / Hostinger Linux root)                  |
|    * Priority 3: 127.0.0.1:3306 (Local XAMPP / Windows development root/empty pass)  |
|    * Priority 4: localhost:3306 (Local XAMPP / Windows development root/empty pass)  |
+---------------------------------------------------------------------------------------+
```

---

## 3. CORE FUNCTIONAL MODULES BREAKDOWN

### 3.1 Point of Sale (POS) & Smart Invoicing (`billing.php` & `view_pdf.php`)
- **Dual Invoice Modes:** 
  - **GST Tax Invoice** (Generates formal Tax Invoice with CGST/SGST breakdown, Customer GSTIN, Place of Supply, HSN tags, and legal disclaimers).
  - **Non-GST Cash Memo** (Streamlined cash bill for local end-consumers).
- **Flexible Item Addition:**
  - Direct selection from live stock inventory (auto-fetches weight, rate, purity, HSN, and HUID).
  - Manual item entry with custom weight, piece count, custom unit (`g`, `pcs`, `pair`).
- **Real-time Price & Weight Calculators:**
  - Pure metal weight vs. Gross weight calculation.
  - Making charge calculation via flat amount (₹) or percentage (%).
  - BIS Hallmark fee per item.
  - Item-level and invoice-level discounts.
- **Old Gold Exchange / Return Handling:**
  - Dedicated deduction for old gold scrap/exchange (`old_gold_amount`, `old_gold_type`).
  - Automatic subtraction from grand total with credit refund option.
- **Split Payment Allocation:**
  - Supports combined payments in single invoice: **Cash**, **UPI / QR**, **Bank Transfer / NEFT / Cheque**, and **Balance Due**.
- **A4 Print Engine (`view_pdf.php`):**
  - High-definition print template with subtle brand watermark.
  - Converts numbers into Indian currency words format (*"Rupees Thirty-Two Thousand Four Hundred Only"*).
  - Generates customer copy and store copy with dual signature stamps.

---

### 3.2 Inventory & Stock Metal Tracking (`stock.php`, `database.php`)
- **Category Support:** Gold, Silver, Diamond, Platinum, Gemstones.
- **Item Identification:** Auto-generated unique serial numbers (`SN0001`), item barcodes, and mandatory **HUID** (Hallmark Unique ID) tracking.
- **Raw Metal Bullion Vault (`stock_metal`):**
  - Tracks raw gold, silver, diamond, and platinum reserves by weight in grams/carats.
  - Real-time deduction when finished jewellery is fabricated or sold.
- **Stock Audit & Low Stock Notifications:** Instant filtering for out-of-stock and low-quantity designs.

---

### 3.3 B2B Purchase Entry & Vendor Management (`purchase.php`, `purchase_history.php`, `purchase_view.php`)
- **Full Inward B2B Invoicing:**
  - Captures Supplier Name, Address, Supplier GSTIN, Supplier PAN, State & State Code.
  - Records buyer details (Moti Jewellers: `19AEPPM9851A1Z4`).
- **HSN / SAC Code Mapping:**
  - Auto-fills HSN `71081200` for Gold, `71069100` for Silver, `71023100` for Diamond, and `71101100` for Platinum.
- **GST Tax Types:**
  - Intrastate: CGST 1.5% + SGST 1.5% (Total 3.0%).
  - Interstate: IGST 3.0%.
- **Voucher Generation:** Printable B2B purchase receipts with tax compliance breakdown.

---

### 3.4 Sanchari Gold Savings Scheme / Monthly Swarna Nidhi (`sanchari_*.php`, `sbook.php`)
- **Scheme Registration:** Unique Customer ID and Passbook ID generation (`sanchari_customers`).
- **Monthly Installments:** Fixed monthly contributions tracked with payment receipt numbers, dates, and payment modes.
- **Gold Accumulation:** Converts installment payments into equivalent gold grams based on the day's spot rate.
- **Maturity & Redemption:** Redemptions against jewellery purchases with making charge discounts and scheme bonus adjustments (`sanchari_redemptions`).

---

### 3.5 Live Bullion Metal Rates Service (`metal_rates.php`)
- **Multi-Source Fetcher:**
  1. FawazAhmed Open Currency / Metals API (CDN-cached fast response).
  2. Spot Gold/Silver USD APIs (`api.gold-api.com`).
  3. Live USD/INR Exchange Rate Feed (`exchangerate-api.com`).
- **Purity Breakdown (Calculated per 10 grams & per gram):**
  - 24 Karat (99.9% Pure Gold)
  - 22 Karat (91.6% BIS Hallmarked Gold)
  - 18 Karat (75.0% Diamond Jewellery Gold)
  - 12 Karat & 9 Karat
  - Fine Silver (999/925)
  - Platinum (950)
- **Local Caching:** 30-minute transient disk cache to protect against third-party rate limits and ensure offline speed.

---

### 3.6 Customer Due List & Payment Follow-Up (`due_list.php`, `customers.php`)
- **Overdue Ledger:** Tracks balance amounts, initial due dates, next expected due dates, and days overdue.
- **Partial Payment Collector:** Allows recording part payments towards existing bills and automatically generates partial payment receipts.
- **Customer Lifetime History:** Instant summary of total purchases, pending dues, and past invoices.

---

### 3.7 WhatsApp & Automation Engine (`whatsapp_automation.php`)
- **API Integration:** Ready connectors for GreenAPI and WhatsApp Cloud API.
- **Automated Templates:**
  - Immediate digital invoice link sent upon bill generation.
  - T-7, T-3, and Due Date payment reminders.
  - Overdue payment alerts with outstanding balance notice.
  - Festive greetings (Diwali, Dhanteras, Akshay Tritiya, New Year).

---

### 3.8 Financial Ledger, Expenses & Reports (`income_expenses.php`, `reports.php`)
- **Income & Expense Categorization:**
  - Income: Sales, interest, commission, exchange premiums.
  - Expenses: Inventory purchase, shop rent, electricity, staff salary, marketing, transport, GST tax payments.
- **Audit & Analytics:**
  - Date-range filtering (Today, This Week, This Month, Custom FY).
  - Excel / CSV export functionality.
  - Invoice cancellation with safe stock replenishment back to inventory.

---

## 4. DATABASE SCHEMA REFERENCE

The MySQL database `moti` consists of the following primary relational structures:

```
+---------------------------------------------------------------------------------------------+
| Table                       | Primary Key        | Key Business Columns                     |
+---------------------------------------------------------------------------------------------+
| users                       | id (INT)           | name, mobile, email, password            |
| products                    | id (INT)           | serial_no, name, category, price, weight |
|                             |                    | quantity, huid_code, hsn_code, unit      |
| customers                   | id (INT)           | name, mobile, email, address, gst_number |
| invoices                    | id (INT)           | invoice_no, customer_name, customer_gstin|
|                             |                    | subtotal, gst_amount, total_amount       |
|                             |                    | paid_amount, balance_amount, cash_paid   |
|                             |                    | upi_paid, account_paid, due_date         |
| invoice_items               | id (INT)           | invoice_id, product_id, product_name     |
|                             |                    | hsn_code, huid_code, gross_wt, net_wt    |
|                             |                    | making_charge, hallmark, discount, total |
| purchase_entries            | id (INT)           | purchase_no, supplier_name, supplier_gstin|
|                             |                    | buyer_gstin, material_type, hsn_sac      |
|                             |                    | qty, rate_per_unit, cgst_amt, sgst_amt   |
| stock_metal                 | id (INT)           | material_type (Gold/Silver), qty_available|
| sanchari_customers          | id (INT)           | customer_id, book_id, monthly_amount     |
| sanchari_payments           | id (INT)           | payment_id, installment_no, amount, weight|
| sanchari_redemptions        | id (INT)           | redemption_id, purchase_date, net_weight |
| income                      | id (INT)           | income_date, source, category, amount    |
| expenses                    | id (INT)           | expense_date, category, amount, bill_no  |
| whatsapp_templates          | id (INT)           | template_name, template_type, message    |
| whatsapp_logs               | id (INT)           | recipient_number, status, sent_at        |
+---------------------------------------------------------------------------------------------+
```

---

## 5. SERVER CONFIGURATION & CREDENTIALS

### 5.1 Database Connection Hierarchy (`config/database.php`)
- **Database Name:** `moti`
- **Supported Hosts & Credentials:**
  1. Host: `127.0.0.1` | User: `root` | Password: `RootPass123` *(VPS / aaPanel / Production)*
  2. Host: `localhost` | User: `root` | Password: `RootPass123`
  3. Host: `127.0.0.1` | User: `root` | Password: `""` *(Local XAMPP / WAMP development)*
  4. Host: `localhost` | User: `root` | Password: `""`
- **Auto Self-Repair:** Executes `CREATE TABLE IF NOT EXISTS` and inspects `SHOW COLUMNS` to add missing columns at runtime without requiring manual SQL migrations.

### 5.2 Email SMTP Service (`config/mail_config.php`)
- **Service:** Gmail SMTP
- **Host:** `smtp.gmail.com`
- **Port:** `587` (STARTTLS)
- **Sender Address:** `motijewellers9635985848@gmail.com`
- **Sender Name:** `MOTI JEWELLERS`
- **Security:** TLS encrypted with PHPMailer engine

---

## 6. MAINTENANCE & EXTENSION GUIDELINES

1. **Updating Company Information:**
   To update shop phone numbers, bank account numbers, or address, edit `config/company_config.php`. Changes automatically reflect on all invoices, receipts, headers, and reports.
2. **Backing Up Database:**
   Export the `moti` database via phpMyAdmin or via terminal:
   ```bash
   mysqldump -u root -p moti > moti_backup_$(date +%F).sql
   ```
3. **Adding New HSN / Metal Categories:**
   Update the HSN mapping arrays in `billing.php` and `purchase.php`.
4. **Print Formatting Adjustments:**
   Print settings, CSS margins, and watermark opacity are styled in `view_pdf.php` under the `@media print` rules.

---
*Document prepared for Moti Jewellers — Jewellery Pro+ Enterprise Solution.*
