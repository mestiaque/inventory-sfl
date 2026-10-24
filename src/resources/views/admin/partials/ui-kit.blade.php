{{--
    Buttons, badges, tables, forms, select2, filter bars and pagination are styled once for the
    whole admin in the host's public/admin/assets/css/ui.css — only module-specific bits live here.
    Shared UI for SFL Inventory pages — matches the HR module on the host's
    Bootstrap 4 theme: plain .card, .table-bordered.table-sm, small buttons,
    and icon-only row actions as .btn-custom (yellow = edit, danger = delete,
    success = view/approve, primary = print/label).
--}}
<style>
    .form-label { margin-bottom: .25rem; }
    .inv-module .table td, .inv-module .table th { vertical-align: middle; }
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
