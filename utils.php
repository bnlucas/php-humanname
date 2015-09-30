<?php
/**
 * Regex
 *
 * Regex is a localized collection of regex patterns used in HumanName.
 *
 * @author      Nathan Lucas <nathan@bnlucas.com>
 * @copyright   2015 Nathan Lucas
 * @version     1.0.0
 * @package     HumanName
 * @version    Release: @package_version@
 * @link       http://framework.zend.com/package/PackageName
 */
namespace HumanName;

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

/**
 * lc(value) converts `value` to lowercase and strips any leading `.`
 * @param string $value
 * @return string
 */
function lc($value) {
    if (!$value) {
        return '';
    }
    return trim(strtolower($value), '.');
}

/**
 * pop(array, key) was made to resemble Python's list.pop([key]) in order to
 * remove Array[key] and reset the key index, while unset(array[key]) does not.
 * @param array $arr
 * @param int $key
 * @return array
 */
function pop($arr, $key) {
    unset($arr[$key]);
    return array_values($arr);
}

/**
 * slice($arr, $m[, $n]) was written to match Python's list[$m:$n] as I was
 * running into issues using `array_slice(array, m[, n])` in the HumanName class
 * as it was ported from Python.
 *
 * @param array $arr
 * @param int $m
 * @param int $n
 * @return array
 */
function slice($arr, $m, $n = null) {
    if ($m === $n) {
        return array();
    }

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
