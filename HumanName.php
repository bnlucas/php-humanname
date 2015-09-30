<?php
require_once('./utils.php');

require_once('./constants/capitalization.php');
require_once('./constants/conjunctions.php');
require_once('./constants/prefixes.php');
require_once('./constants/suffixes.php');
require_once('./constants/titles.php');

/**
 * HumanName parses a supplied name, e.g. Mr. Brian Nathan (Nate) Lucas
 * into it's components [title, first, middle, last, suffix, nicknames]
 *
 * @author      Nathan Lucas <nathan@bnlucas.com>
 * @copyright   2015 Nathan Lucas
 * @link        http://github.com/bnlucas/php-humanname
 * @license     http://githut.com/bnlucas/php-humanname
 * @version     1.0.0
 * @package     HumanName
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
class HumanName {

    /**
     * Original supplied name.
     * 
     * @access private
     * @var string
     */
    private $full_name = '';

    /**
     * Supplied name with additional spaces and nickname removed.
     * 
     * @access private
     * @var string
     */
    private $_full_name = '';

    /**
     * List of title pieces.
     * 
     * @access private
     * @var array
     */
    private $title_list = array();

    /**
     * List of first name pieces.
     * 
     * @access private
     * @var array
     */
    private $first_list = array();

    /**
     * List of middle name pieces.
     * 
     * @access private
     * @var array
     */
    private $middle_list = array();

    /**
     * List of last name pieces.
     * 
     * @access private
     * @var array
     */
    private $last_list = array();

    /**
     * List of suffix pieces.
     * 
     * @access private
     * @var array
     */
    private $suffix_list = array();

    /**
     * List of namename pieces.
     * 
     * @access private
     * @var array
     */
    private $nickname_list = array();

    /**
     * Boolean if supplied name is parsable or not.
     * 
     * @access private
     * @var bool
     */
    private $unparsable = true;

    /**
     * Collection of all pre-defined lists used in various checks.
     * 
     * @access private
     * @var array
     */
    private $C = array();

    /**
     * HumanName constructor. Loads pre-defined lists held in HumanName::C
     * and runs HumanName::parse_full_name() on supplied $full_name.
     * 
     * @access public
     * @param string $full_name
     * @return HumanName
     */
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

    /**
     * Returns full name supplied prior to parsing.
     * 
     * @access public
     * @return string
     */
    public function full_name() {
        return trim($this->full_name);
    }

    /**
     * The person's titles. Consecutive pieces in HumanName::C['titles']
     * or HumanName::C['conjunctions']
     * 
     * @access public
     * @return string
     */
    public function title() {
        return trim(implode(' ', $this->title_list));
    }

    /**
     * The person's first name. The piece after any known HumanName::title()
     * Includes conjunctions such as `John and Jane` or `John & Jane`
     * 
     * @todo currently, conjoined names such as `John and Jane` parse correctly,
     * however `John Bob and Jane Ann` will parse:
     * >>> HumanName::first() = John
     * >>> HumanName::middle() = Bob and Jane Ann
     * 
     * @access public
     * @return string
     */
    public function first() {
        return trim(implode(' ', $this->first_list));
    }

    /**
     * The person's middle name(s). These are the pieces between first name
     * and last name.
     * 
     * @todo currently, conjoined names such as `John and Jane` parse correctly,
     * however `John Bob and Jane Ann` will parse:
     * >>> HumanName::first() = John
     * >>> HumanName::middle() = Bob and Jane Ann
     * 
     * @access public
     * @return string
     */
    public function middle() {
        return trim(implode(' ', $this->middle_list));
    }

    /**
     * The person's last name. Last name piece parsed.
     * 
     * @access public
     * @return string
     */
    public function last() {
        return trim(implode(' ', $this->last_list));
    }

    /**
     * The person's suffixes. Pieces at the end of the name that are found
     * in HumanName::C['suffixes'] or pieces found at the end of a comma separated
     * formats, e.g. `Lastname, Title Firstname Middle[,] Suffix [, Suffix]
     * 
     * @access public
     * @return string
     */
    public function suffix() {
        return trim(implode(' ', $this->suffix_list));
    }

    /**
     * The person's nickname. These are parsed as `(nickname)`, `'nickname'`,
     * `"nickname"`.
     * 
     * @access public
     * @return string
     */
    public function nickname() {
        return trim(implode(' ', $this->nickname_list));
    }

    /**
     * Returns if the name was parsable or not.
     * 
     * @access public
     * @return bool
     */
    public function is_parseable() {
        if ($this->unparsable) {
            return false;
        }
        return true;
    }

    /**
     * Returns delimited string for use in CSV files.
     * 
     * @access public
     * @param string $delimiter
     * @param array $additional_data
     * @return string
     */
    public function delimited($delimiter = ',', $addition_data = array()) {
        if ($this->unparsable) {
            return $this->full_name;
        }
        return trim(implode($delimiter, array_merge(array(
            $this->title(),
            $this->first(),
            $this->middle(),
            $this->last(),
            $this->suffix(),
            $this->nickname()
        ), $addition_data)));
    }

    /**
     * Used in determining if the name was parsed correctly.
     * 
     * @access public
     * @return int
     */
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

    /**
     * Name parser ran when HumanName::$full_name is set on instantiation. Basic flow
     * is to hand off to HumanName::pre_process() to remove nicknames. It is then split
     * on commas and chooses a code path depending on number of commas. These pieces then
     * run through HumanName::parse_pieces() to split those parts on spaces and joins
     * any needed with HumanName::join_on_conjunctions().
     * 
     * @access private
     * @return voic
     */
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
                $pieces = $this->parse_pieces(explode(' ', $parts[1]), 1);
                $lastname_pieces = $this->parse_pieces(explode(' ', $parts[0]), 1);

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
            $this->unparsable = false;
        }

        $this->post_process();
    }

    /**
     * Methods ran prior to parsing pieces.
     * 
     * @access private
     * @return voice
     */
    private function pre_process() {
        $this->parse_nicknames();
    }

    /**
     * Runs HumanName::handle_firstnames() for names such as Mr. Johnson
     * and checks if last element of HumanName::middle_list is the same as
     * the first element of HumanName::first_list as well as the last element
     * of HumanName::last_list and first element of HumanName::first_list.
     * 
     * These checks were added in response to an issue of checking email addresses
     * that are not formatted properly and contain a space before `@`. If either
     * of the checks fail, the name is flagged unparsable.
     * 
     * @access private
     * @return void
     */
    private function post_process() {
        $this->handle_firstnames();

        if ($this->first_list[0] == end($this->middle_list)) {
            $this->unparsable = true;
        }

        if ($this->first_list[0] == end($this->last_list)) {
            $this->unparsable = true;
        }
    }

    /**
     * Parsed out nicknames and sets HumanName::nickname_list
     * 
     * - (nickname)
     * - 'nickname'
     * - "nickname"
     * 
     * @access private
     * @return void
     */
    private function parse_nicknames() {
        if (preg_match(regex::nickname, $this->_full_name)) {
            if (preg_match_all(regex::nickname, $this->_full_name, $nicknames)) {
                $this->nickname_list = $nicknames[1];
            }
            $this->_full_name = preg_replace(regex::nickname, '', $this->_full_name);
        }
    }

    /**
     * If there's only two parts and one is a title, assumes title is a last name
     * instead of a firstname.
     * 
     * Without this check, Mr. Johnson is parsed:
     * >>> HumanName::title() = Mr.
     * >>> HumanName::first() = Johnson
     * 
     * With this check, Mr. Johnson is parsed:
     * >>> HumanName::title() = Mr.
     * >>> HumanName::last() = Johnson
     * 
     * In certain circumstances, the use of a special title, e.g Sir Johnson, John
     * would be set as first name, not last.
     * 
     * @access private
     * @return void
     */
    private function handle_firstnames() {
       $count = $this->test();

        if ($this->title() && ($count == 2) && !in_array(lc($this->title()), $this->C['first_name_titles'])) {
            $temp = $this->first_list;
            $this->first_list = $this->last_list;
            $this->last_list = $temp;
        }
    }

    /**
     * Removes double spacing found in $string
     * 
     * @access private
     * @param string $string
     * @return string
     */
    private function collapse_whitespace($string) {
        return preg_replace(regex::spaces, ' ', trim($string));
    }

    /**
    * Split parts on spaces and removes commas, joins on conjunctions `John and Jane`
    * and last name prefixes `de la Cruz`. If parts have a period in middle, tries
    * splitting on these and checks if the parts are titles or suffixes. If they are,
    * adds them to the HumanName::C lists so they will be found.
    * 
    * $additional_parts_count is used for when the comma format contains other parts,
    * so we know how many there are to decide if things should be considered a
    * conjunction.
    * 
    * @access private
    * @param array $parts
    * @param int $additional_parts_count
    * @return array
    */
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

    /**
     * Join conjunctions to surrounding pieces.
     * 
     * - Mr. and Mrs.
     * - King of the Hill
     * - Jack & Jill
     * - Velasquez y Garcia
     * 
     * Returns new array of pieces with the conjunctions merged into one piece
     * with spaces between.
     * 
     * $additional_parts_count is used for when the comma format contains other parts,
     * so we know how many there are to decide if things should be considered a
     * conjunction.
     * 
     * @access private
     * @param array $pieces
     * @param int $additional_parts_count
     * @return array
     */
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
                $new_piece = implode(' ', slice($pieces, $i));
                $pieces = array_merge(slice($pieces, 0, $i), array($new_piece));
            }
        }

        return $pieces;
    }

    /**
     * Checks if $piece is in list of HumanName::C['titles']
     * 
     * @access private
     * @param string $piece
     * @return bool
     */
    private function is_title($piece) {
        return in_array(lc($piece), $this->C['titles']);
    }

    /**
     * Checks if $piece is on of the defined conjunctions in
     * HumanName::C['conjunctions'] and not an initial.
     * 
     * @access private
     * @param string $piece
     * @return bool
     */
    private function is_conjunction($piece) {
        return (in_array(lc($piece), $this->C['conjunctions']) && !$this->is_an_initial($piece));
    }

    /**
     * Checks if $piece is in HumanName::C['prexies'] and not an initial.
     * 
     * @access private
     * @param string $piece
     * @return bool
     */
    private function is_prefix($piece) {
        $_piece = str_replace('.', '', lc($piece));
        return (in_array($_piece, $this->C['prefixes']) && !$this->is_an_initial($piece));
    }

    /**
     * Checks if $piece is a roman numeral.
     * 
     * @access private
     * @param string $piece
     * @return bool
     */
    private function is_roman_numeral($piece) {
        return preg_match(regex::roman_numeral, $piece);
    }

    /**
     * Checks if $piece is in HumanName::C['suffix_acronyms'] and
     * HumanName::C['suffix_not_acronyms'] and is not an initial. Also
     * strips `.` to ensure proper matching in lists.
     * 
     * @access private
     * @param string $piece
     * @return bool
     */
    private function is_suffix($piece) {
        $_piece = str_replace('.', '', lc($piece));
        return (in_array($_piece, $this->C['suffixes']) && !$this->is_an_initial($piece));
    }

    /**
     * Checks an array for HumanName::is_suffix($piece)
     * 
     * @access private
     * @param array $pieces
     * @return bool
     */
    private function are_suffixes($pieces) {
        foreach ($pieces as $piece) {
            if (!$this->is_suffix($piece)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if $piece is not in HumanName::C['suffixes_prefixes_titles'] and
     * not an initial.
     * 
     * @access private
     * @param string $piece
     * @return bool
     */
    private function is_rootname($piece) {
        return (!in_array(lc($piece), $this->C['suffixes_prefixes_titles']) && !$this->is_an_initial($piece));
    }

    /**
     * Checks in $piece is an initial. `A.`
     * 
     * @access private
     * @param string $piece
     * @return bool
     */
    private function is_an_initial($piece) {
        return preg_match(regex::initial, $piece);
    }
}
?>
