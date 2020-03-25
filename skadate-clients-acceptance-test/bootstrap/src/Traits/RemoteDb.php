<?php

namespace Skadate\Traits;

trait RemoteDb
{
    /**
     * Site salt
     * @var string
     */
    private $siteSalt;

    /**
     * Mario url
     * @var string
     */
    private $marioUrl;


    /**
     * Db table prefix
     * @var string
     */
    private $dbTablePrefix;

    /**
     * Set salt
     * 
     * @param string $salt
     * @return void 
     */
    protected function setSalt($salt) 
    {
        $this->siteSalt = $salt;
    }

    /**
     * Set mario url
     * 
     * @param string $marioUrl
     * @return void 
     */
    protected function setMarioUrl($marioUrl) 
    {
        $this->marioUrl = $marioUrl;
    }
 
    /**
     * Set db table prefix
     * 
     * @param string $dbTablePrefix
     * @return void 
     */
    protected function setDbTablePrefix($dbTablePrefix) 
    {
        $this->dbTablePrefix = $dbTablePrefix;
    }

    /**
     * Execute sql file
     *
     * @param string $path
     * @param array $replace
     * @return void
     */
    protected function executeSqlFile($path, array $replace = [])
    {
        $queries = [];
        $replace = [
            'from' => array_merge([
                '__prefix__',
            ], (!empty($replace) ? array_keys($replace) : [])),
            'to' => array_merge([
                $this->dbTablePrefix
            ], (!empty($replace) ? array_values($replace) : []))
        ];

        if (!file_exists($path) || !($handler = fopen($path, 'r'))) {
            throw new Exception('Sql file not found or permission denied');
        }
 
        $query = null;
        $delimiter = ';';
 
        // collect all queries
        while(!feof($handler)) {
            $str = trim(fgets($handler));

            if(empty($str) || $str[0] == '' || $str[0] == '#' || ($str[0] == '-' && $str[1] == '-'))  {
                continue;
            }

            // change delimiter
            if(strpos($str, 'DELIMITER //') !== false || strpos($str, 'DELIMITER ;') !== false) {
                $delimiter = trim(str_replace('DELIMITER', '', $str));
                continue;
            }

            $query .= ' ' . $str;

            // check for multi line query
            if(substr($str, -strlen($delimiter)) != $delimiter) {
                continue;
            }

            // execute query
            if (!empty($replace['from']) && !empty($replace['to'])) {
                $query = str_replace($replace['from'], $replace['to'], $query);
            }

            if ($delimiter != ';') {
                $query = str_replace($delimiter, '', $query);
            }

            $queries[] = trim($query);
            $query = null;
        }

        fclose($handler);
        $serialized = serialize($queries);

        $post = [
            'action' => 'sql',
            'queries' => $serialized,
            'hash'   => md5($serialized . $this->siteSalt),
        ];

        $ch = curl_init($this->marioUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        // execute!
        curl_exec($ch);
        curl_close($ch);
    }
}
