<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Db\Device\Mysql;

/**
 * MySQL prepared statement.
 *
 * @copyright   copyright (c) 2012-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Statement
{
    /**
     * Instance of mysqli_stmt class.
     *
     * @type    \mysqli_stmt
     */
    protected $instance;

    /**
     * Constructor.
     *
     * @param   \mysqli         $link               Database connection.
     * @param   string          $sql                SQL statement.
     */
    public function __construct(\mysqli $link, $sql)
    {
        $this->instance = new \mysqli_stmt($link, $sql);
    }

    /**
     * Magic getter.
     *
     * @param   string          $name               Name of property to return.
     * @return  mixed                               Value of property.
     */
    public function __get($name)
    {
        $return = null;

        switch ($name) {
            case 'affected_rows':
            case 'errno':
            case 'error_list':
            case 'error':
            case 'field_count':
            case 'insert_id':
            case 'num_rows':
            case 'param_count':
            case 'sqlstate':
                $return = $this->instance->{$name};
                break;
            default:
                throw new \InvalidArgumentException('Undefined property: ' . __CLASS__ . '::$' . $name);
        }

        return $return;
    }

    /**
     * Bind parameters to statement.
     *
     * @param   string          $types              String of type identifiers.
     * @param   array           $values             Array of values to bind.
     */
    public function bindParam($types, array $values)
    {
        if (preg_match('/[^idsb]/', $types)) {
            throw new \InvalidArgumentException('Unknown data type in "' . $types . '"');
        } elseif (($cnt2 = strlen($types)) != ($cnt1 = count($values))) {
            throw new \InvalidArgumentException(
                'Number of specified types (%d) and number of specified values (%d) does not match',
                $cnt2,
                $cnt1
            );
        } elseif ($cnt1 != ($cnt2 = $this->instance->param_count)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Number of specified parameters (%d) does not match required parameters (%d)',
                    $cnt1,
                    $cnt2
                )
            );
        } else {
            array_unshift($values, $types);

            $this->instance->bind_param(...$values);
        }
    }

    /**
     * Return metadata of result.
     *
     * @return  \mysqli_result|null
     */
    public function getResultMetadata()
    {
        return $this->instance->result_metadata();
    }

    /**
     * Execute the statement.
     *
     * @return  \Octris\Core\Db\Device\Mysql\Result|null|false              Instance of mysql result set or null if statement has no result or false in case of an error.
     */
    public function execute()
    {
        $this->instance->execute();

        if ($this->instance->errno > 0) {
            throw new \Exception($this->instance->error, $this->instance->errno);
        }

        if (!is_null($result = $this->instance->result_metadata())) {
            if (($result = $this->instance->get_result())) {
                $result = new \Octris\Core\Db\Device\Mysql\Result($result, $this);
            }
        }

        return $result;
    }
}
