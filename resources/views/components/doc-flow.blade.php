<style>
:root{
  --brand:#2B4A72;        /* main blue */
  --ink:#0f172a;          /* text */
  --muted:#64748b;        /* label */
  --line:#e5e7eb;         /* border */
  --bg:#f8fafc;           /* page bg */
  --card:#ffffff;         /* card bg */
}
body{background:var(--bg)}
.fa-wrap{max-width:1160px;margin:0 auto;padding:20px}
.fa-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.fa-title{font-size:20px;font-weight:700;color:var(--ink)}
.fa-actions{display:flex;gap:8px}
.fa-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid var(--line);padding:8px 12px;text-decoration:none;font-weight:600}
.fa-btn.save{background:var(--brand);color:#fff;border-color:var(--brand)}
.fa-btn.light{background:#fff;color:var(--ink)}
.fa-card{background:var(--card);border:1px solid var(--line);border-radius:14px}
.fa-grid{display:grid;grid-template-columns:1fr 340px;gap:16px}
@media (max-width: 992px){.fa-grid{grid-template-columns:1fr}}
.fa-section{padding:16px}
.fa-meta dl{display:grid;grid-template-columns:130px 1fr;gap:8px 12px;margin:0}
.fa-meta dt{color:var(--muted)}
.fa-meta dd{margin:0;font-weight:700}
.fa-label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}
.fa-input, .fa-select, .fa-textarea{
  width:100%;background:#fff;border:1px solid var(--line);border-radius:10px;padding:9px 10px;
}
.fa-textarea{min-height:84px}
.fa-table{width:100%;border-collapse:separate;border-spacing:0 0}
.fa-table thead th{
  background:var(--brand);color:#fff;border:0;padding:10px 12px;font-weight:700
}
.fa-table tbody td{
  background:#fff;border-bottom:1px solid var(--line);padding:10px 12px;vertical-align:middle
}
.fa-table .no{width:64px;text-align:center}
.fa-table .qty,.fa-table .price,.fa-table .line{text-align:right;width:140px}
.fa-badge{display:inline-block;background:#eef2ff;color:var(--brand);border:1px solid var(--brand);
  padding:2px 8px;border-radius:999px;font-size:12px}
.fa-sticky{position:sticky;top:16px}
.fa-totals .row{display:flex;justify-content:space-between;margin:6px 0}
.fa-totals .row strong{font-weight:800}
.fa-add{margin-top:8px}
.fa-del{background:#fff;border:1px solid var(--line);border-radius:8px;padding:4px 10px}
.text-right{text-align:right}
</style>
