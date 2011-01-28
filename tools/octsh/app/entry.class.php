<?php

namespace org\octris\core\octsh\app {
    /**
     * Entry page.
     *
     * @octdoc      c:app/entry
     * @copyright   copyright (c) 2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class entry extends \org\octris\core\app\cli\page
    /**/
    {
        /**
         * The entry points to which the current page should allow requests to have to be defined with this
         * property.
         *
         * @octdoc  v:entry/$next_page
         * @var     array
         */
         protected $next_pages = array(
             'default' => '\org\octris\core\octsh\app\shell',
         );
        /**/

        /**
         * Prepare page
         *
         * @octdoc  m:entry/prepare
         * @param   \org\octris\core\app\page       $last_page      Instance of last called page.
         * @param   string                          $action         Action that led to current page.
         * @return  mixed                                           Returns either page to redirect to or null.
         */
        public function prepare(\org\octris\core\app\web\page $last_page, $action)
        /**/
        {
        }

        /**
         * Abstract method definition
         *
         * @octdoc  m:entry/render
         * @abstract   
         */
        public function render()
        /**/
        {
            die('error!');
        }
    }
}
