<?php

namespace ShapApp\rbac\controller;

use Base;
use Bcrypt;
use F3;
use Shap;
use ShapApp\rbac\model\Roles;
use ShapApp\rbac\model\Permissions;
use ShapApp\rbac\model\RolesPermissions;
use ShapApp\rbac\model\UserRoles;
use ShapApp\rbac\model\ViewUserRoles;
use ShapApp\rbac\model\ViewUserPermissions;
use ShapApp\rbac\model\ViewRolesPermissions;

class RBAC extends \ShapApp\Controller
{
    public $layout = 'rbac.htm';

    public function home($fw)
    {
        $this->needLogin('read user roles');
        $fw->mset([
            'usersRoles'=>(new ViewUserRoles)->userRoles(),
            'roles'=>(new Roles)->selectArray('*',[],[],60),
            ]);
        if ($this->user->prop['model'])
            $fw['userList'] = (new $this->user->prop['model'])->selectArray(
                sprintf('%s as id,%s as username',
                    $this->user->prop['id'],
                    $this->user->prop['username']),
                [],[],60);
        $this->assign('rbac/home.htm');
    }

    public function userDetail($fw,$args)
    {
        $base = [];
        $base[$this->user->prop['id']] = $args['user'];
        $this->noLayout()->ajaxOut(['user'=>(($this->user->prop['model'] &&
            $this->needLogin('read user roles', false))?
            (new $this->user->prop['model'])->loadByPK($args['user'])->cast():[])+
            $base]);
    }

    public function userRemove($fw,$args)
    {
        $this->noLayout()->ajaxOut(['deleted'=>($this->user->id===$args['user'] ||
            !$this->needLogin('delete user role', false))?false:
            (new UserRoles)->erase(['user_id=:idu', ':idu'=>$args['user']]) > 0]);
    }

    public function userRoleRemove($fw, $args)
    {
        $this->noLayout()->ajaxOut(['deleted'=>$this->needLogin('delete user role', false)?
            (new UserRoles)->loadByPK($args['user'].','.$args['role'])->erase():false]);
    }

    public function userRoleAdd($fw, $args)
    {
        $this->noLayout()->ajaxOut(['saved'=>$this->needLogin('create user role',false)?(new UserRoles)
            ->add($args['user'],$args['role']):false]);
    }

    public function permissionsHome($fw)
    {
        $this->needLogin('read permissions');
        $fw->mset([
            'permissions'=>(new Permissions)->page('*',
                Shap::$app->helper->bootstrap->page(),
                Shap::$app->helper->bootstrap->limit()
                ),
            ]);
        $this->assign('permissions/home.htm');
    }

    public function permissionsAdd($fw)
    {
        $result = ['success'=>false,'message'=>'Cannot create permission.'];
        if ($this->needLogin('create permission')) {
            $permission = new Permissions;
            $permission->load(['permission=:p',
                ':p'=>$fw['POST.permission']]);
            if ($permission->dry()) {
                $permission->permission = $fw['POST.permission'];
                $permission->save();
                $result['success'] = true;
            } else
                $result['message'] .= ' Permissions exists.';
        }
        $this->noLayout()->ajaxOut($result);
    }

    public function permissionsUpdate($fw)
    {
        $result = ['success'=>false,'message'=>'Cannot update permission.'];
        if ($this->needLogin('update permission')) {
            $permission = new Permissions;
            $permission->loadByPK($fw['POST.permission_id']);
            if (!$permission->dry()) {
                $permission->permission = $fw['POST.permission'];
                $permission->save();
                $result['success'] = true;
            } else
                $result['message'] .= ' Permissions not exists.';
        }
        $this->noLayout()->ajaxOut($result);
    }

    public function permissionsRemove($fw)
    {
        $result = ['success'=>$this->needLogin('delete permission', false)?
            (new Permissions)->loadByPK($fw['POST.permission_id'])->erase():false,
            'message'=>'Cannot delete permission.'];
        $this->noLayout()->ajaxOut($result);
    }

    public function login($fw)
    {
        !$this->user->isLogged || $fw->reroute('@rbac');

        if ($this->isPost)
            if ('developer'===$fw['POST.username'] &&
                Bcrypt::instance()->verify($fw['POST.password'],
                    '$2y$10$ydVd7I8UkjNUsmhB/UQdgODgCBpdmAbnwgQ0Vfow2FJTe3csDzEdy'))
            {
                $fw['SESSION.login'] = true;
                $fw['SESSION.id'] = 'eghojansu';
                $fw->reroute('@rbac');
            } else
                $fw['error'] = 'Access denied!';

        $this->setLayout('rbac/login.htm');
    }

    public function logout($fw)
    {
        $this->user->logout();
        $fw->reroute('@rbacLogin');
    }

    public function beforeroute($fw)
    {
        $fw->mset([
            'UI'=>__DIR__.'/../view/',
            'menus'=>[
                'rbac'=>['label'=>'Beranda'],
                'rbacRoles'=>['label'=>'Roles'],
                'rbacPermissions'=>['label'=>'Permissions'],
                'rbacLogout'=>['label'=>'Logout'],
                ],
            ]);
        empty($fw['ALIAS']) || $fw['menus.'.$fw['ALIAS'].'.active'] = true;
    }

    private function needLogin($perms, $out = true)
    {
        if ($this->user->isLogged && $this->user->access->can($perms))
            return true;
        !$out || F3::reroute('@rbacLogout');

        return false;
    }
}
