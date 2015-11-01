<?php

namespace ShapApp\rbac;

use Base;
use Shap;
use ShapApp\rbac\model\Roles;
use ShapApp\rbac\model\UserRoles;
use ShapApp\rbac\model\ViewUserRoles;
use ShapApp\rbac\model\ViewUserPermissions;

class Access
{
    private $userID;
    private $cache = [];
    private $userRoles;
    private $userPermissions;

    public function __construct($userID)
    {
        $this->userID = $userID;
        $this->userRoles = new ViewUserRoles;
        $this->userPermissions = new ViewUserPermissions;
    }

    public function hasRole($roleName)
    {
        $cacheID = 'role-'.$roleName;
        if (empty($this->cache[$cacheID])) {
            $this->userRoles->load(['role=:role and user_id=:id',
                ':role'=>$roleName,
                ':id'=>$this->userID], ['limit'=>1]);
            $this->cache[$cacheID] = !$this->userRoles->dry();
        }

        return $this->cache[$cacheID];
    }

    public function can($permission)
    {
        $permission = trim($permission);
        $cacheID = 'can-'.$permission;
        if (empty($this->cache[$cacheID])) {
            $this->userPermissions->load(['permission=:perm and user_id=:id',
                ':perm'=>$permission,
                ':id'=>$this->userID], ['limit'=>1]);
            $this->cache[$cacheID] = !$this->userPermissions->dry();
        }

        return $this->cache[$cacheID];
    }
}
