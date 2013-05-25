<?php

/**
 * Tokenizer
 *
 * @author Alexander Tesminetskiy <tesav@yandex.ru>
 */
class Tokenizer {

    /**
     * @var array contains the result of processing.
     */
    public $result = array();

    /**
     * @var bool format.
     */
    public $inLine = true;

    /**
     * @var string processed row.
     */
    protected $_string = '';

    /**
     * @var int length of string.
     */
    protected $_strLen = null;

    /**
     * @var int character position in the string.
     */
    protected $_charNum = null;

    /**
     * @var array an array of characters.
     */
    protected $_chars = array(
        "\r" => '\r',
        "\n" => '\n',
        ' ' => 'WHITESPACE',
        ';' => 'CHARACTER ;',
        '$' => 'VARIABLE',
        '*' => array('MULTIPLLI', array(
                '*/' => 'COMMENT_ERROR',
        )),
        '=' => array('ASSIGNMENT', array(
                '===' => 'IDENTICALLY',
                '==' => 'EQUAL',
                '=>' => '<-KEY | VALUE->',
        )),
        '-' => array('DIFFERENCE', array(
                '->' => 'OBJECT_POINTER',
                '--' => 'DECREMENT',
        )),
        '+' => array('SUM', array(
                '++' => 'INCREMENT',
        )),
        '>' => array('MORE', array(
                '>=' => 'GREATER_OR_EQUAL',
        )),
        '<' => array('LESS', array(
                '<=' => 'LESS_OR_EQUAL',
                '<?' => '_open',
        )),
        '?' => array('', array(
                '?>' => '_close',
        )),
        '/' => array('DIVIDE', array(
                '/*' => '_comment',
                '//' => '_comment',
        )),
        '#' => array(false, array(
                '#' => '_comment',
        )),
        '"' => array('STRING_CONSTANT', array(
                '"' => '_stringConstant',
        )),
        "'" => array('STRING_CONSTANT', array(
                "'" => '_stringConstant',
        )),
    );

    /**
     * @var int line number.
     */
    private $_lineNum = 1;

    /**
     * @var bool key opening tag.
     */
    private $_open = false;

    /**
     * Tokenizer Constructor.
     * @param string $file path/to/file.php
     * @param bool $inLine
     */
    public function __construct($file, $inLine = true) {

        !$file and die('There is no file name !');
        file_exists($file) or die('File not found !');
        !$this->_string = file_get_contents($file) and die('File empty !');
        $this->inLine = (bool) $inLine;
        $this->_process();
    }

    /**
     * Sign of an opening tag.
     * @return string
     */
    protected function _open() {

        if ($this->_open) return 'ERROR REPEAT OPEN_TAG';

        if ($this->_next('=')) {
            $this->_open = true;
            $this->_charNum++;
            return 'OPEN_TAG &lt;? ECHO ';
        }

        $_chars = array("\r", "\n", ' ');

        if ($this->_next($_chars)) {
            $this->_open = true;
            return 'OPEN_TAG &lt;?';
        }

        if ($this->_is('?php') and $this->_next($_chars)) {
            $this->_open = true;
            return 'OPEN_TAG &lt;?php';
        }
        return 'ERROR OPEN_TAG';
    }

    /**
     * A sign of the end tag.
     * @return string
     */
    protected function _close() {

        if (!$this->_open) return 'ERROR CLOSE_TAG';
        $this->_open = false;
        return 'CLOSE_TAG ?&gt;';
    }

    /**
     * Returns a string constant.
     * @return string
     */
    protected function _stringConstant() {

        $char = $this->_char();
        if ($str = $this->_strTo($char))
                return $this->_chars[$char][0] . ' ' . $str . $char;
        return $this->_chars[$char][0] . ' ERROR ' . $this->_getAll();
    }

    /**
     * Returns a string comments.
     * @return string
     */
    protected function _comment() {

        if ($this->_char() != '*') return 'COMMENT ' . $this->_strTo("\n");
        $str = $this->_strTo('*/');
        return $str ? 'COMMENT ' . $str : 'COMMENT_ERROR ' . $this->_getAll();
    }

    /**
     * Insensitive comparison of a character
     * or characters, with the next.
     *
     * @param string|array $char
     * @return string|bool false
     */
    protected function _next($char) {
        return $this->_check($char, 1);
    }

    /**
     * Insensitive comparison of a character
     * or characters, with the previous.
     *
     * @param string|array $char
     * @return string|bool false
     */
    protected function _prev($char) {
        return $this->_check($char, -1);
    }

    /**
     * Returns the rest of the line.
     * @return string
     */
    protected function _getAll() {

        $str = substr($this->_string, $this->_charNum);
        $this->_charNum = $this->_strLen;
        return $str;
    }

    /**
     * Takes a character or a string.
     *
     * searches for the first occurrence of the current position.
     * If found, returns the substring from the current position
     * to the position of occurrence.
     * Establishes a new position.
     *
     * Otherwise, it returns false.
     *
     * @param string $str
     * @return string|bool false
     */
    protected function _strTo($str) {

        if (strlen($str) > 1) return $this->_strToString($str);
        return $this->_strToChar($str);
    }

    /**
     * Looking for a substring from the current position
     * compares insensitive.
     * if successful, returns a boolean true,
     * establishes a new position.
     *
     * @param string $str
     * @return bool
     */
    protected function _is($str) {

        $len = strlen($str);

        if (strcasecmp(substr($this->_string, $this->_charNum, $len), $str) != null)
                return false;

        $this->_charNum += $len - 1;
        return true;
    }

    /**
     * Puts the result into an the resulting array.
     * @param string $str
     */
    protected function _res($str) {
        $this->inLine ?
                        $this->result[$this->_lineNum] .= $str . ' ' :
                        $this->result[] .= $str . ' ';
    }

    /**
     * Returns the current character
     * @return string
     */
    protected function _char() {
        return $this->_string{$this->_charNum};
    }

    /**
     * Parses a string character by character.
     */
    private function _process() {

        $str = '';

        $this->_strLen = strlen($this->_string);

        for (; $this->_strLen >= $this->_charNum; $this->_charNum++) {

            $char = $this->_char();

            if (!($this->_open or $char == '<' and $this->_next('?'))) continue;

            if (preg_match('/\\w/', $char)) {
                $str .= $char;
                continue;
            }

            if ($str) {
                $this->_res(strtoupper($str)/* . ' ' . $str */);
                $str = '';
            }

            if (empty($this->_chars[$char])) continue;

            if (!is_array($this->_chars[$char])) {
                $this->_res($this->_chars[$char]);
                $char == "\n" and $this->_lineNum++;
                continue;
            }

            $res = '';

            foreach ($this->_chars[$char][1] as $key => $value) {

                if ($this->_is($key)) {
                    if ($value{0} == '_') {
                        $res = $this->$value();
                        break; // break foreach
                    }

                    $res = $value;
                    break; // break foreach
                }
            } // end foreach
            $this->_res($res ? $res : $this->_chars[$char][0]);
        } // end for
    }

    /**
     * Performs a case-insensitive search for a character
     * in a string from the current position.
     *
     * If successful, returns a substring
     * and establishes a new position.
     *
     * @param string $char
     * @return string|bool false
     */
    private function _strToChar($char) {

        $str = '';

        $num = $this->_charNum;

        while ($this->_strLen > $num) {
            $str .= $this->_string{$num++};
            if (strcasecmp($this->_string{$num}, $char) != null) continue;
            $this->_charNum = $num;
            return $str;
        }
        return false;
    }

    /**
     * Searches for a substring in a string
     * from the current position.
     *
     * If successful, returns a substring
     * and establishes a new position.
     *
     * @param string $str
     * @return string|bool false
     */
    private function _strToString($str) {

        $num = $this->_charNum;
        if ($_str = $this->_strToStr($str)) return $_str;
        $this->_charNum = $num;
        return false;
    }

    /**
     * A recursive function.
     *
     * Searches for a substring in a string
     * from the current position.
     *
     * @param string $str
     * @return string|bool false
     */
    private function _strToStr($str) {

        $len = strlen($str) - 1;

        if (!$_str = $this->_strToChar($str{$len})) return false;

        $this->_charNum -= $len;

        if ($this->_is(substr($str, 0, $len))) {
            $this->_charNum += $len;
            return $_str;
        }
        $this->_charNum += $len;
        return $this->_strToStr($str);
    }

    /**
     * Insensitive comparison of a character
     * or characters
     *
     * @param string|array $char
     * @param int $num
     * @return bool|string char
     */
    private function _check($char, $num) {

        $_char = $this->_string{$this->_charNum + $num};

        // Could be easier,
        // but I wanted to try to apply the closure
        $cmp = function ($char)use($_char) {
                    if (strcasecmp($_char, $char) == null) return true;
                    return false;
                };

        if (is_array($char)) {
            foreach ($char as $value)
                if ($cmp($value)) return $_char;
            return false;
        }

        if ($cmp($char)) return $_char;
        return false;
    }

}