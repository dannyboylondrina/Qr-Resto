# Salo — Modern Filipino Kitchen · DineFlow

QR-code table ordering, reservations, payments (Cash / GCash / Online Banking), kitchen display and sales reports.
Built with **CodeIgniter 4 + MySQL/MariaDB** for XAMPP.

## Setup (XAMPP on Windows)

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Install the PHP packages: `composer install --no-dev`
3. Create the database: `CREATE DATABASE dineflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
   (Database settings are in `.env`.)
4. Create tables and starter data:
   ```
   php spark migrate
   php spark db:seed DatabaseSeeder
   php spark db:seed DemoSalesSeeder   # optional: 5 weeks of fake sales for the dashboard
   ```
5. Open **http://localhost/QRCodeResto/public/**

### Staff logins (change these passwords right away in *My account*)

| Role    | Email                  | Password       |
|---------|------------------------|----------------|
| Admin   | admin@dineflow.test    | Admin@12345    |
| Cashier | cashier@dineflow.test  | Cashier@12345  |
| Kitchen | kitchen@dineflow.test  | Kitchen@12345  |

## Ordering from phones

Table QR codes link to `<site>/menu?table=T03`. Phones can't open `localhost`, so:

- Find the PC's Wi-Fi IP (`ipconfig`, e.g. `192.168.1.10`). Allow Apache through Windows Firewall.
- Open **http://192.168.1.10/QRCodeResto/public/staff/qr-codes** and print the cards from there (the site uses whatever address you opened it with),
  **or** set `app.baseURL` in `.env` to the final address before printing.

## Optional services (`.env`)

| Feature | Settings |
|---|---|
| Google sign-in | `google.clientId`, `google.clientSecret` — redirect URI `<baseURL>auth/google/callback` |
| Email to guests / password reset | `email.SMTPHost`, `email.SMTPUser`, `email.SMTPPass` … |
| SMS to guests | `sms.semaphoreKey` (semaphore.co) |

Without them, messages are recorded under **Staff → Activity log → Guest notifications** and nothing is sent.
In development mode the password-reset link is shown on screen when SMTP isn't configured.

## How payments flow

- **Cash** → order is *Awaiting payment*; the kitchen only sees it after the cashier taps **Mark as paid**.
- **GCash / Online Banking** → guest enters the reference number + uploads a screenshot → *For verification* →
  cashier **Approves** (goes to the kitchen) or **Rejects** with a reason (guest re-submits on the tracking page).
- **Pay at the end** (running tab) → offered for extra dine-in orders once the table has a paid order; settled on **Staff → Floor**.
- Reservations with pre-orders, or parties of 8+ (deposit set in *Payment settings*), pay ahead via GCash/bank; approving the payment confirms the booking.
  When the party is seated, the pre-order fires to the kitchen and the deposit is credited against the bill.

## Going live checklist

- `CI_ENVIRONMENT = production` in `.env`
- Point the web server's document root at `public/` (or keep the included root `.htaccess`, which blocks `app/`, `vendor/`, `writable/` and `.env`)
- Use HTTPS, change all seeded passwords, enable two-step verification for admins
- Upload the real GCash QR and bank accounts in **Payment settings**

Photo credits: `public/uploads/menu/CREDITS.md` (Wikimedia Commons, CC BY-SA).
