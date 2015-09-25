<?php
class regex {
    const spaces            = '/\s+/';
    const word              = '/\w+/';
    const mac               = '/^(ma?c)(\w+)/i';
    const initial           = '/^(\w\.|[A-Z])?$/';
    const nickname          = '/\s*?[\("\'](.+?)[\)"\']/';
    const roman_numeral     = '/^(x|ix|iv|v?i{0,3})$/i';
    const no_vowels         = '/[^aeiouy]+$/i';
    const period_not_at_end = '/.*\..+$/i';
}


function lc($value) {
    if (!$value) {
        return '';
    }
    return trim(strtolower($value), '.');
}

function pop($arr, $key) {
    unset($arr[$key]);
    return array_values($arr);
}

function slice($arr, $m, $n = null) {
    if ($n == null) {
        $n = count($arr);
    }
    $out = array();
    for ($i = $m; $i < $n; $i++) {
        $out[] = $arr[$i];
    }
    return $out;
}
?>
