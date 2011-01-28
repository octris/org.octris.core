<?php

namespace {{$namespace}}\{{$module}}\app {
    /**
     * Entry page.
     *
     * @octdoc      c:app/index
     * @copyright   copyright (c) {{$year}} by {{$company}}
     * @author      {{$author}} <{{$email}}>
     */
    class index extends \org\octris\core\app\cli\page
    /**/
    {
        /**
         * The entry points to which the current page should allow requests to have to be defined with this
         * property.
         *
         * @octdoc  v:index/$next_page
         * @var     array
         */
         protected $next_pages = array(
             'default' => '\{{$namespace}}\{{$module}}\app\index',
         );
        /**/

        /**
         * The constructor is used to setup common settings for example validation rulesets must be defined 
         * through the page object constructor.
         *
         * @octdoc  m:index/__construct
         */
        public function __construct() 
        /**/
        {
            parent::__construct();
        }

        /**
         * Prepare rendering of a page. this method is called _BEFORE_ rendering a page.
         *
         * @octdoc  m:index/prepare
         * @param   \org\octris\core\app\page       $last_page  Instance of the page that was active before this one
         * @return  null|\org\octris\core\app\page              A page can be returned.
         */
        public function prepare(\org\octris\core\app\page $last_page, $action)
        /**/
        {
        }

        /**
         * This method is used to populate a template with data and render it. This method should never be reached for
         * the entry page. Otherwise the application is propably broken.
         *
         * @octdoc  m:index/render
         */
        public function render()
        /**/
        {
        }
    }
}
