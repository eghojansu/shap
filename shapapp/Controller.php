<?php

namespace ShapApp;

use Base;
use F3;
use Shap;
use Template;

abstract class Controller
{
    /**
     * Layout
     * @var string
     */
    protected $layout;
    /**
     * Content key
     * @var string
     */
    protected $content = 'CONTENT';
    /**
     * User
     * @var User object
     */
    protected $user;
    /**
     * Is request post
     * @var bool
     */
    protected $isPost;
    /**
     * Is request ajax
     * @var bool
     */
    protected $isAjax;
    /**
     * Home route
     * @var string
     */
    protected $home = '@home';

    protected function ajaxOnly()
    {
        $this->isAjax || F3::error(403);
        return $this->noLayout();
    }

    protected function ajaxOut($data)
    {
        header('Content-type: application/json');
        echo json_encode($data);
    }

    /**
     * Require PHPOffice/PHPExcel
     * @param  string $fname    filename
     * @param  object $PHPExcel
     * @return null
     */
    protected function excel($fname, $PHPExcel)
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$fname.'.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        unset($PHPExcel);
        $objWriter->setPreCalculateFormulas(false);
        $objWriter->save("php://output");
        exit;
    }

    protected function assign($file)
    {
        F3::set($this->content, Template::instance()->render($file));
    }

    protected function render($file)
    {
        echo Template::instance()->render($file);
    }

    protected function noLayout()
    {
        $this->layout = null;
        return $this;
    }

    protected function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }

    protected function goHome()
    {
        F3::reroute($this->home);
    }

    protected function refresh()
    {
        F3::reroute();
    }

    public function afterroute()
    {
        empty($this->layout) || $this->render($this->layout);
    }

    public function __construct(Base $fw)
    {
        $this->user = Shap::$app->user;
        $this->isPost = 'POST'===$fw['VERB'];
        $this->isAjax = F3::get('AJAX');
    }
}
