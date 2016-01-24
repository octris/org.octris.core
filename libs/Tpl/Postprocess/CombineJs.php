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
 * Try to combine multiple javascript source files into a single file.
 *
 * @copyright   copyright (c) 2010-2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class CombineJs extends \Octris\Core\Tpl\Postprocess
{
    /**
     * File extension of created file.
     *
     * @type    string
     */
    protected $ext = 'js';

    /**
     * Pattern to match.
     *
     * @type    string
     */
    protected $pattern = '<script[^>]+src="(?!https?://)([^"]+)"[^>]*></script>';

    /**
     * Snippet to replace pattern with.
     *
     * @type    string
     */
    protected $snippet = '<script type="text/javascript" src="/libsjs/%s"></script>';

    /**
     * Destination directory for created files.
     *
     * @type    string
     */
    protected $dst;

    /**
     * Constructor.
     *
     * @param   array       $mappings   Array of path-prefix to real-path mappings.
     * @param   string      $dst        Destination directory for created files.
     */
    public function __construct(array $mappings, $dst)
    {
        parent::__construct();

        foreach ($mappings as $prefix => $mapping) {
            $this->addMapping($prefix, $mapping);
        }

        $this->dst = $dst;
    }

    /**
     * Process (combine) collected files.
     *
     * @param   array       $files      Files to combine.
     * @return  string                  Destination name.
     */
    public function processFiles(array $files)
    {
        $files = array_map(function ($file) {
            return escapeshellarg($file);
        }, $files);

        $tmp = tempnam('/tmp', 'oct');

        $cmd = sprintf(
            'cat %s > %s 2>&1',
            implode(' ', $files),
            $tmp
        );

        $ret = array();
        $ret_val = 0;
        exec($cmd, $ret, $ret_val);

        $md5  = md5_file($tmp);
        $name = $md5 . '.' . $this->ext;
        rename($tmp, $this->dst . '/' . $name);

        return $name;
    }

    /**
     * Postprocess a template. The method collects all blocks found using '$this->pattern' and extract
     * all included external files. The function makes sure that files are not included mutliple times. Patterns
     * found will be replaces with '$this->snippet'.
     *
     * @param   string      $tpl        Template to postprocess.
     * @return  string                  Postprocessed template.
     */
    public function postProcess($tpl)
    {
        $files  = array();
        $offset = 0;

        while (preg_match("#(?:$this->pattern"."([\n\r\s]*))+#si", $tpl, $m_block, PREG_OFFSET_CAPTURE, $offset)) {
            $compressed = '';

            if (preg_match_all("#$this->pattern#si", $m_block[0][0], $m_tag)) {
                // collect files to process
                $diff = array_diff($m_tag[1], $files);
                $files = array_merge($files, $diff);

                // process files
                $resolved = $this->resolvePaths($diff);
                $name = $this->processFiles($resolved);

                $compressed = sprintf($this->snippet, $name);
            }

            $compressed .= $m_block[2][0];

            $tpl = substr_replace($tpl, $compressed, $m_block[0][1], strlen($m_block[0][0]));
            $offset = $m_block[0][1] + strlen($compressed);
        };

        return $tpl;
    }
}
