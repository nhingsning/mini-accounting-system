# mini-accounting-system
Simple Laravel & Blade project for invoices and accounting.

## Quickstart
1. **Install dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```
2. **Copy env + key + DB**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   ```
3. **Run the app**
   ```bash
   php artisan serve
   ```

Open `http://127.0.0.1:8000/auth` to log in or create your first account. The default seeder creates a `test@example.com` user without a password, so register a new user or update that record with a password before signing in.

## Main pages
- **Dashboard** – `/dashboard` for KPIs, aging, heatmaps, and forecasts.
- **Invoices** – `/invoices` with create/edit, approvals, payments, receipt/credit-note conversion, and PDF export.
- **Quotations / POs / Credit & Debit Notes / Receipts** – respective resource routes (`/quotations`, `/po`, `/credit-notes`, `/receipts`).
- **Payments** – `/payments` to record methods (transfer/cash/card/e-wallet) plus attachments; `/bank-statements` to import CSV and reconcile to invoices.
- **Settings** – `/settings` to upload a logo, choose the primary color (`#31689E` default), set header/footer text for PDFs, and pick the default language (TH/EN).

## Language toggle
The floating language button (bottom-right) lets you switch between Thai and English. Your choice is stored in the session; when none is set the app falls back to the default language from **Settings**.

## PDF templates
Printable templates live in `resources/views/*/pdf.blade.php` for quotations, invoices, and receipts. They pull branding (logo, primary color), header/footer text, and bilingual labels automatically from **Settings**.
