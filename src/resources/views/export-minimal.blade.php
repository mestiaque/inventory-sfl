{{-- No <title> tag on purpose: PhpSpreadsheet's HTML reader re-reads it
     during parsing and calls Worksheet::setTitle() with the raw text,
     silently overwriting InvReportExport::title() (capped at 31 chars) —
     a long page title (e.g. "Store Inventory Report - Suhana Fashions
     Ltd") then blows the Excel sheet-name limit and throws. --}}
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
@yield('contents')
</body>
</html>
