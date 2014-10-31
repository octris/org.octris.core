<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Logger\Writer;

/**
 * Logger for graylog backend. Inspired by official GELF library.
 *
 * @copyright   copyright (c) 2011-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Graylog implements \Octris\Core\Logger\IWriter
{
    /**
     * Mapping of logger levels to graylog levels.
     *
     * @type    array
     */
    private static $graylog_levels = array(
        \Octris\Core\Logger::T_EMERGENCY => 0,
        \Octris\Core\Logger::T_ALERT     => 1,
        \Octris\Core\Logger::T_CRITICAL  => 2,
        \Octris\Core\Logger::T_ERROR     => 3,
        \Octris\Core\Logger::T_WARNING   => 4,
        \Octris\Core\Logger::T_NOTICE    => 5,
        \Octris\Core\Logger::T_INFO      => 6,
        \Octris\Core\Logger::T_DEBUG     => 7
    );
    
    /**
     * Graylog format version.
     *
     */
    private static $version = '1.0';
    
    /**
     * Constants to more easy configure chunk sizes.
     *
     */
    const T_WAN = 1420;
    const T_LAN = 8154;
    
    /**
     * IP address of graylog server.
     *
     * @type    string
     */
    protected $host;
    
    /**
     * Port number of graylog server.
     *
     * @type    int
     */
    protected $port;
    
    /**
     * Maximum chunk size of packets to send to graylog server.
     *
     * @type    int
     */
    protected $chunk_size;
    
    /**
     * Constructor.
     *
     * @param   string      $hostname       Hostname of graylog server.
     * @param   int         $port           Optional port number the graylog server is expected to listen on.
     * @param   int         $chunk_size     Optional maximum chunk size.
     */
    public function __construct($hostname, $port = 12201, $chunk_size = self::T_WAN)
    {
        $this->host       = gethostbyname($hostname);
        $this->port       = $port;
        $this->chunk_size = $chunk_size;
    }

    /**
     * Create GELF compatible message from logger message.
     *
     * @param   array       $message        Message to convert.
     */
    protected function prepareMessage(array $message)
    {
        $gelf = array(
            'version'       => self::$version,
            'host'          => $message['host'],
            'short_message' => $message['message'],
            'full_message'  => (is_null($message['exception'])
                                ? ''
                                : $message['exception']->getTraceAsString()),
            'timestamp'     => $message['timestamp'],
            'level'         => self::$graylog_levels[$message['level']],
            'facility'      => $message['facility'],
            'file'          => $message['file'],
            'line'          => $message['line'],
            '_code'         => $message['code']
        );

        array_walk($message['data'], function ($v, $k) use (&$gelf) {
            if ($k !== 'id' && $k !== '_id') {
                $gelf[(substr($k, 0, 1) != '_' ? '_' : '') . $k] = $v;
            }
        });

        return $gelf;
    }

    /**
     * Write logging message to graylog server.
     *
     * @param   array       $message        Message to send.
     */
    public function write(array $message)
    {
        $message = $this->prepareMessage($message);
        $message = gzcompress(json_encode($message));

        $sock = stream_socket_client('udp://' . $this->host . ':' . $this->port);

        if (strlen($message) > $this->chunk_size) {
            // message is longer than maximum chunk size -- split message
            $msg_id = hash('sha256', microtime(true) . rand(0, 10000), true);
            $parts  = str_split($message, $this->chunk_size);

            $seq_num = 0;
            $seq_cnt = count($parts);

            foreach ($parts as $part) {
                fwrite(
                    $sock,
                    pack('CC', 30, 15) . $msg_id . pack('nn', $seq_num, $seq_cnt) . $part
                );

                ++$seq_num;
            }
        } else {
            // send one datagram
            fwrite($sock, $message);
        }
    }
}