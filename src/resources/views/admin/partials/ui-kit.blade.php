
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
.inv-module .table-responsive { border-radius: 10px; border: 1px solid #f0f0f2; }
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
.inv-module .select2-container--default .select2-selection--single { border-radius: 8px !important; border-color: #e4e4e7 !important; height: auto !important; }

/* Active toggle switch — this theme bundles Bootstrap 4, which has no native
   .form-switch (that's Bootstrap 5 only), so plain `form-check form-switch`
   renders as a bare checkbox. This draws a real switch by hand. */
.inv-module .form-switch { position: relative; padding-left: 3.25rem; min-height: 1.6rem; display: flex; align-items: center; }
.inv-module .form-switch .form-check-input {
    -webkit-appearance: none; appearance: none; position: absolute; left: 0; top: 0;
    width: 2.75rem; height: 1.5rem; margin: 0; border: none; border-radius: 999px;
    background: #d1d5db; cursor: pointer; transition: background-color .2s ease; outline: none;
}
.inv-module .form-switch .form-check-input::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px;
    background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.35); transition: transform .2s ease;
}
.inv-module .form-switch .form-check-input:checked { background: var(--inv-accent); }
.inv-module .form-switch .form-check-input:checked::after { transform: translateX(20px); }
.inv-module .form-switch .form-check-input:focus { box-shadow: 0 0 0 .2rem rgba(249,115,22,.2); }
.inv-module .form-switch .form-check-label { font-weight: 700; color: var(--inv-ink); cursor: pointer; margin-bottom: 0; }

/* Submit-guard visual state — see the script below. */
.inv-module .btn.inv-submitting { opacity: .65; cursor: progress; pointer-events: none; }
</style>

{{--
    Global double-submit guard: on a slow network or a laggy PC, a Save/
    Approve/Reject/etc. click can feel like it didn't register, so the user
    clicks again — silently creating a duplicate GRN, requisition, issue,
    etc. This disables every submit button on a form the instant it actually
    submits (delegated on document, so it also covers delete/trash modal
    forms and anything added dynamically), before a second click has any
    chance to fire another submission. Runs once even if this partial is
    ever included more than once on the same page.
--}}
<script>
(function () {
    if (window.__invDoubleSubmitGuardInstalled) {
        return;
    }
    window.__invDoubleSubmitGuardInstalled = true;

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.invNoGuard === '1') {
            return;
        }

        // The browser only builds the actual submitted form data AFTER this
        // handler returns, reading the DOM's state at that point — so if the
        // clicked button (the "submitter", carrying e.g. name="decision"
        // value="approve") gets disabled here, that name=value pair is
        // silently dropped from the request. Copy it into a hidden input
        // first so multi-button forms (Approve/Reject, etc.) keep working.
        var submitter = e.submitter || (
            document.activeElement
            && form.contains(document.activeElement)
            && document.activeElement.matches('button[type="submit"], input[type="submit"]')
            ? document.activeElement
            : null
        );
        if (submitter && submitter.name) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = submitter.name;
            hidden.value = submitter.value;
            form.appendChild(hidden);
        }

        form.querySelectorAll('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])').forEach(function (btn) {
            btn.disabled = true;
            btn.classList.add('inv-submitting');
        });
    });

    // Browsers can restore a page from bfcache (e.g. the user hits Back
    // after a slow submit) with those buttons still disabled from before —
    // release them so the page isn't stuck looking broken.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) {
            return;
        }
        document.querySelectorAll('form button.inv-submitting, form input.inv-submitting').forEach(function (btn) {
            btn.disabled = false;
            btn.classList.remove('inv-submitting');
        });
    });
})();
</script>
