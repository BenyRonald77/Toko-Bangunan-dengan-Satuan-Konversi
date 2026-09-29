# DESIGN.md — Toko Bangunan dengan Satuan Konversi

**Reading this as:** internal back-office / POS-style admin tool for a toko bangunan, for
admin and kasir on a shared desktop/tablet at the counter, in a plain utilitarian visual
language, dial **ENERGY 2 / RHYTHM 2 / MOTION 1**.

This is not a marketing site: nobody outside the store's own staff will ever see it. Every
decision below optimizes for "can a tired kasir finish a transaction fast and without
mistakes", not for making a visitor feel something.

## Dials

| Dial | Value | What that means here |
|---|---|---|
| ENERGY | 2 (Balanced) | Clean and modern like an admin dashboard (Stripe/Vercel register), not a flat GOV.UK form, but never loud. |
| RHYTHM | 2 (Consistent with a few breaks) | List/table screens (Produk, Piutang) share one composition; the sale-entry screen and the aging report break that pattern because their job is different (fast repeated input vs. a grouped report). |
| MOTION | 1 (Hover states only) | No scroll-reveal, no page-transition choreography. A back-office tool is used all day; motion should never make a cashier wait. Livewire's own loading/disabled states are the only "motion" present. |

## Identity

- **Product**: single-tenant internal tool, one toko bangunan, two roles (admin, kasir).
- **Personality**: efficient, trustworthy, a little "workshop" rather than "startup". It should
  feel closer to an accounting ledger than to a SaaS landing page.
- **No illustrations**: nothing to illustrate — every screen is a form or a table over real
  data. An illustration here would be decoration with nothing to connect to (R-22).
- **No fake stats, no testimonials**: any number shown (stock, totals, aging buckets) is a real
  query result. If a screen would otherwise be empty, it says so and names the next action
  (R-17, R-27, R-38).

## Palette

- **Neutral base**: Tailwind `slate` scale (backgrounds, borders, body text) — Breeze's own
  default neutral, kept because there is no reason to fight it: the interface is 95% tables,
  labels and forms, and a neutral base keeps that content legible over anything decorative.
- **One accent: amber (`amber-600` light / `amber-500` dark)**. *Reason:* amber/orange is the
  toko bangunan's own visual vocabulary — safety vests, tool handles, warning tape, hazard
  signage on a real construction site — so it reads as "hardware store", not as a generic web
  accent, and it is different enough from the "blue-purple SaaS default" this filter forbids
  (R-01, R-29). It is used only where one thing must stand out: primary buttons, the active nav
  item, low-stock and jatuh-tempo badges, and focus rings. Everything else stays neutral.
- Semantic colors (not part of the core 2-3 + 1 accent count, per R-29): `emerald` for
  lunas/paid states, `red` for jatuh tempo / stock errors — these are status colors, not brand
  decoration, and they carry real meaning (R-31).
- **Fixed light theme, no dark mode toggle.** *Reason (R-21):* this runs on the store's own
  counter PC/tablet, read under bright daytime shop lighting next to paper invoices and a
  cash drawer — a single high-contrast light theme is the safer default for that environment,
  and nobody has asked for dark mode. This is a stated product/context reason, not a shortcut:
  building an untested second theme for a two-role internal tool would add surface area with no
  real user behind it.

## Typography

- **Figtree** (Breeze's own default sans, loaded via Bunny Fonts). *Reason:* this is a
  data-dense internal tool where legibility of numbers and short labels at small sizes matters
  far more than typographic personality; Figtree's proportions and x-height are made for exactly
  that, and introducing a second typeface would add a decision with no payoff for an audience of
  two roles (R-06).
- No large monospace headings, no uppercase wide-tracking labels (R-06).

## Layout

- **Why a sidebar shell exists here and is not a default**: the app genuinely has several
  independent modules an admin moves between all day (Produk, Pelanggan, Penjualan, Piutang),
  and a kasir needs the single "Penjualan baru" screen to be one click away at all times. A
  persistent left nav is the correct tool for that job, not a template reflex (C-3, R-20 in
  `antislop-ui`'s "Default Dashboard Shell" entry).
- **Tables, not cards**: lists of products, sales, receivables are real tables with columns the
  user actually needs to make a decision (e.g. Piutang: pelanggan, sisa, jatuh tempo, status —
  the deciding field, sisa/status, is not buried after a menu button).
- **No stat-card row with invented numbers**: the Piutang page's aging report is the one place
  with summary numbers, and every number there is a real `COUNT`/`SUM` over the receivables that
  exist, labelled by its real bucket name, never a decorative KPI row.
- **RHYTHM break**: the sale-entry screen (Feature 1) is a single-focus form with a running
  item list and a stock/price preview next to it — a different composition from the table
  screens, because its job (fast repeated data entry with live feedback) is different from
  "browse and manage a list" (R-05).

## States (R-27, mandatory on every data screen)

- **Empty**: named cause + one action, e.g. "Belum ada transaksi hari ini. Buat penjualan baru"
  with a working link to the sale form, not a bare "No data".
- **Loading**: Livewire's native `wire:loading` on the submit button/table region — a button
  that says "Menyimpan..." and disables itself, not a generic spinner overlay.
- **Error**: server-side validation and business-rule errors (stok tidak cukup, tier harga
  belum ada, dsb.) render inline next to the field or item they refer to, in plain Indonesian,
  never a raw exception.

## Explicitly rejected patterns (named so nobody re-adds them later)

- Gradients, glassmorphism, glow, decorative badges/pills, arrows-on-every-button: none of
  these serve a data-entry tool, so none are used (R-01, R-09, R-10, R-13).
- "Trusted by" bars, testimonials, marketing FAQ, hero sections: this app has no visitors to
  market to, so these sections do not exist (C-3).
