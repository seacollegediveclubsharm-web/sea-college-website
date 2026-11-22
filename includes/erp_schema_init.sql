-- ============================================================================
-- SEA COLLEGE DIVE CLUB — UNIFIED BIG-DATA SQL WAREHOUSE & RBAC MOBILE ERP
-- Compatible with SQLite 3 (Edge / Hostinger PDO) & PostgreSQL / MySQL 8
-- Designed jointly by Karim (Architect), Tarek (CFO), Nadia (Chief Accountant),
-- Ayman (Website Dev), Hossam (Marketing Director), & Lara (Customer Care)
-- ============================================================================

PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;

-- ----------------------------------------------------------------------------
-- 1. RBAC: ROLES, GRANULAR PERMISSIONS, EMPLOYEES & USER ACCOUNTS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    role_code TEXT PRIMARY KEY,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    department TEXT NOT NULL,
    hierarchy_level INTEGER NOT NULL DEFAULT 10, -- 100=Owner/CFO, 85=ChiefAcc, 75=Partner, 60=DeptHead, 40=Supervisor, 20=Staff
    default_dashboard TEXT NOT NULL,
    description_en TEXT NOT NULL,
    description_ar TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS permissions (
    perm_code TEXT PRIMARY KEY,
    module TEXT NOT NULL,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_code TEXT NOT NULL,
    perm_code TEXT NOT NULL,
    PRIMARY KEY (role_code, perm_code),
    FOREIGN KEY (role_code) REFERENCES roles(role_code) ON DELETE CASCADE,
    FOREIGN KEY (perm_code) REFERENCES permissions(perm_code) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employees_master (
    emp_code TEXT PRIMARY KEY,
    full_name_ar TEXT NOT NULL,
    full_name_en TEXT NOT NULL,
    job_title_ar TEXT NOT NULL,
    job_title_en TEXT NOT NULL,
    branch_code TEXT NOT NULL DEFAULT 'CYRENE',
    department TEXT NOT NULL,
    base_salary_egp REAL NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    latest_month TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    pin_hint TEXT NOT NULL DEFAULT '',
    emp_code TEXT DEFAULT NULL,
    display_name_en TEXT NOT NULL,
    display_name_ar TEXT NOT NULL,
    role_code TEXT NOT NULL,
    branch_scope TEXT NOT NULL DEFAULT 'ALL',   -- ALL, CYRENE, JETTY, JAZ, WEBSITE
    vessel_scope TEXT NOT NULL DEFAULT 'ALL',   -- ALL, or comma-separated e.g. 'OCEANO,OMAR_4' for Fleet Partners
    rep_scope TEXT NOT NULL DEFAULT '',         -- Sales rep filter e.g. 'FADY' so reps only see own vouchers/commissions
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    FOREIGN KEY (role_code) REFERENCES roles(role_code)
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    created_at TEXT NOT NULL,
    username TEXT NOT NULL,
    role_code TEXT NOT NULL,
    action_type TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id TEXT NOT NULL DEFAULT '',
    details_json TEXT NOT NULL DEFAULT '{}'
);

-- ----------------------------------------------------------------------------
-- 2. CORE MASTER DIMENSIONS (BRANCHES, FX, COA, HOTELS, CATALOG, FLEET, ASSETS)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS branches (
    code TEXT PRIMARY KEY,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    location TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS currencies_fx (
    currency TEXT PRIMARY KEY,
    symbol TEXT NOT NULL,
    rate_to_egp REAL NOT NULL,
    rate_to_eur REAL NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    code TEXT PRIMARY KEY,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    account_type TEXT NOT NULL, -- Asset, Liability, Equity, Revenue, COGS, OpEx, Other
    legacy_sheet_map TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS counterparties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    party_type TEXT NOT NULL, -- AGENCY_AR, SUPPLIER_AP, PARTNER, HOTEL_LANDLORD, BANK
    opening_balance_egp REAL NOT NULL DEFAULT 0,
    total_debit_egp REAL NOT NULL DEFAULT 0,
    total_credit_egp REAL NOT NULL DEFAULT 0,
    current_balance_egp REAL NOT NULL DEFAULT 0,
    source_workbook TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS vessels (
    code TEXT PRIMARY KEY,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    vessel_type TEXT NOT NULL, -- YACHT, SPEEDBOAT, ZODIAC, VEHICLE, EXTERNAL
    max_pax INTEGER NOT NULL DEFAULT 35,
    ownership_structure TEXT NOT NULL DEFAULT '',
    partner_name TEXT NOT NULL DEFAULT '',
    partner_qirat INTEGER NOT NULL DEFAULT 0,
    sea_college_qirat INTEGER NOT NULL DEFAULT 24,
    monthly_fixed_rent_egp REAL NOT NULL DEFAULT 0,
    drydock_capex_total_egp REAL NOT NULL DEFAULT 0,
    drydock_sea_college_share_egp REAL NOT NULL DEFAULT 0,
    drydock_partner_share_egp REAL NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'ACTIVE'
);

CREATE TABLE IF NOT EXISTS hotels_directory (
    code TEXT PRIMARY KEY,
    name_en TEXT NOT NULL,
    zone_code TEXT NOT NULL,
    zone_label_en TEXT NOT NULL,
    zone_label_ar TEXT NOT NULL,
    pickup_window TEXT NOT NULL,
    drive_duration_en TEXT NOT NULL DEFAULT '',
    is_onsite INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS products_catalog (
    code TEXT PRIMARY KEY,
    section TEXT NOT NULL,
    slug TEXT NOT NULL DEFAULT '',
    category_group TEXT NOT NULL,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    price_eur REAL DEFAULT NULL,
    price_egp_est REAL NOT NULL DEFAULT 0,
    direct_cost_ratio REAL NOT NULL DEFAULT 0.35,
    direct_cogs_egp_est REAL NOT NULL DEFAULT 0,
    duration TEXT NOT NULL DEFAULT '',
    is_popular INTEGER NOT NULL DEFAULT 0,
    requires_padi_pic INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS equipment_assets_master (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sc_tag TEXT NOT NULL,
    location_branch TEXT NOT NULL,
    item_category TEXT NOT NULL,
    qty REAL NOT NULL DEFAULT 1,
    brand_description TEXT NOT NULL DEFAULT '',
    serial_or_size TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'AVAILABLE',
    assigned_to TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS safes_wallets (
    code TEXT PRIMARY KEY,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    safe_type TEXT NOT NULL, -- CASHBOX, CUSTODY, POS, BANK
    branch_code TEXT NOT NULL,
    bal_egp REAL NOT NULL DEFAULT 0,
    bal_usd REAL NOT NULL DEFAULT 0,
    bal_eur REAL NOT NULL DEFAULT 0,
    bal_gbp REAL NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL
);

-- ----------------------------------------------------------------------------
-- 3. HISTORICAL & LIVE FACT TABLES (DAILY / WEEKLY / MONTHLY / QUARTERLY / YEARLY)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS etl_workbooks_catalog (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rel_path TEXT UNIQUE NOT NULL,
    category TEXT NOT NULL,
    file_size_bytes INTEGER NOT NULL,
    sheet_count INTEGER NOT NULL DEFAULT 0,
    rows_ingested INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL, -- INGESTED, RESTRICTED_PENDING_ACCESS, SKIPPED
    ingested_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS raw_sheet_rows (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workbook_rel_path TEXT NOT NULL,
    category TEXT NOT NULL,
    sheet_name TEXT NOT NULL,
    row_number INTEGER NOT NULL,
    row_json TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_raw_sheet_cat ON raw_sheet_rows(category, sheet_name);

CREATE TABLE IF NOT EXISTS pnl_monthly_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    period_month TEXT NOT NULL,    -- YYYY-MM
    period_quarter TEXT NOT NULL,  -- YYYY-Qn
    period_year TEXT NOT NULL,     -- YYYY
    branch_code TEXT NOT NULL,     -- CYRENE, JETTY
    section_type TEXT NOT NULL,    -- CASH_REV, ACCRUED_REV, TOTAL_REV, CASH_EXP, ACCRUED_EXP, TOTAL_EXP, NET_PROFIT
    line_name_ar TEXT NOT NULL,
    line_name_en TEXT NOT NULL,
    trip_or_cost_group TEXT NOT NULL,
    amount_egp REAL NOT NULL DEFAULT 0,
    cost_ratio REAL DEFAULT NULL,
    source_file TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_pnl_period ON pnl_monthly_lines(period_year, period_quarter, period_month, branch_code);

CREATE TABLE IF NOT EXISTS daily_revenue_fact (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rev_date TEXT NOT NULL,        -- YYYY-MM-DD
    period_week TEXT NOT NULL,     -- YYYY-Www
    period_month TEXT NOT NULL,    -- YYYY-MM
    period_quarter TEXT NOT NULL,  -- YYYY-Qn
    period_year TEXT NOT NULL,     -- YYYY
    fx_usd REAL NOT NULL DEFAULT 48,
    fx_eur REAL NOT NULL DEFAULT 52,
    fx_gbp REAL NOT NULL DEFAULT 60,
    cyrene_le REAL NOT NULL DEFAULT 0,
    cyrene_usd REAL NOT NULL DEFAULT 0,
    cyrene_eur REAL NOT NULL DEFAULT 0,
    cyrene_gbp REAL NOT NULL DEFAULT 0,
    cyrene_visa REAL NOT NULL DEFAULT 0,
    cyrene_total_egp REAL NOT NULL DEFAULT 0,
    jetty_le REAL NOT NULL DEFAULT 0,
    jetty_usd REAL NOT NULL DEFAULT 0,
    jetty_eur REAL NOT NULL DEFAULT 0,
    jetty_gbp REAL NOT NULL DEFAULT 0,
    jetty_visa REAL NOT NULL DEFAULT 0,
    jetty_total_egp REAL NOT NULL DEFAULT 0,
    combined_total_egp REAL NOT NULL DEFAULT 0,
    notes TEXT NOT NULL DEFAULT '',
    source_file TEXT NOT NULL,
    UNIQUE(rev_date, source_file)
);
CREATE INDEX IF NOT EXISTS idx_daily_rev_dates ON daily_revenue_fact(rev_date, period_month, period_quarter, period_year);

CREATE TABLE IF NOT EXISTS expense_vouchers_fact (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exp_date TEXT NOT NULL,
    period_week TEXT NOT NULL,
    period_month TEXT NOT NULL,
    period_quarter TEXT NOT NULL,
    period_year TEXT NOT NULL,
    voucher_seq TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    cost_center TEXT NOT NULL,
    amount_le REAL NOT NULL DEFAULT 0,
    amount_usd REAL NOT NULL DEFAULT 0,
    amount_eur REAL NOT NULL DEFAULT 0,
    amount_gbp REAL NOT NULL DEFAULT 0,
    total_egp REAL NOT NULL DEFAULT 0,
    source_file TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_exp_dates ON expense_vouchers_fact(exp_date, period_month, period_quarter, period_year);

CREATE TABLE IF NOT EXISTS cashbox_daily_fact (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tx_date TEXT NOT NULL,
    period_week TEXT NOT NULL,
    period_month TEXT NOT NULL,
    period_quarter TEXT NOT NULL,
    period_year TEXT NOT NULL,
    inc_le REAL NOT NULL DEFAULT 0,
    inc_usd REAL NOT NULL DEFAULT 0,
    inc_eur REAL NOT NULL DEFAULT 0,
    inc_gbp REAL NOT NULL DEFAULT 0,
    inc_visa REAL NOT NULL DEFAULT 0,
    exp_le REAL NOT NULL DEFAULT 0,
    exp_usd REAL NOT NULL DEFAULT 0,
    exp_eur REAL NOT NULL DEFAULT 0,
    exp_gbp REAL NOT NULL DEFAULT 0,
    bal_le REAL NOT NULL DEFAULT 0,
    bal_usd REAL NOT NULL DEFAULT 0,
    bal_eur REAL NOT NULL DEFAULT 0,
    bal_gbp REAL NOT NULL DEFAULT 0,
    net_flow_egp_equiv REAL NOT NULL DEFAULT 0,
    source_file TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_cashbox_dates ON cashbox_daily_fact(tx_date, period_month, period_quarter, period_year);

CREATE TABLE IF NOT EXISTS reservations_fact (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    voucher_no TEXT NOT NULL,
    qr_token TEXT NOT NULL DEFAULT '',
    trip_date TEXT NOT NULL,
    period_week TEXT NOT NULL,
    period_month TEXT NOT NULL,
    period_quarter TEXT NOT NULL,
    period_year TEXT NOT NULL,
    branch_code TEXT NOT NULL DEFAULT 'CYRENE',
    channel TEXT NOT NULL DEFAULT 'HOTEL_DESK',
    hotel_name TEXT NOT NULL DEFAULT '',
    pickup_zone TEXT NOT NULL DEFAULT '',
    room_no TEXT NOT NULL DEFAULT '',
    nationality TEXT NOT NULL DEFAULT 'OTHER',
    pickup_time TEXT NOT NULL DEFAULT '',
    trip_name TEXT NOT NULL DEFAULT '',
    product_category TEXT NOT NULL DEFAULT 'SNORKEL_BOAT',
    adults INTEGER NOT NULL DEFAULT 0,
    children INTEGER NOT NULL DEFAULT 0,
    infants INTEGER NOT NULL DEFAULT 0,
    total_pax INTEGER NOT NULL DEFAULT 0,
    snork_pax INTEGER NOT NULL DEFAULT 0,
    intro1_pax INTEGER NOT NULL DEFAULT 0,
    intro2_pax INTEGER NOT NULL DEFAULT 0,
    dives_pax INTEGER NOT NULL DEFAULT 0,
    divers_names TEXT NOT NULL DEFAULT '',
    sales_rep TEXT NOT NULL DEFAULT '',
    executor TEXT NOT NULL DEFAULT '',
    client_agency TEXT NOT NULL DEFAULT '',
    rest_egp REAL NOT NULL DEFAULT 0,
    rest_usd REAL NOT NULL DEFAULT 0,
    rest_eur REAL NOT NULL DEFAULT 0,
    rest_gbp REAL NOT NULL DEFAULT 0,
    rest_egp_equiv REAL NOT NULL DEFAULT 0,
    estimated_revenue_egp REAL NOT NULL DEFAULT 0,
    estimated_cogs_egp REAL NOT NULL DEFAULT 0,
    estimated_profit_egp REAL NOT NULL DEFAULT 0,
    vessel_code TEXT NOT NULL DEFAULT '',
    boarding_status TEXT NOT NULL DEFAULT 'COMPLETED',
    payment_status TEXT NOT NULL DEFAULT 'SETTLED',
    source_file TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS idx_res_dates ON reservations_fact(trip_date, period_month, period_quarter, period_year);
CREATE INDEX IF NOT EXISTS idx_res_rep ON reservations_fact(sales_rep, client_agency, product_category);
CREATE INDEX IF NOT EXISTS idx_res_vo ON reservations_fact(voucher_no);

CREATE TABLE IF NOT EXISTS vessel_trips_fact (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_date TEXT NOT NULL,
    period_week TEXT NOT NULL,
    period_month TEXT NOT NULL,
    period_quarter TEXT NOT NULL,
    period_year TEXT NOT NULL,
    vessel_code TEXT NOT NULL,
    vessel_name_ar TEXT NOT NULL,
    adults INTEGER NOT NULL DEFAULT 0,
    children INTEGER NOT NULL DEFAULT 0,
    infants INTEGER NOT NULL DEFAULT 0,
    total_pax INTEGER NOT NULL DEFAULT 0,
    launch_fee_egp REAL NOT NULL DEFAULT 0,
    food_catering_egp REAL NOT NULL DEFAULT 0,
    supermarket_egp REAL NOT NULL DEFAULT 0,
    total_direct_cost_egp REAL NOT NULL DEFAULT 0,
    estimated_trip_rev_egp REAL NOT NULL DEFAULT 0,
    estimated_trip_net_egp REAL NOT NULL DEFAULT 0,
    staff_crew TEXT NOT NULL DEFAULT '',
    notes TEXT NOT NULL DEFAULT '',
    source_file TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_vessel_dates ON vessel_trips_fact(vessel_code, trip_date, period_month, period_year);

CREATE TABLE IF NOT EXISTS speedboat_trips_fact (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_date TEXT NOT NULL,
    period_week TEXT NOT NULL,
    period_month TEXT NOT NULL,
    period_quarter TEXT NOT NULL,
    period_year TEXT NOT NULL,
    boat_code TEXT NOT NULL, -- YOUANA, SHARM_REEB, SPEED, FUEL_BENZINE
    voucher_no TEXT NOT NULL DEFAULT '',
    banana_qty REAL NOT NULL DEFAULT 0,
    tube_qty REAL NOT NULL DEFAULT 0,
    ski_qty REAL NOT NULL DEFAULT 0,
    private_trips REAL NOT NULL DEFAULT 0,
    diving_intro_qty REAL NOT NULL DEFAULT 0,
    revenue_egp REAL NOT NULL DEFAULT 0,
    fuel_cost_egp REAL NOT NULL DEFAULT 0,
    notes TEXT NOT NULL DEFAULT '',
    source_file TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS drydock_capex_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tx_date TEXT NOT NULL DEFAULT '',
    period_year TEXT NOT NULL,
    vessel_code TEXT NOT NULL,
    item_description TEXT NOT NULL,
    qty REAL NOT NULL DEFAULT 1,
    unit_price_egp REAL NOT NULL DEFAULT 0,
    total_egp REAL NOT NULL DEFAULT 0,
    sea_college_share_egp REAL NOT NULL DEFAULT 0,
    partner_share_egp REAL NOT NULL DEFAULT 0,
    source_file TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS subledger_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ledger_type TEXT NOT NULL, -- AR_CLIENT, AP_SUPPLIER, PARTNER_EQUITY, VESSEL_RENT, HOTEL_RENT, PADI_PIC, POS_VISA
    party_code TEXT NOT NULL,
    party_name TEXT NOT NULL,
    tx_date TEXT NOT NULL DEFAULT '',
    period_month TEXT NOT NULL DEFAULT '',
    period_year TEXT NOT NULL DEFAULT '',
    voucher_no TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    debit_egp REAL NOT NULL DEFAULT 0,
    credit_egp REAL NOT NULL DEFAULT 0,
    running_balance_egp REAL NOT NULL DEFAULT 0,
    notes TEXT NOT NULL DEFAULT '',
    source_file TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_subledger_party ON subledger_lines(ledger_type, party_code, period_year);

CREATE TABLE IF NOT EXISTS payroll_monthly_fact (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    period_month TEXT NOT NULL,
    period_quarter TEXT NOT NULL,
    period_year TEXT NOT NULL,
    emp_name_ar TEXT NOT NULL,
    job_title_ar TEXT NOT NULL DEFAULT '',
    branch_code TEXT NOT NULL DEFAULT 'CYRENE',
    base_salary_egp REAL NOT NULL DEFAULT 0,
    worked_days REAL NOT NULL DEFAULT 0,
    actual_wage_egp REAL NOT NULL DEFAULT 0,
    commission_egp REAL NOT NULL DEFAULT 0,
    allowances_egp REAL NOT NULL DEFAULT 0,
    bonuses_egp REAL NOT NULL DEFAULT 0,
    advances_deductions_egp REAL NOT NULL DEFAULT 0,
    net_pay_egp REAL NOT NULL DEFAULT 0,
    source_file TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_payroll_period ON payroll_monthly_fact(period_year, period_month, emp_name_ar);

CREATE TABLE IF NOT EXISTS website_leads_crm (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id TEXT UNIQUE NOT NULL,
    received_at TEXT NOT NULL,
    lead_type TEXT NOT NULL, -- BOOKING_LEAD, DIVER_CHECKIN, WHATSAPP_CLICK, ADS_SYNC
    lang TEXT NOT NULL DEFAULT 'en',
    guest_name TEXT NOT NULL DEFAULT '',
    phone TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    interest_items TEXT NOT NULL DEFAULT '',
    people INTEGER NOT NULL DEFAULT 1,
    trip_date TEXT NOT NULL DEFAULT '',
    hotel TEXT NOT NULL DEFAULT '',
    room_no TEXT NOT NULL DEFAULT '',
    cert_level TEXT NOT NULL DEFAULT '',
    gear_sizes_json TEXT NOT NULL DEFAULT '{}',
    estimated_eur REAL NOT NULL DEFAULT 0,
    final_paid_eur REAL NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'NEW', -- NEW, CONTACTED_LARA, VOUCHER_ISSUED, PAID_COMPLETED
    gclid TEXT NOT NULL DEFAULT '',
    fbclid TEXT NOT NULL DEFAULT '',
    utm_source TEXT NOT NULL DEFAULT '',
    utm_campaign TEXT NOT NULL DEFAULT '',
    assigned_to TEXT NOT NULL DEFAULT 'LARA',
    linked_voucher_no TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS journal_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_no TEXT UNIQUE NOT NULL,
    entry_date TEXT NOT NULL,
    source_module TEXT NOT NULL,
    reference_no TEXT NOT NULL,
    branch_code TEXT NOT NULL,
    created_by TEXT NOT NULL DEFAULT 'SYSTEM',
    description_en TEXT NOT NULL,
    description_ar TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS journal_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    journal_id INTEGER NOT NULL,
    account_code TEXT NOT NULL,
    debit_egp REAL NOT NULL DEFAULT 0,
    credit_egp REAL NOT NULL DEFAULT 0,
    orig_currency TEXT NOT NULL DEFAULT 'EGP',
    orig_amount REAL NOT NULL DEFAULT 0,
    vessel_code TEXT DEFAULT NULL,
    FOREIGN KEY (journal_id) REFERENCES journal_entries(id) ON DELETE CASCADE
);
