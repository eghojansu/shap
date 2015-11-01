<?php

namespace ShapApp;

use Base;
use DB\SQL;
use DB\SQL\Mapper;
use F3;
use Registry;
use Shap;
use InvalidArgumentException;

class SQLTable extends Mapper
{
    const E_Connection = 'Error in connection setting for: %s';

    protected $tableName;
    protected $connection = 'default';
    protected $pkeys = [];

    public function __construct()
    {
        $this->tableName || $this->tableName = Shap::className(get_called_class());
        $connID = 'database.'.$this->connection;
        if (!Registry::exists($connID)) {
            $fw = Base::instance();
            $config = ($fw[$connID]?:[])+[
                'type'=>null,
                'user'=>null,
                'pass'=>null,
                ];
            $dsn = $config['type'].':';
            switch ($config['type']) {
                case 'sqlite':
                    $dsn .= $config['file'];
                    break;
                case 'mysql':
                    $dsn .= 'host='.$config['host'].';port='.$config['port'].';dbname='.$config['name'];
                    break;
                default:
                    $dsn = null;
                    break;
            }
            if (empty($dsn))
                throw new InvalidArgumentException(sprintf(self::E_Connection, $connID));
            Registry::set($connID, new \DB\SQL($dsn, $config['user'], $config['pass']));
        }

        parent::__construct(Registry::get($connID), $this->tableName);
        $this->init();
    }

    public function page($fields='*',$page=1,$size=10,$filter=null,array $options=[],$ttl=0)
    {
        $recordsTotal=$this->countWithOption($filter,$options,$ttl);
        $totalPage=ceil($recordsTotal/$size);

        return [
            'data'=>$this->selectArray($fields,$filter,array_merge($options,
                ['limit'=>$size,'offset'=>$page*$size-$size]),$ttl),
            'recordCount'=>$recordsTotal,
            'limit'=>(int) $size,
            'page'=>(int) $page,
            'totalPage'=>(int) $totalPage,
            'firstNumber'=>(int) ($page - 1) * $size + 1
        ];
    }

    /**
     * Count rows
     * @param  string|array|null $force
     * @param array $options
     * @return int
     */
    public function countWithOption($filter=null,array $options=[],$ttl=0)
    {
        $count = clone $this;
        $row = $count->selectArray('COUNT(*) AS '.$this->db->quoteKey('rows'),
            $filter, $options, $ttl);
        unset($count);

        return $row[0]['rows'];
    }

    /**
     * Performing select query
     * @param  string $fields  delimited by , (comma)
     * @param  string|array $filter  where and parameter
     * @param  array  $options group (having),order,limit,offset
     * @param  bool $fetchFirst
     * @param  int $ttl
     * @return array
     */
    public function selectArray($fields='*',$filter=null,array $options = [],$ttl=0)
    {
        $options+=[
            'group'=>null,
            'order'=>null,
            'limit'=>0,
            'offset'=>0
        ];
        $db = $this->db;
        $s = '    ';
        $e = PHP_EOL;
        $sql = 'SELECT '.$e.$s.$fields.$e.'FROM '.$this->table;
        $args=array();
        if ($filter) {
            if (is_array($filter)) {
                $args=isset($filter[1]) && is_array($filter[1])?
                    $filter[1]:
                    array_slice($filter,1,NULL,TRUE);
                $args=is_array($args)?$args:array(1=>$args);
                list($filter)=$filter;
            }
            $sql.=$e.$s.'WHERE '.$filter;
        }
        if ($options['group']) {
            $sql.=$e.$s.'GROUP BY '.implode(',',array_map(
                function($str) use($db) {
                    return preg_replace_callback(
                        '/\b(\w+)\h*(HAVING.+|$)/i',
                        function($parts) use($db) {
                            return $db->quotekey($parts[1]);
                        },
                        $str
                    );
                },
                explode(',',$options['group'])));
        }
        if ($options['order']) {
            $sql.=$e.$s.'ORDER BY '.implode(',',array_map(
                function($str) use($db) {
                    return preg_match('/^(\w+)(?:\h+(ASC|DESC))?\h*(?:,|$)/i',
                        $str,$parts)?
                        ($db->quotekey($parts[1]).
                        (isset($parts[2])?(' '.$parts[2]):'')):$str;
                },
                explode(',',$options['order'])));
        }
        if (preg_match('/mssql|sqlsrv|odbc/', $this->engine) &&
            ($options['limit'] || $options['offset'])) {
            $keys=array_keys($this->_keys);
            $ofs=$options['offset']?(int)$options['offset']:0;
            $lmt=$options['limit']?(int)$options['limit']:0;
            if (strncmp($db->version(),'11',2)>=0) {
                // SQL Server 2012
                if (!$options['order'])
                    $sql.=$e.$s.'ORDER BY '.$db->quotekey($keys[0]);
                $sql.=$e.$s.'OFFSET '.$ofs.' ROWS';
                if ($lmt)
                    $sql.=$e.$s.'FETCH NEXT '.$lmt.' ROWS ONLY';
            }
            else {
                // SQL Server 2008
                $sql=str_replace('SELECT',
                    'SELECT '.
                    ($lmt>0?'TOP '.($ofs+$lmt):'').' ROW_NUMBER() '.
                    'OVER (ORDER BY '.
                        $db->quotekey($keys[0]).') AS rnum,',$sql);
                $sql='SELECT * FROM ('.$e.$s.$sql.$e.$s.') x WHERE rnum > '.($ofs);
            }
        }
        else {
            if ($options['limit'])
                $sql.=$e.$s.'LIMIT '.(int)$options['limit'];
            if ($options['offset'])
                $sql.=$e.$s.'OFFSET '.(int)$options['offset'];
        }

        return $this->db->exec($sql,$args,$ttl);
    }

    /**
     * Load record by primary key
     * @param  string $id
     * @param  array $filter
     * @param  int $ttl
     * @return array|false
     */
    public function loadByPK($id,array $filter = [],$ttl=0)
    {
        if (empty($this->pkeys))
            foreach ($this->fields as $field => $schema)
                false===$schema['pkey'] || $this->pkeys[] = $field;

        if (is_array($id)) {
            foreach ($id as $key=>$value)
                if (!in_array($key, $this->pkeys))
                    unset($id[$key]);
        } else {
            $modeOne = false===strpos($id, '=');
            $idt = F3::split($id);
            $id = [];
            if ($modeOne)
                foreach ($this->pkeys as $field)
                    $id[$field] = array_shift($idt);
            else
                foreach ($idt as $kv) {
                    $kv = array_map('trim', explode('=', $kv));
                    $id[$kv[0]] = $kv[1];
                }
        }

        $idn = [''];
        foreach ($id as $key => $value) {
            $token = ':load_'.$key;
            $idn[0] .= ($idn[0]?' AND ':'').
                $this->db->quoteKey($this->source.'.'.$key).'='.$token;
            $idn[$token] = $value;
        }
        $id = null;

        $idn[0] || user_error(sprintf(SQL::E_PKey,$table),E_USER_ERROR);
        empty($filter[0]) || $filter[0] .= (preg_match('/(and|or)$/i', trim($filter[0]))?
            ' ':' and ').'('.$idn[0].')';
        $filter += $idn;

        $this->load($filter, ['limit'=>1], $ttl);

        return $this;
    }

    public function init()
    {
        // when initialize model
    }
}
