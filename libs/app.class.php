<?php

namespace org\octris\core {
    /****c* core/app
     * NAME
     *      app
     * FUNCTION
     *      Core application class. This class needs to be the first
     *      one included in an application, because it set's up a function
     *      which will try to automatically load all required classes.
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    abstract class app {
        /****d* app/T_CONTEXT_UNDEFINED, T_CONTEXT_CLI, T_CONTEXT_WEB, T_CONTEXT_TEST
         * SYNOPSIS
         */
        const T_CONTEXT_UNDEFINED = 0;
        const T_CONTEXT_CLI       = 1;
        const T_CONTEXT_WEB       = 2;
        const T_CONTEXT_TEST      = 3;
        /*
         * FUNCTION
         *      context the application is running in
         ****
         */
        
        /****v* app/$instance
         * SYNOPSIS
         */
        private static $instance = null;
        /*
         * FUNCTION
         *      application instance
         ****
         */
        
        /****v* app/$context
         * SYNOPSIS
         */
        protected static $context = self::T_CONTEXT_UNDEFINED;
        /*
         * FUNCTION
         *      application context
         ****
         */
        
        /****m* app/__construct
         * SYNOPSIS
         */
        protected function __construct()
        /*
         * FUNCTION
         *      constructor
         ****
         */
        {
        }

        /****m* app/process
         * SYNOPSIS
         */
        abstract public function process();
        /*
         * FUNCTION
         *      methods to be implemented by application controller
         ****
         */
        
        /****m* app/autoload
         * SYNOPSIS
         */
        public static function autoload($classpath)
        /*
         * FUNCTION
         *      class autoloader
         * INPUTS
         *      * $classpath (string) -- path of class to load
         ****
         */
        {
            $pkg = preg_replace('|\\\\|', '/', preg_replace('|\\\\|', '.', ltrim($classpath, '\\\\'), 2)) . '.class.php';

            require_once($pkg);
        }
        
        /****m* app/triggerError
         * SYNOPSIS
         */
        public static function triggerError($code, $string, $file, $line, $context)
        /*
         * FUNCTION
         *      catches non OO errors and convert them to real exceptions
         * INPUTS
         *      * $code (int) -- error code
         *      * $string (string) -- the error message
         *      * $file (string) -- the name of the file the error was raised
         *      * $line (int) -- the line number in which the error was raised
         *      * $context (array) -- array of the active symbol table, when error was raised
         ****
         */
        {
            // TODO: implementation
        }
        
        /****m* app/getContext
         * SYNOPSIS
         */
        public static final function getContext()
        /*
         * FUNCTION
         *      Return context the application is running in.
         * OUTPUTS
         *      (int) -- application context
         ****
         */
        {
            return static::$context;
        }
        
        /****m* app/getInstance
         * SYNOPSIS
         */
        public static function getInstance()
        /*
         * FUNCTION
         *      return instance of main application class
         * OUTPUTS
         *      (app) -- instance of main application class
         ****
         */
        {
            if (is_null(self::$instance)) {
                self::$instance = new static();
            }
            
            return self::$instance;
        }
    }

    spl_autoload_register(array('\org\octris\core\app', 'autoload'));
    set_error_handler(array('\org\octris\core\app', 'triggerError'), E_ALL);
}

