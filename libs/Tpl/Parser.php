<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl;

/**
 * Template parser base class.
 *
 * @copyright   copyright (c) 2012-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Parser implements \Iterator
{
    /**
     * Option flags.
     */
    const DEBUG           = 1;    // for switching on debug mode
    const IGNORE_COMMENTS = 2;    // for ignoring commands in comments

    /**
     * RegExp pattern for matching template snippets.
     *
     * @type    string
     */
    protected static $snippet_pattern = "\{\{((?:[^\"'{}]+|(?:\"(?:\\\\\"|[^\"])*\")|(?:\'(?:\\\\\'|[^\'])*\'))*)\}\}";

    /**
     * Names of parser tokens, generated by class constructor.
     *
     * @type    array|null
     */
    private static $tokennames = null;

    /**
     * Filename of template to parse.
     *
     * @type    string
     */
    protected $filename;

    /**
     * Template content to parse.
     *
     * @type    string
     */
    protected $tpl;

    /**
     * Whether debug-mode is enabled.
     *
     * @type    bool
     */
    protected $debug;

    /**
     * Whether to ignore commands inside of HTML comments.
     */
    protected $ignore_comments;

    /**
     * For calculating correct line number in template.
     *
     * @type    int
     */
    protected $line_correction = 0;

    /**
     * Current offset to start parsing from.
     *
     * @type    int
     */
    protected $offset = 0;

    /**
     * Offset to start parsing from in next iteration.
     *
     * @type    int
     */
    protected $next_offset = 0;

    /**
     * Whether parser is in a valid state. The parser is in a valid state, if the current parser iteration found something to work with.
     *
     * @type    bool
     */
    protected $valid = false;

    /**
     * Current parsed content.
     *
     * @type    array
     */
    protected $current = null;

    /**
     * Filter to call for every snippet returned.
     *
     * @type    callback
     */
    protected $filter;

    /**
     * Constructor.
     *
     * @param   string                  $filename                   Filename of template to load.
     * @param   int                     $flags                      Optional option flags to set.
     */
    public function __construct($filename, $flags = 0)
    {
        if (is_null(self::$tokennames)) {
            $class = new \ReflectionClass($this);
            self::$tokennames = array_flip($class->getConstants());
        }

        $this->filter = function ($command) {
            return $command;
        };

        $tpl = file_get_contents($filename);

        $this->tpl      = $this->prepare($tpl);
        $this->filename = $filename;

        // option flags
        $this->debug           = (($flags & self::DEBUG) === self::DEBUG);
        $this->ignore_comments = (($flags & self::IGNORE_COMMENTS) === self::IGNORE_COMMENTS);
    }

    /** Implementation of methods required for Iterator interface **/

    /**
     * Set offset to 0 to parse template again.
     */
    public function rewind()
    {
        $this->offset          = 0;
        $this->line_correction = 0;

        $this->next();
    }

    /**
     * Return current parsed command.
     *
     * @return  array                                               Array with template command and escaping.
     */
    public function current()
    {
        $filter = $this->filter;

        return $filter($this->current);
    }

    /**
     * Return current offset of parser.
     *
     * @return  int                                                 Offset.
     */
    public function key()
    {
        return $this->offset;
    }

    /**
     * This methods parses the template until a template command is reached. The template command is than evailable as iterator item.
     */
    public function next()
    {
        $this->offset = $this->next_offset;

        if (($this->valid = (preg_match('/(' . self::$snippet_pattern . ')/s', $this->tpl, $m, PREG_OFFSET_CAPTURE, $this->offset) > 0))) {
            $this->current = array(
                'snippet' => (isset($m[2]) ? $m[2][0] : ''),
                'escape'  => null,
                'line'    => $this->getLineNumber($m[2][1]),
                'length'  => strlen($m[0][0]),
                'offset'  => $m[1][1]
            );

            $this->next_offset = $m[1][1] + strlen($m[1][0]);
        } else {
            $this->current = null;
        }
    }

    /**
     * Returns whether parser is at a valid state.
     *
     * @Return  bool                                                Parser state is valid.
     */
    public function valid()
    {
        return $this->valid;
    }

    /** Implementation of helper methods for parser **/

    /**
     * Return number of line.
     *
     * @param   int                     $offset                     Offset to count lines up to.
     * @return  int                                                 Number of lines detected.
     */
    protected function getLineNumber($offset)
    {
        return substr_count(substr($this->tpl, 0, $offset), "\n") + 1 - $this->line_correction;
    }

    /**
     * Get number of total lines of template.
     *
     * @return  int                                                 Number of total lines.
     */
    public function getTotalLines()
    {
        return substr_count($this->tpl, "\n") + 1;
    }

    /**
     * Return template contents.
     *
     * @return  string                                              Template contents.
     */
    public function getTemplate()
    {
        return $this->tpl;
    }

    /**
     * Return name of specified token.
     *
     * @param   int                     $token                      Id of token to return name of.
     */
    protected function getTokenName($token)
    {
        return (isset(self::$tokennames[$token])
                ? self::$tokennames[$token]
                : 'T_UNKNOWN');
    }

    /**
     * Return names of multiple tokens.
     *
     * @param   array       $tokens     Array of token IDs.
     * @return  array                   Names of tokens.
     */
    protected function getTokenNames(array $tokens)
    {
        $return = array();

        foreach ($tokens as $token) {
            $return[] = $this->getTokenName($token);
        }

        return $return;
    }

    /**
     * Method to change the parser offset.
     *
     * @param   int         $offset     New offset to move parser to.
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * Filter to call before snippet is returned.
     *
     * @param   callable            $callback           Callback to call.
     * @return  mixed                                   Data to return.
     */
    public function setFilter(callable $callback)
    {
        $this->filter = $callback;
    }

    /**
     * Trigger an error and halt execution.
     *
     * @param   string      $type       Type of error to trigger.
     * @param   int         $cline      Line in parser class error was triggered from.
     * @param   int         $line       Line in template the error was triggered for.
     * @param   int         $token      ID of token that triggered the error.
     * @param   mixed       $payload    Optional additional information. Either an array of expected token IDs or an additional message to output.
     */
    protected function error($type, $cline, $line, $token, $payload = null)
    {
        printf("\n** ERROR: %s(%d) **\n", $type, $cline);
        printf("   line :    %d\n", $line);
        printf("   file :    %s\n", $this->filename);
        printf("   token:    %s\n", $this->getTokenName($token));

        if (is_array($payload)) {
            printf("   expected: %s\n", implode(', ', $this->getTokenNames(array_keys($payload))));
        } elseif (isset($payload)) {
            printf("   message:  %s\n", $payload);
        }

        die();
    }

    /**
     * Prepare template for parsing. Deactivate PHP code by replacing it as template snippet. This
     * allows for templatizing PHP files.
     *
     * @param   string      $tpl            Template content to prepare.
     */
    protected function prepare($tpl)
    {
        $pattern = '/(' . self::$snippet_pattern . '|<\?php|\?>)/s';
        $offset  = 0;

        while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE, $offset)) {
            if ($m[1][0] == '<?php' || $m[1][0] == '?>') {
                // de-activate php code by replacing tags with template snippets
                $rpl = '{{string("' . $m[1][0] . '")}}';
                $tpl = substr_replace($tpl, $rpl, $m[1][1], strlen($m[1][0]));
                $len = strlen($rpl);
            } else {
                $len = strlen($m[1][0]);
            }

            $offset = $m[1][1] + $len;
        }

        return $tpl;
    }

    /**
     * Replace part of a template with specified string.
     *
     * @param   string      $str            String to replace template snippet with.
     * @param   bool        $move_offset    Optional whether to move offset after replacing or set it to value of first parameter.
     */
    public function replaceSnippet($str, $move_offset = false)
    {
        $offset = $this->current['offset'];
        $length = $this->current['length'];

        // replace template snippet
        $this->line_correction += (substr_count($str, "\n") - substr_count($this->tpl, "\n", $offset, $length));

        $this->tpl = substr_replace($this->tpl, $str, $offset, $length);

        if ($move_offset) {
            // $this->setOffset($offset + strlen($str));
            $this->next_offset = $offset + strlen($str);
        } else {
            // $this->setOffset($offset);
            $this->next_offset = $offset;
        }
    }
}
