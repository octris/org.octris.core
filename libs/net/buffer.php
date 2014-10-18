<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace octris\core\net {
    /**
     * Helper class for temporarly storing request output data.
     *
     * @octdoc      c:net/buffer
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class buffer extends \octris\core\fs\file
    /**/
    {
        /**
         * Constructor.
         *
         * @octdoc  m:buffer/__construct
         */
        public function __construct()
        /**/
        {
            parent::__construct(
                'php://memory', 
                'w', 
                parent::T_READ_TRIM_NEWLINE | parent::T_STREAM_ITERATOR
            );
        }
    }
}