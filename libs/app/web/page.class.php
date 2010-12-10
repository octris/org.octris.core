<?php

namespace org\octris\core\app\web {
    use \org\octris\core\app\web as app;
    use \org\octris\core\validate as validate;
    
    /****c* app/page
     * NAME
     *      page
     * FUNCTION
     *      page controller of the MVC framework
     * COPYRight
     *      copyright (c) 2006-2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    abstract class page {
        /****v* page/$next_pages
         * SYNOPSIS
         */
        protected $next_pages = array(
        );
        /*
         * FUNCTION
         *      next valid actions -> pages for current page
         ****
         */

        /****v* page/$errors
         * SYNOPSIS
         */
        protected $errors = array();
        /*
         * FUNCTION
         *      stored error messages occured during execution of the current page
         ****
         */

        /****v* page/$messages
         * SYNOPSIS
         */
        protected $messages = array();
        /*
         * FUNCTION
         *      stored messages collected during execution of the current page
         ****
         */

        /****m* page/$secure
         * SYNOPSIS
         */
        protected $secure = false;
        /*
         * FUNCTION
         *      whether the page should be delivered through https or not
         ****
         */

        /****m* page/__construct
         * SYNOPSIS
         */
        public function __construct()
        /*
         * FUNCTION
         *      constructor
         ****
         */
        {
        }

        /****m* page/__toString
         * SYNOPSIS
         */
        public function __toString()
        /*
         * FUNCTION
         *      magic method __toString returns name of page class
         ****
         */
        {
            return get_class($this);
        }

        /****m* page/isSecure
         * SYNOPSIS
         */
        public final function isSecure()
        /*
         * FUNCTION
         *      return, whether page has to be secured
         ****
         */
        {
            return $this->secure;
        }

        /****m* page/prepareRender, render
         * SYNOPSIS
         */
        abstract public function prepareRender(\org\octris\core\app $app, lima_page $last_page, $action);
        abstract public function render(\org\octris\core\app $app);
        /*
         * FUNCTION
         *      abstract methods must be defined in the application page classes
         ****
         */

        /****m* page/validate
         * SYNOPSIS
         */
        public function validate(\org\octris\core\app $app, $action)
        /*
         * FUNCTION
         *      apply a validation ruleset
         * INPUTS
         *      * $app (object) -- application object
         *      * $action (string) -- action
         * OUTPUTS
         *      (bool) -- returns false, if validation failed, otherwise true
         ****
         */
        {
            return validate::getInstance()->validate($this, $action);
        }

        /****m* page/getNextPage
         * SYNOPSIS
         */
        public function getNextPage(\org\octris\core\app $app)
        /*
         * FUNCTION
         *      get's next page from action and next_pages array of last page
         * INPUTS
         *      * $app (object) -- application object
         * OUTPUTS
         *      (object) -- instance of next page
         ****
         */
        {
            $next = $this;

            if (count($this->errors) <= 0) {
                $action = $this->getAction();

                if (is_array($this->next_pages) && isset($this->next_pages[$action])) {
                    // lookup next page from current page's next_page array
                    $class = $this->next_pages[$action];
                    $next  = new $class($app);
                } else {
                    // lookup next page from entry page's next_page array
                    $entry = new $entry_page($app);

                    if (is_array($entry->next_pages) && isset($entry->next_pages[$action])) {
                        $class = $entry->next_pages[$action];
                        $next  = new $class($app);
                    }
                }
            }

            return $next;
        }

        /****m* page/getValidationRuleset
         * SYNOPSIS
         */
        public function getValidationRuleset($action)
        /*
         * FUNCTION
         *      returns a validation ruleset for specified action
         * INPUTS
         *      * $action (string) -- name of action to return ruleset for
         * OUTPUTS
         *      (mixed) -- array of rules for specified action, returns false, if no ruleset is specified for action
         ****
         */
        {
            $return = false;

            if (isset($this->validate[$action])) {
                $return = $this->validate[$action];
            }

            return $return;
        }

        /****m* page/addError
         * SYNOPSIS
         */
        public function addError($err)
        /*
         * FUNCTION
         *      add error message for current page
         * INPUTS
         *      * $err (string) -- error message
         ****
         */
        {
            $this->errors[] = $err;
        }

        /****m* page/addMessage
         * SYNOPSIS
         */
        public function addMessage($msg)
        /*
         * FUNCTION
         *      add message for current page
         * INPUTS
         *      * $msg (string) -- message
         ****
         */
        {
            $this->messages[] = $msg;
        }

        /****m* page/countErrors
         * SYNOPSIS
         */
        public function countErrors()
        /*
         * FUNCTION
         *      return number of errors for current page
         ****
         */
        {
            return count($this->errors);
        }

        /****m* page/countMessages
         * SYNOPSIS
         */
        public function countMessages()
        /*
         * FUNCTION
         *      return number of messages for current page
         ****
         */
        {
            return count($this->messages);
        }

        /****m* page/getErrors
         * SYNOPSIS
         */
        public function getErrors()
        /*
         * FUNCTION
         *      return all errors
         ****
         */
        {
            return $this->errors;
        }

        /****m* page/getMessages
         * SYNOPSIS
         */
        public function getMessages()
        /*
         * FUNCTION
         *      return all messages
         ****
         */
        {
            return $this->messages;
        }

        /****m* page/addErrors
         * SYNOPSIS
         */
        public function addErrors(array $errors)
        /*
         * FUNCTION
         *      method to add multiple errors for page
         * INPUTS
         *      * $errors (array) -- array of error messages
         ****
         */
        {
            $this->errors = array_merge($this->errors, $errors);
        }

        /****m* page/addMessages
         * SYNOPSIS
         */
        public function addMessages(array $messages)
        /*
         * FUNCTION
         *      method to add multiple messages for page
         * INPUTS
         *      * $messages (array) -- array of messages 
         ****
         */
        {
            $this->messages = array_merge($this->messages, $messages);
        }

        /****m* page/getValidateRulesets
         * SYNOPSIS
         */
        public function getValidateRulesets()
        /*
         * FUNCTION
         *      return validate rulesets
         ****
         */
        {
            return lima_validate::getInstance()->export($this);
        }

        /****m* page/prepareMessages
         * SYNOPSIS
         */
        public function prepareMessages(\org\octris\core\app $app)
        /*
         * FUNCTION
         *      prepare messages for output page (eg error- or status messages)
         * INPUTS
         *      * $app (object) -- application object
         ****
         */
        {
            if (count($this->errors) > 0) {
                $app->setErrors($this->errors);
            }

            if (count($this->messages) > 0) {
                $app->setMessages($this->messages);
            }
        }
    }
}
