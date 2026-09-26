{{--
    Shared UI for SFL Inventory pages — matches the HR module on the host's
    Bootstrap 4 theme: plain .card, .table-bordered.table-sm, small buttons,
    and icon-only row actions as .btn-custom (yellow = edit, danger = delete,
    success = view/approve, primary = print/label) from admin/assets/css/custom.css.
--}}
<style>
    /* Same sizing HR applies to its row-action buttons. */
    .btn-custom { padding: 0 3px !important; height: auto !important; }
    /* Bootstrap-5 utilities still used by a few layouts, not in BS 4.4. */
    .gap-1 { gap: .25rem; } .gap-2 { gap: .5rem; } .gap-3 { gap: 1rem; }
    .form-label { margin-bottom: .25rem; }
    .inv-module .table td, .inv-module .table th { vertical-align: middle; }
    /* Table headers: black text on light grey (host layout sets white-on-grey). */
    table.table thead { background: #e9ecef !important; color: #000 !important; }
    table.table thead th { color: #000 !important; }
    /* Select2 at the same height as .form-control-sm inputs. */
    .select2-container .select2-selection--single { height: calc(1.5em + .5rem + 2px) !important; font-size: .875rem; }
    .select2-container .select2-selection--single .select2-selection__rendered { line-height: calc(1.5em + .5rem) !important; }
    .select2-container .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .5rem) !important; }
    .select2-container .select2-selection--multiple { min-height: calc(1.5em + .5rem + 2px) !important; font-size: .875rem; }
    /* Row-action cells stay on one line. */
    .inv-module .table td.text-right:last-child { white-space: nowrap; }
    .inv-module .table td:last-child > form { display: inline-block; }
    /* Submit-guard visual state — see the script below. */
    .inv-module .btn.inv-submitting, .inv-module .inv-submitting { opacity: .65; cursor: progress; pointer-events: none; }
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
