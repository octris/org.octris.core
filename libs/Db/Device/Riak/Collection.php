<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Db\Device\Riak;

use \Octris\Core\Net\Client\Http as http;

/**
 * Riak database collection. Note, that a collection in Riak is called "Bucket" and so this
 * class operates on riak buckets.
 *
 * @copyright   copyright (c) 2012-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Collection
{
    /**
     * Device the collection belongs to.
     *
     * @type    \Octris\Core\Db\Device\Riak
     */
    protected $device;

    /**
     * Instance of connection class the collection is access by.
     *
     * @type    \Octris\Core\Db\Device\Riak\Connection
     */
    protected $connection;

    /**
     * Name of collection.
     *
     * @type    string
     */
    protected $name;

    /**
     * Constructor.
     *
     * @param   \Octris\Core\Db\Device\Riak             $device         Device the connection belongs to.
     * @param   \Octris\Core\Db\Device\Riak\Connection  $connection     Connection instance.
     * @param   string                                      $name           Name of collection.
     */
    public function __construct(\Octris\Core\Db\Device\Riak $device, \Octris\Core\Db\Device\Riak\Connection $connection, $name)
    {
        $this->device     = $device;
        $this->connection = $connection;
        $this->name       = $name;
    }

    /**
     * Return name of collection.
     *
     * @return  string                                          Name of collection.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Create an empty object for storing data into specified collection.
     *
     * @param   array                                           $data       Optional data to store in data object.
     * @return  \Octris\Core\Db\Device\Riak\DataObject                  Data object.
     */
    public function create(array $data = array())
    {
        $object = new \Octris\Core\Db\Device\Riak\DataObject(
            $this->device,
            $this->getName(),
            $data
        );

        $object->setContentType('application/json');

        return $object;
    }

    /**
     * Fetch the stored item of a specified key.
     *
     * @param   string          $key                                Key of item to fetch.
     * @return  \Octris\Core\Db\Device\Riak\DataObject|bool     Either a data object containing the found item or false if no item was found.
     */
    public function fetch($key)
    {
        $request = $this->connection->getRequest(
            http::T_GET,
            '/buckets/' . $this->name . '/keys/' . $key
        );
        $result = $request->execute();
        $status = $request->getStatus();

        if ($status == 404) {
            // object not found
            $return = false;
        } else {
            $result['_id'] = $key;

            // parse link references and populate data with references
            if (($links = $request->getResponseHeader('link')) !== false) {
                $count = 0;

                do {
                    $links = preg_replace_callback(
                        '|</buckets/(?P<bucket>[^/]+)/keys/(?P<key>[^/>]+)>; *riaktag="(?P<tag>[^"]+)"|',
                        function ($match) use (&$result) {
                            $result[$match['tag']] = new \Octris\Core\Db\Type\DbRef(
                                $match['bucket'],
                                $match['key']
                            );

                            return '';
                        },
                        $links,
                        -1,
                        $count
                    );
                } while ($count > 0);
            }

            // create dataobject
            $return = new \Octris\Core\Db\Device\Riak\DataObject(
                $this->device,
                $this->getName(),
                $result
            );
        }

        return $return;
    }

    /**
     * Query a Riak collection using Riak search interface.
     *
     * @param   array           $query                      Query conditions.
     * @param   int             $offset                     Optional offset to start query result from.
     * @param   int             $limit                      Optional limit of result items.
     * @param   array           $sort                       Optional sorting parameters.
     * @param   array           $fields                     Optional fields to return.
     * @param   array           $hint                       Optional query hint.
     * @return  \Octris\Core\Db\Device\Riak\Result      Result object.
     *
     * @ref     http://docs.basho.com/riak/latest/cookbooks/Riak-Search---Indexing-and-Querying-Riak-KV-Data/
     */
    public function query(array $query, $offset = 0, $limit = 20)
    {
        if (count($query) == 0) {
            // TODO: list total bucket contents
            return false;
        }

        $q = array();
        foreach ($query as $k => $v) {
            $q[] = $k . ':' . $v;
        }

        $request = $this->connection->getRequest(
            http::T_GET,
            '/solr/' . $this->name . '/select',
            array(
                'q'     => implode(' AND ', $q),
                'start' => $offset,
                'rows'  => $limit,
                'wt'    => 'json'
            )
        );

        $result = $request->execute();
        $status = $request->getStatus();

        return new \Octris\Core\Db\Device\Riak\Result(
            $this->device,
            $this->getName(),
            $result
        );
    }

    /**
     * Add a link reference header to a request.
     *
     * @param   \Octris\Core\Db\Riak\Request            $request    Request object.
     * @param   \Octris\Core\Db\Device\Riak\DataObject  $object     Data object to collect references from.
     */
    protected function addReferences(\Octris\Core\Db\Device\Riak\Request $request, \Octris\Core\Db\Device\Riak\DataObject $object)
    {
        $iterator = new \RecursiveIteratorIterator(new \Octris\Core\Db\Type\RecursiveDataIterator($object));

        foreach ($iterator as $name => $value) {
            if ($value instanceof \Octris\Core\Db\Type\DbRef) {
                $request->addHeader(
                    'Link',
                    sprintf(
                        '</buckets/%s/keys/%s>; riaktag="%s"',
                        urlencode($value->collection),
                        urlencode($value->key),
                        urlencode($name)
                    )
                );
            }
        }
    }

    /**
     * Insert an object into a database collection.
     *
     * @param   \Octris\Core\Db\Device\Riak\DataObject  $object     Data to insert into collection.
     * @param   string                                      $key        Optional key to insert.
     * @return  string|bool                                             Returns the inserted key if insert succeeded or false.
     */
    public function insert(\Octris\Core\Db\Device\Riak\DataObject $object, $key = null)
    {
        $request = $this->connection->getRequest(
            http::T_POST,
            '/buckets/' . $this->name . '/keys' . (is_null($key) ? '' : '/' . $key)
        );

        $request->addHeader('Content-Type', $object->getContentType());

        $this->addReferences($request, $object);

        $request->execute(json_encode($object));

        if (($return = ($request->getStatus() == 201))) {
            $loc = $request->getResponseHeader('location');

            $return = substr($loc, strrpos($loc, '/') + 1);
        }

        return $return;
    }

    /**
     * Update data in database collection.
     *
     * @param   \Octris\Core\Db\Device\Riak\DataObject  $object     Data to insert into collection.
     * @param   string                                      $key        Key to update.
     * @return  bool                                                    Returns true if update succeeded otherwise false.
     */
    public function update(\Octris\Core\Db\Device\Riak\DataObject $object, $key)
    {
        $request = $this->connection->getRequest(
            http::T_PUT,
            '/buckets/' . $this->name . '/keys/' . $key
        );
        $request->addHeader('Content-Type', $object->getContentType());

        $this->addReferences($request, $object);

        $request->execute(json_encode($object));

        return ($request->getStatus() == 200);
    }
}
