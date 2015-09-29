<?php
require_once('./HumanName.php');

function check_email($line) {
    if (preg_match('/([\@0-9](?:[a-z0-9](?:[-+]?\w+)*[\.\,\s])+[a-z]{2,})/i', $line)) {
        if (preg_match('/([a-z0-9](?:[-.+_]?[a-z0-9]+)+\@(?:[a-z0-9](?:[-+]?[a-z0-9]+)*\.)+[a-z]{2,})/i', $line, $matches)) {
            $pos = stripos($line, $matches[0]);
            if (substr($line, stripos($line, $matches[0]) - 1, 1) == ' ') {
                return $matches[0];
            }
            return false;
        }
        return false;
    }
}

$good_lines = array();
$bad_lines = array();

$fp = @fopen('names.txt', 'r');
if ($fp) {
    while (($buffer = fgets($fp, 4096)) !== false) {
        if (!$email = check_email($buffer)) {
            $bad_lines[] = trim($buffer);
            continue;
        }

        $hn = new HumanName(str_replace($email, '', $buffer));

        if (!$hn->is_parseable()) {
            $bad_lines[] = trim($hn->full_name()).str_repeat('	', 7);
            continue;
        }
    
        $good_lines[] = $hn->delimited('	', array($email));
    }
    if (!feof($fp)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($fp);
}

$fp = @fopen('names.csv', 'w');
if ($fp) {
    fwrite($fp, 'full name	title	first name	middle name	last name	suffix	nickname	email address'.PHP_EOL);

    foreach (array_merge($bad_lines, $good_lines) as $line) {
        fwrite($fp, $line.PHP_EOL);
    }

    fclose($fp);
}
?>
