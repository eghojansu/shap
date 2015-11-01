<?php

namespace ShapApp;

use Base;
use Template;
use Shap;

abstract class AbstractApplication
{
    public $helper;

	final public function __construct(array $option)
    {
        $option += [
            'DIR'=>realpath(__DIR__.'/../app/'),
            'DEV'=>false
            ];
        extract($option);
        Shap::$app = $this;
        $fw = Base::instance();
        $fw->concat('AUTOLOAD', ';'.implode(';', [
            $DIR, // namespaced with dir name
            $DIR.'modules/', // namespaced with module dir name
            ]));
        $fw->mset([
            'TEMP'=>'runtime/',
            'TZ'=>'Asia/Jakarta',
            'UI'=>$DIR.'view/',
            'PACKAGE'=>Shap::PACKAGE,
            'VERSION'=>Shap::VERSION,
            'paging'=>[
                'length'=>[10,20,30,50,100],
                ],
            'user'=>[
                'model'=>null, // must be instance of Shap table (eg. SQLTable)
                'password'=>'password',
                'username'=>'username',
                'id'=>'id_user',
                'info'=>[],
                'update'=>[],
                ],
            'database'=>[
                'default'=>[
                    'type'=>'mysql',
                    'host'=>'localhost',
                    'port'=>3306,
                    'name'=>null,
                    'user'=>'root',
                    'pass'=>null
                    ],
                'rbac'=>[
                    'type'=>'sqlite',
                    'file'=>$DIR.'data/rbac.sq3',
                    ],
                ],
            ]);
        if ($DEV) {
            $fw->mset([
                'CACHE'=>false,
                'DEBUG'=>3,
                ]);
            $fw->config(__DIR__.'/rbac/routes.ini');
        }
        else
            $fw->mset([
                'CACHE'=>true,
                ]);
        array_map([$fw, 'config'], Shap::find('ini', $DIR, true));
        $this->helper = new Loader('ShapApp\\helper\\');
        $this->user = new User;
        $template = Template::instance();
        $template->filter('path', 'Shap::path');
        $template->filter('url', 'Shap::url');
        $this->init();
    }

    public function init()
    {
    }

	public function up()
    {
        return Base::instance()->run();
    }
}
