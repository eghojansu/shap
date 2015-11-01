<?php

namespace ShapApp\rbac\model;

class UserRoles extends \ShapApp\SQLTable
{
    protected $connection = 'rbac';

    public function add($userID,$roleID)
    {
        $roles = new Roles;
        $roles->load(['role_id=:id', ':id'=>$roleID]);
        if (empty($userID) || $roles->dry())
            return false;

        $roles = null;
        $this->load(['user_id=:idu AND role_id=:id',
            ':id'=>$roleID,
            ':idu'=>$userID]);
        if ($this->dry()) {
            $this->user_id = $userID;
            $this->role_id = $roleID;

            return $this->save();
        }

        return true;
    }
}
