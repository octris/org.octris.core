<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl\Compiler;

/**
 * Library for handling template constants.
 *
 * @copyright   copyright (c) 2010-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Constant
{
    /**
     * Constant registry.
     *
     * @type    array
     */
    protected static $registry = array(
        // pre-defined constants for bool type
        'TRUE'     => true,
        'FALSE'    => false,

        // pre-defined constants for escaping
        'ESC_NONE' => '',
        'ESC_ATTR' => 'attr',
        'ESC_CSS'  => 'css',
        'ESC_JS'   => 'js',
        'ESC_URI'  => 'uri',

        // pre-defined constants for json_encode/json_decode
        'JSON_HEX_QUOT'          => JSON_HEX_QUOT,
        'JSON_HEX_TAG'           => JSON_HEX_TAG,
        'JSON_HEX_AMP'           => JSON_HEX_AMP,
        'JSON_HEX_APOS'          => JSON_HEX_APOS,
        'JSON_NUMERIC_CHECK'     => JSON_NUMERIC_CHECK,
        'JSON_BIGINT_AS_STRING'  => JSON_BIGINT_AS_STRING,
        'JSON_PRETTY_PRINT'      => JSON_PRETTY_PRINT,
        'JSON_UNESCAPED_SLASHES' => JSON_UNESCAPED_SLASHES,
        'JSON_FORCE_OBJECT'      => JSON_FORCE_OBJECT,
        'JSON_UNESCAPED_UNICODE' => JSON_UNESCAPED_UNICODE,
        'JSON_BIGINT_AS_STRING'  => JSON_BIGINT_AS_STRING,

        // pre-defined constants for string functions
        'CASE_UPPER'             => \Octris\Core\Type\Text::CASE_UPPER,
        'CASE_LOWER'             => \Octris\Core\Type\Text::CASE_LOWER,
        'CASE_TITLE'             => \Octris\Core\Type\Text::CASE_TITLE,
        'CASE_UPPER_FIRST'       => \Octris\Core\Type\Text::CASE_UPPER_FIRST,
        'CASE_LOWER_FIRST'       => \Octris\Core\Type\Text::CASE_LOWER_FIRST,
    );

    /**
     * Last occured error.
     *
     * @type    string
     */
    protected static $last_error = '';

    /**
     * Constructor and clone magic method are protected to prevent instantiating of class.
     */
    protected function __construct()
    {
    }
    protected function __clone()
    {
    }

    /**
     * Return last occured error.
     *
     * @return  string                  Last occured error.
     */
    public static function getError()
    {
        return self::$last_error;
    }

    /**
     * Set error.
     *
     * @param   string      $name       Name of constant the error occured for.
     * @param   string      $msg        Additional error message.
     */
    protected static function setError($name, $msg)
    {
        self::$last_error = sprintf('"%s" -- %s', $name, $msg);
    }

    /**
     * Set a constant.
     *
     * @param   string      $name       Name of constant to set.
     * @param   mixed       $value      Value of constant.
     */
    public static function setConstant($name, $value)
    {
        $name = strtoupper($name);

        if (isset(self::$registry[$name])) {
            throw new \Exception("constant '$name' is already defined");
        } else {
            self::$registry[$name] = $value;
        }
    }

    /**
     * Set multiple constants.
     *
     * @param   array       $constants  Key/value array defining constants.
     */
    public static function setConstants(array $constants)
    {
        foreach ($constants as $name => $value) {
            $this->setConstant($name, $value);
        }
    }

    /**
     * Return value of a constant. An error will be set if the requested constant is not defined.
     *
     * @param   string      $name       Name of constant to return value of.
     * @return  mixed                   Value of constant.
     */
    public static function getConstant($name)
    {
        self::$last_error = '';

        $name = strtoupper($name);

        if (!isset(self::$registry[$name])) {
            self::setError($name, 'unknown constant');
        } else {
            return self::$registry[$name];
        }
    }
}
