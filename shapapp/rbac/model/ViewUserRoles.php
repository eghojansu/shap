<?php

namespace ShapApp\rbac\model;

class ViewUserRoles extends \ShapApp\SQLTable
{
    protected $connection = 'rbac';

    public function userRoles()
    {
        $userRoles = [];
        foreach ($this->selectArray('*',[],['order'=>'user_id'],60) as $value) {
            extract($value);
            isset($userRoles[$user_id]) || $userRoles[$user_id] = [];
            $userRoles[$user_id][$role_id] = $role;
        }

        return $userRoles;
    }
}
