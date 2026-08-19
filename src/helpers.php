<?php

if (! function_exists('inv_qty')) {
    /**
     * Format a quantity for display, trimming insignificant trailing zeros
     * without eating into the integer part (unlike a bare
     * rtrim(rtrim($value, '0'), '.'), which turns "100" into "1" since it
     * has no decimal point to stop at).
     */
    function inv_qty($value, int $decimals = 2): string
    {
        return rtrim(rtrim(number_format((float) $value, $decimals, '.', ''), '0'), '.') ?: '0';
    }
}
