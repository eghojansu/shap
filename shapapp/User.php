<?php

namespace ShapApp;

use Base;
use Bcrypt;
use F3;
use ShapApp\rbac\Access;

class User
{
    public $id;
    /**
     * User object
     * @var DB\SQL\Mapper object
     */
    public $map;
    public $prop;
    /**
     * User access
     * @var Shap\Access object
     */
    public $access;
    public $isLogged = false;

    public function update()
    {
        $fw = Base::instance();
        $fw['error'] = 'Update data gagal!';
        $bcrypt = Bcrypt::instance();
        if ($this->map->dry())
            return false;
        elseif (!$bcrypt->verify($fw['POST.'.$this->prop['password']],
            $this->map->get($this->prop['password']))) {
            $fw['error'] .= ' Password tidak cocok!';
            return false;
        } else
            unset($fw['error']);

        foreach ($this->prop['update'] as $field => $post)
            $this->map->set($field, $fw['POST.'.$post]);
        if (isset($this->prop['update'][$this->prop['password']]))
            !$fw['POST.'.$this->prop['update'][$this->prop['password']]] ||
                $this->map->set($this->prop['password'],
                    $bcrypt->hash($fw['POST.'.$this->prop['update'][$this->prop['password']]]));
        if ($saved = $this->map->save()) {
            $fw['SESSION.info'] = 'Data sudah diupdate!';
            $this->setSession(
                null,
                $this->map->cast()
                );
        }

        return $saved;
    }

    public function login()
    {
        $fw = Base::instance();
        if (!$this->map->dry())
            return true;
        else {
            $this->map->load([$this->prop['username'].'=:u',
                ':u'=>$fw['POST.'.$this->prop['username']]], ['limit'=>1]);

            if ($this->map->dry() || !\Bcrypt::instance()->verify(
                $fw['POST.'.$this->prop['password']],
                $this->map->get($this->prop['password']))) {
                $fw['error'] = 'Login gagal!';

                return false;
            }
        }

        $this->isLogged = true;
        return $this->setSession(
            $this->map->get($this->prop['id']),
            $this->map->cast()
            );
    }

    public function setSession($id = null, array $data = [])
    {
        $fw = Base::instance();
        $fw['SESSION.login'] = true;
        empty($id) || $fw['SESSION.id'] = $id;
        foreach ($this->prop['info'] as $info)
            $fw->set('SESSION.'.$info, isset($data[$info])?$data[$info]:null);

        return true;
    }

    public function logout()
    {
        F3::clear('SESSION');
        return true;
    }

    public function info()
    {
        $info = [];
        foreach ($this->prop['info'] as $info)
            $info[] = $fw->get('SESSION.'.$info);

        return $info;
    }

    public function __construct()
    {
        $fw = Base::instance();
        $this->prop = $fw['user'];
        $this->isLogged = $fw['SESSION.login'];
        $this->id = $fw['SESSION.id'];
        empty($this->prop['model']) ||
            $this->map = new $this->prop['model'];
        (empty($this->isLogged) || empty($this->map)) ||
            $this->map->load($this->prop['id'].'='.$this->id);
        $this->access = new Access($this->id);
    }
}
