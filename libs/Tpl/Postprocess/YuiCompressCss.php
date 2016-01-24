<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl\Postprocess;

/**
 * Try to combine multiple css source files into a single file.
 * Compressing is done afterwards using yuicompressor.
 *
 * @copyright   copyright (c) 2010-2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class YuiCompressCss extends CombineCss
{
    /**
     * File extension of created file.
     *
     * @type    string
     */
    protected $ext = 'css';

    /**
     * Pattern to match.
     *
     * @type    string
     */
    protected $pattern = '<link[^>]*? href="(?!https?://)([^"]+\.css)"[^>]*/>';

    /**
     * Snippet to replace pattern with.
     *
     * @type    string
     */
    protected $snippet = '<link rel="stylesheet" href="/styles/%s" type="text/css" />';

    /**
     * Optional additional options for yui compressor.
     *
     * @type    array
     */
    protected $options = array('type' => 'css');

    /**
     * Constructor.
     *
     * @param   array       $mappings   Array of path-prefix to real-path mappings.
     * @param   string      $dst        Destination directory for created files.
     * @param   string      $yuic_path  Path to yui compressor.
     * @param   array       $options    Optional additional options for yui compressor.
     */
    public function __construct(array $mappings, $dst, $yuic_path, array $options = array())
    {
        parent::__construct($mappings, $dst, $yuic_path, $options);
    }
}