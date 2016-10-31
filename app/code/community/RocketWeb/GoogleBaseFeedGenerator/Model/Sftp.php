<?php

/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   RocketWeb_GoogleBaseFeedGenerator
 * @copyright Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
/**
 * This class just adds mode parameter (NET_SFTP_LOCAL_FILE ) to call to allow uploading files
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Sftp extends Varien_Io_Sftp
{
    /**
     * Write a file
     * @param $src Must be a local file name
     */
    public function write($filename, $src, $mode=null)
    {
        $mode = is_null($mode) ? NET_SFTP_LOCAL_FILE : $mode;
        return $this->_connection->put($filename, $src, $mode);
    }

    /**
     * Open a SFTP connection to a remote site.
     *
     * @param array $args Connection arguments
     * @param string $args[host] Remote hostname
     * @param string $args[username] Remote username
     * @param string $args[password] Connection password
     * @param int $args[timeout] Connection timeout [=10]
     *
     */
    public function open(array $args = array())
    {
        if (!isset($args['timeout'])) {
            $args['timeout'] = self::REMOTE_TIMEOUT;
        }
        if (strpos($args['host'], ':') !== false) {
            list($host, $port) = explode(':', $args['host'], 2);
        } else {
            $host = $args['host'];
            $port = self::SSH2_PORT;
        }

        /*
         * new Net_SFTP() will try to read from socket to negotiate SSH handshake without setting timeout on stream.
         * This will end up freezing the code for about 5 minutes. Calling testSftpRead() will set timeout on socket
         * and then try reading. If the reading times out it will throw an exception. This call only throws an
         * exception if connecting to the socket fails or if reading fails, it doesn't check for other problems.
         */
        $this->testSftpRead($host, $port, $args['timeout']);

        $this->_connection = new Net_SFTP($host, $port, $args['timeout']);

        if (!$this->_connection->login($args['username'], $args['password'])) {
            throw new Exception(sprintf(__("Unable to open SFTP connection as %s@%s", $args['username'], $args['host'])));
        }

    }

    /**
     * Test SFTP connection responds before trying to negotiate SSH handshake.
     * This method only throws an exception if connecting to the socket fails or
     * if reading fails, it does not check for other problems.
     *
     * @param string $host
     * @param optional integer $port
     * @param optional integer $timeout
     * @throws Exception
     *
     */
    protected function testSftpRead($host, $port = 22, $timeout = 10)
    {
        $socket = @fsockopen($host, $port, $errNo, $errStr, $timeout);

        if (!$socket) {
            throw new Exception(sprintf(__("Cannot connect to %s. Error %d. %s", $host, $errNo, $errStr)));
        }

        $temp = '';
        $info = array('timed_out' => false);
        stream_set_timeout($socket, $timeout);

        while (!feof($socket) && !preg_match('#^SSH-(\d\.\d+)#', $temp, $matches) && !$info['timed_out']) {
            if (substr($temp, -2) == "\r\n") {
                $temp = '';
            }
            $temp.= fgets($socket, 500);
            $info = stream_get_meta_data($socket);
        }

        fclose($socket);

        if ($info['timed_out']) {
            throw new Exception(sprintf(__("Cannot connect to %s. Error %d. %s", $host, $errNo, $errStr)));
        }
    }
}
