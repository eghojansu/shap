<?php

namespace ShapApp;

use Audit;
use F3;
use Shap;
use DateTime;
use InvalidArgumentException;

class Validator
{
    const DEFAULT_MESSAGE = 'Failed when validating %field with %rule rule';
    protected $data;
    protected $pkeys;
    protected $errors = [];
    protected $messages = [
        'required'=>'%field cannot empty',
        'max'=>'%field maximum %param1',
        'min'=>'%field minimum %param1',
        'between'=>'%field must between %param1 and %param2',
        'lengthMax'=>'%field maximum %param1 character',
        'lengthMin'=>'%field minimum %param1 character',
        'lengthBetween'=>'%field must between %param1 and %param2',
        'dateMax'=>'%field maximum %param1',
        'dateMin'=>'%field minimum %param1',
        'dateBetween'=>'%field must between %param1 and %param2',
        'dateValid'=>'%field invalid date format, given value %value',
        'in'=>'%field invalid, value must be in (%paramString)',
        'is_numeric'=>'%field must be numeric',
        'alpha'=>'%field must be alpha text',
        'alphanumeric'=>'%field must be alpha-numeric text',
        'uppercase'=>'%field must be in uppercase',
        'lowercase'=>'%field must be in lowercase',
        'url'=>'%field is not valid url',
        'email'=>'%field is not email',
        'ipv4'=>'%field is not valid IPV4',
        'ipv6'=>'%field is not valid IPV6',
        'ipPrivate'=>'%field is not private IP',
        'ipPublic'=>'%field is not public IP',
        'ipReserved'=>'%field is not reserved IP',
        'exists'=>'%field "%value%" was not exists',
        'unique'=>'%field "%value%" was exists',
        ];
    public $success = null;

    public function __construct(array &$data = [], array $pkeys = [], array $rules = [], array $messages = [])
    {
        $this->messages = $messages+(F3::get('validation_message')?:[])+$this->messages;
        $this->data =& $data;
        $this->pkeys = $pkeys;
        foreach ($rules as $field => $fieldRules) {
            $value = isset($data[$field])?$data[$field]:null;
            foreach (is_string($fieldRules)?
                $this->extractRules($fieldRules):$fieldRules as $rule=>$param) {
                $result = $this->apply($rule, $param, $value);
                if (is_bool($result)) {
                    if (!$result) {
                        isset($this->errors[$field]) || $this->errors[$field] = [];
                        $this->errors[$field][] = $this->buildMessage(
                            $rule, $field, $value, $param);
                    }
                } else
                    empty($result) || $value = $result;
            }
            !isset($data[$field]) || $data[$field] = $value;
        }
        $this->success = 0===count($this->errors);
    }

    public function result()
    {
        return $this->errors;
    }

    public function _required($value)
    {
        return isset($value);
    }

    /**
     * Check wether value was exists or not in a model
     * @param  mixed $value id to check
     * @param  string $model model class, currently only support ShapApp\table\SQL
     * @return bool
     */
    public function _unique($value,$model)
    {
        $model = new $model;
        $model->loadByPK($this->data);

        return $model->dry() || $model->pkeys==$this->pkeys;
    }

    /**
     * Check wether value was exists in a model
     * @param  mixed $value id to check
     * @param  string $model model class, currently only support ShapApp\table\SQL
     * @return bool
     */
    public function _exists($value,$model)
    {
        $model = new $model;
        $model->loadByPK($this->data);

        return $model->valid();
    }

    public function _url($value)
    {
        return Audit::instance()->url($value);
    }

    public function _email($value,$mx=true)
    {
        $mx = is_bool($mx)?$mx:('false'===strtolower($mx)?false:true);

        return Audit::instance()->email($value,$mx);
    }

    public function _ipv4($value)
    {
        return Audit::instance()->ipv4($value);
    }

    public function _ipv6($value)
    {
        return Audit::instance()->ipv6($value);
    }

    public function _ipPrivate($value)
    {
        return Audit::instance()->isprivate($value);
    }

    public function _ipPublic($value)
    {
        return Audit::instance()->ispublic($value);
    }

    public function _ipReserved($value)
    {
        return Audit::instance()->isreserved($value);
    }

    public function _in()
    {
        $param = func_get_args();

        return in_array(array_shift($param), $param);
    }

    public function _max($value,$max)
    {
        return $value<=$max;
    }

    public function _min($value,$min)
    {
        return $value>=$min;
    }

    public function _between($value,$min,$max)
    {
        return $value>=$min && $value<=$max;
    }

    public function _lengthMax($value,$max)
    {
        return strlen($value)<=$max;
    }

    public function _lengthMin($value,$min)
    {
        return strlen($value)>=$min;
    }

    public function _lengthBetween($value,$min,$max)
    {
        return strlen($value)>=$min && strlen($value)<=$max;
    }

    public function _dateMax($value,$max,$delim='-/ ')
    {
        if (!$this->_dateValid($value))
            return false;
        elseif (!$this->_dateValid($max))
            throw new InvalidArgumentException('Invalid max date argument');

        $dv = $this->extractDate($value,$delim);
        $dm = $this->extractDate($max,$delim);

        $dvo = new DateTime;
        $this->dateMode(1,$dv)?
            $dvo->setDate($dv[0],$dv[1],$dv[2]):
            $dvo->setDate($dv[2],$dv[1],$dv[0]);
        $dmo = new DateTime;
        $this->dateMode(1,$dm)?
            $dmo->setDate($dm[0],$dm[1],$dm[2]):
            $dmo->setDate($dm[2],$dm[1],$dm[0]);

        return $dvo<=$dmo;
    }

    public function _dateMin($value,$min,$delim='-/ ')
    {
        if (!$this->_dateValid($value))
            return false;
        elseif (!$this->_dateValid($min))
            throw new InvalidArgumentException('Invalid min date argument');

        $dv = $this->extractDate($value,$delim);
        $dm = $this->extractDate($min,$delim);

        $dvo = new DateTime;
        $this->dateMode(1,$dv)?
            $dvo->setDate($dv[0],$dv[1],$dv[2]):
            $dvo->setDate($dv[2],$dv[1],$dv[0]);
        $dmo = new DateTime;
        $this->dateMode(1,$dm)?
            $dmo->setDate($dm[0],$dm[1],$dm[2]):
            $dmo->setDate($dm[2],$dm[1],$dm[0]);

        return $dvo>=$dmo;
    }

    public function _dateBetween($value,$min,$max,$delim='-/ ')
    {
        if (!$this->_dateValid($value))
            return false;
        elseif (!$this->_dateValid($min))
            throw new InvalidArgumentException('Invalid min date argument');
        elseif (!$this->_dateValid($max))
            throw new InvalidArgumentException('Invalid max date argument');

        $dv = $this->extractDate($value,$delim);
        $dn = $this->extractDate($min,$delim);
        $dx = $this->extractDate($max,$delim);

        $dvo = new DateTime;
        $this->dateMode(1,$dv)?
            $dvo->setDate($dv[0],$dv[1],$dv[2]):
            $dvo->setDate($dv[2],$dv[1],$dv[0]);
        $dno = new DateTime;
        $this->dateMode(1,$dn)?
            $dno->setDate($dn[0],$dn[1],$dn[2]):
            $dno->setDate($dn[2],$dn[1],$dn[0]);
        $dxo = new DateTime;
        $this->dateMode(1,$dx)?
            $dxo->setDate($dx[0],$dx[1],$dx[2]):
            $dxo->setDate($dx[2],$dx[1],$dx[0]);

        return $dvo>=$dno && $dvo<=$dxo;
    }

    public function _dateValid($value, $delim = '-/ ')
    {
        $date = $this->extractDate($value, $delim);

        return empty($date)?false:
            ($this->dateMode(1,$date) || $this->dateMode(2,$date));
    }

    public function _alpha($value)
    {
        return (bool) preg_match('/^[a-z ]+$/i', $value);
    }

    public function _alphanumeric($value)
    {
        return (bool) preg_match('/^[a-z0-9_\-\. ]+$/i', $value);
    }

    public function _uppercase($value)
    {
        return (bool) preg_match('/^[A-Z0-9_\-\. ]+$/', $value);
    }

    public function _lowercase($value)
    {
        return (bool) preg_match('/^[a-z0-9_\-\. ]+$/', $value);
    }

    private function apply($rule, $param, $value, array $data)
    {
        if (is_numeric($rule))
            return call_user_func($param, $value);
        elseif (method_exists($this, '_'.$rule))
            return call_user_func_array([$this, '_'.$rule], is_array($param)?
                array_merge([$value], $param, [$data]):[$value, $param, $data]);
        elseif (function_exists($rule))
            return call_user_func($rule, $value);
        else
            throw new InvalidArgumentException('Rule "'.$rule.'" was not exists');
    }

    private function extractRules($str)
    {
        $rules = [];
        foreach (explode('|', $str) as $rule) {
            $rule = explode(':', $rule);
            $paramIsRegexp = 'match'===reset($rule);
            $rules[array_shift($rule)] = $paramIsRegexp?
                array_pop($rule):explode(',', array_pop($rule));
        }

        return $rules;
    }

    private function buildMessage($rule, $field, $value, $param)
    {
        $replace = [
            '%value'=>$value,
            '%field'=>$field,
            '%rule'=>$rule,
            ];
        if (is_array($param)) {
            $replace += Shap::prependKey('%param',
                array_filter(array_merge([0=>''],$param)));
            $replace['%paramString'] = implode(',', $param);
        }
        else {
            $replace['%param1'] = $param;
            $replace['%paramString'] = $param;
        }

        return str_replace(array_keys($replace),array_values($replace),
            isset($this->messages[$rule])?$this->messages[$rule]:
                self::DEFAULT_MESSAGE);
    }

    private function extractDate($str, $delim = '-/ ')
    {
        $date = [];
        while ($delim) {
            $date = explode(substr($delim, 0, 1), $str);
            if (3===count($date)) break;
            else $date = [];
            $delim = substr($delim, 1);
        }

        return $date;
    }

    /**
     * Check date mode
     * @param  int $mode 1 YYYY-MM-DD, 2 DD-MM-YYYY
     * @param  array  $date
     * @return bool
     */
    private function dateMode($mode, array $date)
    {
        return (1===$mode && checkdate($date[1], $date[2], $date[0])) ||
                (2===$mode && checkdate($date[1], $date[0], $date[2]));
    }
}
