<?php

require_once('org.octris.core/app/test.class.php');

use \org\octris\core\app\test as test;
use \org\octris\core\validate as validate;

class schemaTest extends PHPUnit_Framework_TestCase {
    public function testSimple() {
        $data = array(
            'username' => 'harald@octris.org',           
            'password' => 'WynVakHoj5'                  // random password -- nice try ... ;-)
        );

        $schema = new validate\schema(
            array(
                'default' => array(                             // entry point, always required!
                    'type'          => validate::T_OBJECT,
                    'properties'    => array(
                        'username' => array(                    // field: username
                            'type'  => validate::T_PRINTABLE
                        ),
                        'password' => array(                    // field: password
                            'type'  => validate::T_PRINTABLE
                        )
                    )
                )
            )
        );
        
        $this->assertTrue($schema->validate($data));
    }
    
    public function testExtended() {
        $data = array(
            'username'  => 'harald@octris.org',           
            'password'  => 'dulCesh8',                  // another random password ...
            'password2' => 'dulCesh9',
            'websites'  => array(
                array(
                    'url'   => 'http://www.octris.org/',
                    'tyoe'  => 'Homepage'
                )
            )
        );

        $schema = new validate\schema(
            array(
                'default' => array(                         // default schema
                    'type'       => validate::T_OBJECT,
                    'properties' => array(
                        'username' => array(
                            'type'  => validate::T_PRINTABLE
                        ),
                        'password' => array(
                            'type'  => validate::T_PRINTABLE,
                        ),
                        'password2' => array(               // a second password field to compare to
                            'type'  => validate::T_PRINTABLE,
                            // 'type'      => validate::T_CALLBACK,
                            // 'options'   => array(
                            //     'callback' => function($value) {
                            //         return ($value === ...);
                            //     }
                            // ),
                            // 'required'  => true
                        ),
                        'websites' => array(
                            'type'      => validate::T_ARRAY,
                            'items'     => 'url',           // name of allowed sub-schema
                            'min_items' => 0,
                            'max_items' => 5
                        )
                    )
                ),
        
                'url' => array(                             // sub-schema: url
                    'type'       => validate::T_OBJECT,
                    'properties' => array(
                        'url'   => array(
                            'type'  => validate::T_PRINTABLE
                            // 'type' => validate::T_URL
                        ),
                        'title' => array(
                            'type' => validate::T_PRINTABLE
                        )
                    )
                )
            )
        );
        
        $this->assertTrue($schema->validate($data));
    }
    
    public function testList() {
        $data = array(
            'string1', 'string2'
        );

        $schema = new validate\schema(
            array(
                'default' => array(                         // default schema
                    'type'       => validate::T_ARRAY,
                    'min_items'  => 2,
                    'max_items'  => 2,
                    'items'      => array(
                        'type'      => validate::T_PRINTABLE
                    )
                )
            )
        );
        
        $this->assertTrue($schema->validate($data));
    }
    
    public function testKeyrename() {
        $data = array(
            '--convert' => true,
            0           => 'input-file',
            1           => 'output-file'
        );
        
        $schema = new validate\schema(
            array(
                'default' => array(
                    'type'       => validate::T_OBJECT,
                    'keyrename'  => array(
                        'input', 'output'
                    ),
                    'properties' => array(
                        '--convert' => array(
                            'type'  => validate::T_BOOL
                        ),
                        'input' => array(
                            'type'  => validate::T_PRINTABLE,
                        ),
                        'output' => array(
                            'type'  => validate::T_PRINTABLE,
                        )
                    )
                )
            )
        );
        
        $this->assertTrue($schema->validate($data));
    }
    
    public function testChain() {
        $data = array(
            'username' => 'harald'
        );

        $schema = new validate\schema(
            array(
                'default' => array(
                    'type'       => validate::T_OBJECT,
                    'properties' => array(
                        'username' => array(
                            'type'      => validate::T_CHAIN,
                            'options'   => array(
                                'chain' => array(
                                    array(
                                        'type'      => validate::T_PRINTABLE
                                    ),
                                    array(
                                        'type'      => validate::T_CALLBACK,
                                        'options'   => array(
                                            'callback'  => function($value) {
                                                return !in_array($value, array('admin', 'chef'));
                                            }
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
        
        $this->assertTrue($schema->validate($data));
    }
}
