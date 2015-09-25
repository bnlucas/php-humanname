<?php
require_once('./utils.php');

require_once('./constants/capitalization.php');
require_once('./constants/conjunctions.php');
require_once('./constants/prefixes.php');
require_once('./constants/suffixes.php');
require_once('./constants/titles.php');


class HumanName {

    private $_full_name = '';

    private $title_list = array();

    private $first_list = array();

    private $middle_list = array();

    private $last_list = array();

    private $suffix_list = array();

    private $nickname_list = array();

    private $unparsable = true;

    private $C = array();

    public function __construct($full_name) {
        global $CONJUNCTIONS;
        global $PREFIXES;
        global $SUFFIX_NOT_ACRONYMS;
        global $SUFFIX_ACRONYMS;
        global $SUFFIXES;
        global $FIRST_NAME_TITLES;
        global $TITLES;

        $this->C = array(
            'conjunctions' => $CONJUNCTIONS,
            'prefixes' => $PREFIXES,
            'suffix_not_acronyms' => $SUFFIX_NOT_ACRONYMS,
            'suffix_acronyms' => $SUFFIX_ACRONYMS,
            'suffixes' => $SUFFIXES,
            'titles' => $TITLES,
            'first_name_titles' => $FIRST_NAME_TITLES,
            'suffixes_prefixes_titles' => array_unique(array_merge($PREFIXES, $SUFFIXES, $TITLES)),
        );

        $this->full_name = $full_name;
        $this->_full_name = $full_name;
        $this->parse_full_name();
    }

    public function title() {
        return implode(' ', $this->title_list);
    }

    public function first() {
        return implode(' ', $this->first_list);
    }

    public function middle() {
        return implode(' ', $this->middle_list);
    }

    public function last() {
        return implode(' ', $this->last_list);
    }

    public function suffix() {
        return implode(' ', $this->suffix_list);
    }

    public function nickname() {
        return implode(' ', $this->nickname_list);
    }

    private function test() {
        $test = array(
            $this->title_list,
            $this->first_list,
            $this->middle_list,
            $this->last_list,
            $this->suffix_list,
            $this->nickname_list,
        );

        $count = 0;
        foreach ($test as $item) {
            if (count($item) > 0) {
                $count += 1;
            }
        }

        return $count;
    }

    private function parse_full_name() {
        $this->title_list = array();
        $this->first_list = array();
        $this->middle_list = array();
        $this->last_list = array();
        $this->suffix_list = array();
        $this->nickname_list = array();
        $this->unparsable = true;

        $this->pre_process();

        $this->_full_name = $this->collapse_whitespace($this->_full_name);

        $parts = array();
        foreach (explode(',', $this->_full_name) as $part) {
            $parts[] = trim($part);
        }

        if (count($parts) == 1) {
            $pieces = $this->parse_pieces($parts);
            $p_len = count($pieces);

            $i = -1;
            foreach ($pieces as $piece) {
                $i++;
                if (array_key_exists($i + 1, $pieces)) {
                    $nxt = $pieces[$i + 1];
                } else {
                    $nxt = null;
                }

                if ($this->is_title($piece) && ($nxt || ($p_len == 1)) && !$this->first()) {
                    $this->title_list[] = $piece;
                    continue;
                }

                if (!$this->first()) {
                    $this->first_list[] = $piece;
                    continue;
                }

                if ($this->are_suffixes(slice($pieces, $i + 1)) || ($this->is_roman_numeral($nxt) && ($i == $p_len - 2) && !$this->is_an_initial($piece))) {
                    $this->last_list[] = $piece;
                    $this->suffix_list = array_merge($this->suffix_list, slice($pieces, $i + 1));
                    break;
                }

                if (!$nxt) {
                    $this->last_list[] = $piece;
                    print 'null';
                    continue;
                }

                $this->middle_list[] = $piece;
            }
        } else {
            if ($this->are_suffixes(explode(' ', $parts[1]))) {
                $this->suffix_list = array_merge($this->suffix_list, slice($pieces, 1));

                $pieces = $this->parse_pieces(explode(' ', $parts[0]));
                
                $i = -1;
                foreach ($pieces as $piece) {
                    $i++;
                    if (array_key_exists($i + 1, $pieces)) {
                        $nxt = $pieces[$i + 1];
                    } else {
                        $nxt = null;
                    }

                    if ($this->is_title($piece) && ($nxt || (count($pieces) == 1)) && !$this->first()) {
                        $this->title_list[] = $piece;
                        continue;
                    }

                    if (!$this->first) {
                        $this->first_list[] = $piece;
                        continue;
                    }

                    if ($this->are_suffixes(slice($pieces, $i + 1))) {
                        $this->last_list[] = $pieces;
                        $this->suffix_list = array_merge(slice($pieces, $i + 1), $this->suffix_list);
                        break;
                    }

                    if (!$nxt) {
                        $this->last_list[] = $piece;
                        continue;
                    }

                    $this->middle_list[] = $piece;
                }
            } else {
                $pieces = explode(' ', $parts[1], 1);
                $lastname_pieces = $this->parse_pieces(explode(' ', $parts[0], 1));

                foreach ($lastname_pieces as $piece) {
                    if ($this->is_suffix($piece) && (count($this->last_list) > 0)) {
                        $this->suffix_list[] = $piece;
                    } else {
                        $this->last_list[] = $piece;
                    }
                }

                $i = -1;
                foreach ($pieces as $piece) {
                    $i++;
                    if (array_key_exists($i + 1, $pieces)) {
                        $nxt = $pieces[$i + 1];
                    } else {
                        $nxt = null;
                    }

                    if ($this->is_title($piece) && ($nxt || (count($pieces) == 1)) && !$this->first()) {
                        $this->title_list[] = $piece;
                        continue;
                    }

                    if (!$this->first()) {
                        $this->first_list[] = $piece;
                        continue;
                    }

                    if ($this->is_suffix($piece)) {
                        $this->suffix_list[] = $piece;
                        continue;
                    }

                    $this->middle_list[] = $piece;
                }

                if (array_key_exists(2, $parts)) {
                    foreach (slice($parts, 2) as $part) {
                        $this->suffix_list[] = $part;
                    }
                }
            }
        }

        if ($this->test() > 0) {
            $this->unparsable = False;
        }

        $this->post_process();
    }

    private function pre_process() {
        $this->parse_nicknames();
    }

    private function post_process() {
        $this->handle_firstnames();
    }

    private function parse_nicknames() {
        if (preg_match(regex::nickname, $this->_full_name)) {
            if (preg_match_all(regex::nickname, $this->_full_name, $nicknames)) {
                $this->nickname_list = $nicknames[1];
            }
            $this->_full_name = preg_replace(regex::nickname, '', $this->_full_name);
        }
    }

    private function handle_firstnames() {
       $count = $this->test();

        if ($this->title() && ($count == 2) && !in_array(lc($this->title()), $this->C['first_name_titles'])) {
            $last = $this->first;
            $this->first = $this->last;
            $this->last = $last;
        }
    }

    private function collapse_whitespace($string) {
        return preg_replace(regex::spaces, ' ', trim($string));
    }

    private function parse_pieces($parts, $additional_parts_count = 0) {
        $pieces = array();

        foreach ($parts as $part) {
            $items = array();
            foreach (explode(' ', $part) as $item) {
                $items[] = trim($item, ' ,');
            }
            $pieces = array_merge($pieces, $items);
        }

        foreach ($pieces as $part) {
            if (preg_match(regex::period_not_at_end, $part)) {
                $period_chunks = explode('.', $part);

                if (count(array_filter($period_chunks, array($this, 'is_title'))) > 0) {
                    $this->C['titles'][] = $part;
                    continue;
                }

                if (count(array_filter($period_chunks, array($this, 'is_suffix'))) > 0) {
                    $this->C['suffixes'][] = $part;
                    continue;
                }
            }
        }

        return $this->join_on_conjunctions($pieces, $additional_parts_count);
    }

    private function join_on_conjunctions($pieces, $additional_parts_count = 0) {
        if ((count($pieces) + $additional_parts_count) < 3) {
            return $pieces;
        }

        foreach (array_values(array_filter(array_reverse($pieces), array($this, 'is_conjunction'))) as $conj) {
            $rootname_pieces = array_values(array_filter($pieces, array($this, 'is_rootname')));
            $length = count($rootname_pieces) + $additional_parts_count;

            if ((strlen($conj) == 1) && ($length < 4)) {
                continue;
            }

            if (!$i = array_search($conj, $pieces)) {
                continue;
            }

            if ($i < (count($pieces) - 1)) {
                if ($i == 0) {
                    $nxt = $pieces[$i + 1];
                    $new_piece = implode(' ', slice($pieces, 0, 2));

                    if ($this->is_title($nxt)) {
                         $this->C['titles'][] = lc($new_piece);
                    } else {
                        $this->C['conjunctions'][] = lc($new_piece);
                    }

                    $pieces[$i] = $new_piece;
                    $pieces = pop($pieces, $i + 1);
                    continue;
                }

                if ($this->is_conjunction($pieces[$i - 1])) {
                    $new_piece = implode(' ', slice($pieces, $i, $i + 2));
                    $this->C['conjunctions'][] = lc($new_piece);

                    $pieces[$i] = $new_piece;
                    $pieces = pop($pieces, $i + 1);
                    continue;
                }

                $new_piece = implode(' ', slice($pieces, $i - 1, $i + 2));

                if ($this->is_title($pieces[$i - 1])) {
                    $this->C['titles'][] = lc($new_piece);
                }

                $pieces[$i - 1] = $new_piece;
                $pieces = pop($pieces, $i);
                $pieces = pop($pieces, $i);
            }
        }

        if ($prefixes = array_values(array_filter($pieces, array($this, 'is_prefix')))) {
            $i = array_search($prefixes[0], $pieces);
            if ($next_suffix = array_values(array_filter(slice($pieces, $i), array($this, 'is_suffix')))) {
                $j = array_search($next_suffix[0], $pieces);
                $new_piece = implode(' ', slice($pieces, $i, $j));
                $pieces = array_merge(
                    slice($pieces, 0, $i),
                    array($new_piece),
                    slice($pieces, $j)
                );
            } else {
                $new_piece = implode(' ', slice($pieces, 0, $i), array($new_piece));
            }
        }

        return $pieces;
    }

    private function is_title($value) {
        return in_array(lc($value), $this->C['titles']);
    }

    private function is_conjunction($piece) {
        return (in_array(lc($piece), $this->C['conjunctions']) && !$this->is_an_initial($piece));
    }

    private function is_prefix($piece) {
        return (in_array(lc($piece), $this->C['prefixes']) && !$this->is_an_initial($piece));
    }

    private function is_roman_numeral($value) {
        return preg_match(regex::roman_numeral, $value);
    }

    private function is_suffix($piece) {
        $_lc = lc($piece);
        $_lcr = str_replace('.', '', $_lc);
        return (in_array($_lcr, $this->C['suffix_acronyms']) || in_array($_lc, $this->C['suffix_not_acronyms']) && !$this->is_an_initial($piece));
    }

    private function are_suffixes($pieces) {
        foreach ($pieces as $piece) {
            if (!$this->is_suffix($piece)) {
                return false;
            }
        }
        return true;
    }

    private function is_rootname($piece) {
        return (!in_array(lc($piece), $this->C['suffixes_prefixes_titles']) && !$this->is_an_initial($piece));
    }

    private function is_an_initial($value) {
        return preg_match(regex::initial, $value);
    }
}
?>
