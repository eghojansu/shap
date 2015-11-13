<?php

namespace ShapApp\rest;

use F3;

abstract class Controller extends ShapApp\Controller
{
    protected $model;
    protected $view;
    protected $paramID;
    protected $homeview;
    protected $filterList = [''];
    protected $options = [];
    protected $search = [
        'keyword'=>'search',
        'fields'=>[],
        ];
    protected $filter = [
        'create'=>null,
        'update'=>null,
        ];
    protected $messages = [
        'create'=>[
            'failed'=>'Data gagal disimpan',
            'success'=>'Data sudah disimpan',
            ],
        'update'=>[
            'failed'=>'Data gagal disimpan',
            'success'=>'Data sudah disimpan',
            ],
        'delete'=>[
            'failed'=>'Data gagal dihapus',
            'success'=>'Data sudah dihapus',
            ],
        ];

    public function home($fw)
    {
        if ($fw['GET.'.$this->search['keyword']]) {
            $filter = '';
            foreach ($this->search['fields'] as $field)
                $filter .= ($filter?' OR ':'').$field.' like :u';
            if ($filter) {
                $this->proFilter[0] .= ' AND ('.$filter.')':
                $this->proFilter[':u'] = $fw['GET.'.$this->search['keyword']];
            }
        }
        $fw['data'] = $this->view->page('*',
            Shap::$app->helper->bootstrap->page(),
            Shap::$app->helper->bootstrap->limit(),
            array_filter($this->proFilter), $this->options);
        $this->assign($this->homeview);
    }

    public function head($fw,$params)
    {
        $this->ajaxOnly();
        !$this->view->loadByPK($this->buildID($params))->dry() || $fw->error(404);
    }

    public function get($fw,$params)
    {
        $this->ajaxOnly();
        !$this->view->loadByPK($this->buildID($params))->dry() || $fw->error(404);
        $this->ajaxOut($this->view->cast());
    }

    public function post($fw,$params)
    {
        $this->ajaxOnly();
        $result = [
            'status'=>false,
            'message'=>$this->messages['create']['failed'],
        ];
        $this->model->copyFrom('POST', $this->filter['create']);
        if ($this->model->insert()->success())
            $result = [
                'status'=>true,
                'message'=>$this->messages['create']['success'],
                'id'=>$this->model->pkeysValue(),
            ];
        else
            $result += [
                'info'=>$this->model->errors(),
                ];
        $this->ajaxOut($result);
    }

    public function put($fw,$params)
    {
        $this->ajaxOnly();
        !$this->model->loadByPK($this->buildID($params))->dry() || $fw->error(404);
        $result = [
            'status'=>false,
            'message'=>$this->messages['update']['failed'],
        ];
        $this->model->copyFrom('POST', $this->filter['update']);
        if ($this->model->insert()->success())
            $result = [
                'status'=>true,
                'message'=>$this->messages['update']['success'],
                'id'=>$this->model->pkeysValue(),
            ];
        else
            $result += [
                'info'=>$this->model->errors(),
                ];
        $this->ajaxOut($result);
    }

    public function delete($fw,$params)
    {
        $this->ajaxOnly();
        !$this->model->loadByPK($this->buildID($params))->dry() || $fw->error(404);
        $this->ajaxOut([
            'status'=($status = $this->model->erase()),
            'message'=>$this->messages['delete'][($status?'success':'failed')],
            ]);
    }

    protected function buildID($params)
    {
        $ids = '';
        foreach (is_array($this->paramID)?$this->paramID:F3::split($this->paramID) as $id)
            empty($params[$id]) || $ids .= ($ids?',':'').$params[$id];

        return $ids;
    }
}
