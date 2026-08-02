{{-- Shared visual design system for every Inventory Management page. Scoped
     under .inv-module so it only affects this page's own markup — include
     once near the top of any index/create/edit view, right after the
     wrapping <div> gets the `inv-module` class added. Targets the existing
     Bootstrap classes already used everywhere (.card, .table, .badge, .btn,
     form.row.g-2 filter bars) so pages get the upgrade without needing their
     HTML restructured. --}}
<style>
.inv-module { --inv-accent: #f97316; --inv-accent-dark: #ea580c; --inv-ink: #1f2937; }

/* Cards */
.inv-module .card { border: none; border-radius: 14px; box-shadow: 0 2px 14px rgba(0,0,0,.07); margin-bottom: 1.25rem; }
.inv-module .card-header {
    background: #fff; border-bottom: 2px solid #fef3e8; border-radius: 14px 14px 0 0 !important;
    padding: 16px 20px; display: flex; flex-wrap: wrap; gap: 10px;
}
.inv-module .card-header h5, .inv-module .card-header h6 {
    font-weight: 800; color: var(--inv-ink); border-left: 4px solid var(--inv-accent); padding-left: 12px; margin: 0;
}
.inv-module .card-body { padding: 20px; }

/* Filter bar */
.inv-module form.row.g-2 {
    background: #fbfbfc; border: 1px solid #f0f0f2; border-radius: 10px; margin: 0 0 18px; padding: 14px 12px 4px;
}
.inv-module form.row.g-2 .form-control, .inv-module form.row.g-2 select { border-radius: 8px; }

/* Forms (create/edit) */
.inv-module .form-label { font-weight: 700; font-size: 12.5px; text-transform: uppercase; letter-spacing: .4px; color: #52525b; margin-bottom: 6px; }
.inv-module .form-control, .inv-module select.form-control, .inv-module textarea.form-control {
    border-radius: 8px; border-color: #e4e4e7;
}
.inv-module .form-control:focus, .inv-module select.form-control:focus {
    border-color: var(--inv-accent); box-shadow: 0 0 0 .2rem rgba(249,115,22,.15);
}
.inv-module .form-text { font-size: 12px; color: #9ca3af; }
.inv-module hr { border-top: 2px dashed #f0f0f2; margin: 22px 0; }

/* Tables */
.inv-module .table-responsive { border-radius: 10px; overflow: hidden; border: 1px solid #f0f0f2; }
.inv-module .table { margin-bottom: 0; }
.inv-module .table thead th {
    background: #fff8ed; color: #9a3412; font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px;
    font-weight: 800; border-bottom: none; padding: 12px 14px; white-space: nowrap;
}
.inv-module .table tbody td { padding: 12px 14px; vertical-align: middle; font-size: 13.5px; color: #374151; border-color: #f4f4f5; }
.inv-module .table-striped tbody tr:nth-of-type(odd) { background-color: #fafafa; }
.inv-module .table tbody tr:hover { background-color: #fff8ed; }
.inv-module .table tbody tr td.text-center.text-muted { padding: 34px 14px; font-size: 13.5px; }

/* Badges */
.inv-module .badge { border-radius: 999px; padding: 5px 12px; font-size: 11px; font-weight: 700; letter-spacing: .2px; }

/* Buttons */
.inv-module .btn { border-radius: 8px; font-weight: 600; }
.inv-module .btn-sm { border-radius: 7px; padding: .3rem .65rem; font-size: 12.5px; }
.inv-module .btn-primary { background: var(--inv-accent); border-color: var(--inv-accent); }
.inv-module .btn-primary:hover, .inv-module .btn-primary:focus { background: var(--inv-accent-dark); border-color: var(--inv-accent-dark); }
.inv-module .btn-outline-primary { color: var(--inv-accent-dark); border-color: var(--inv-accent); }
.inv-module .btn-outline-primary:hover { background: var(--inv-accent); border-color: var(--inv-accent); }
.inv-module td.text-end .btn, .inv-module td.text-end form { margin-left: 4px; }
.inv-module td.text-end { white-space: nowrap; }

/* Pagination */
.inv-module .pagination { margin-top: 14px; }
.inv-module .page-link { border-radius: 8px; margin: 0 2px; border-color: #f0f0f2; color: var(--inv-ink); }
.inv-module .page-item.active .page-link { background: var(--inv-accent); border-color: var(--inv-accent); }

/* Modals (master CRUD add/edit/delete popups) */
.inv-module .modal-content { border: none; border-radius: 14px; overflow: hidden; }
.inv-module .modal-header { background: #fff8ed; border-bottom: 1px solid #fde9d4; padding: 16px 20px; }
.inv-module .modal-header .modal-title { font-weight: 800; color: var(--inv-ink); }
.inv-module .modal-body { padding: 20px; }
.inv-module .modal-footer { border-top: 1px solid #f4f4f5; padding: 14px 20px; }

/* Alerts */
.inv-module .alert { border: none; border-radius: 10px; font-size: 13.5px; }

/* Select2 tweaks to match rounded inputs */
.inv-module .select2-container--default .select2-selection--single { border-radius: 8px !important; border-color: #e4e4e7 !important; height: calc(1.5em + .75rem + 2px) !important; }
</style>
