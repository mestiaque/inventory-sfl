@push('js')
<script>
    function invSelect2Init(scope) {
        scope = scope || document;
        $(scope).find('.inv-select2').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                return;
            }
            $(this).select2({
                theme: 'default',
                width: '100%',
                dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : $(document.body),
            });
        });
    }
    $(function () {
        invSelect2Init(document);
    });
</script>
@endpush
