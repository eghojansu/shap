<?php

namespace ShapApp\helper;

use Base;
use F3;
use Shap;

class Bootstrap
{
    public function alert($type,$message)
    {
        return !$message?null:<<<ALERT
<div class="alert alert-{$type} alert-dismissible" role="alert">
    <a href="#" type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
    {$message}
</div>
ALERT;
    }

    public function page()
    {
        $page = F3::get('GET.page');
        return $page>0?$page:1;
    }

    public function limit()
    {
        $fw = Base::instance();
        return in_array($fw['GET.limit'], $fw['paging.length'])?
            $fw['GET.limit']:reset($fw['paging.length']);
    }

    public function paging(array $opt)
    {
        $opt += [
            'adjacent'=>3,
            'page'=>1,
            'totalPage'=>1,
            'recordCount'=>0,
            'limit'=>null,
            ];
        $get = array_filter(F3::get('GET'));
        unset($get['page'],$get['limit']);
        $suffix = $get?'&'.http_build_query($get):'';

        $start = $opt['page']-$opt['adjacent']>0?$opt['page']-$opt['adjacent']:1;
        $end = $opt['page']+$opt['adjacent']<$opt['totalPage']?
            $opt['page']+$opt['adjacent']:$opt['totalPage'];

        $html = '<ul class="pagination">';
        $html .= '<li'.(1===$opt['page']?' class="disabled"':'').'><a href="'.
            (1===$opt['page']?'#':'?limit='.$opt['limit'].'&page='.($opt['page']-1 ).$suffix).
            '">&laquo;</a></li>';

        if ($start>1) {
            $html .= '<li'.(1===$opt['page']?' class="active"':'').'><a href="?limit='.
                $opt['limit'] . '&page=1'.$suffix.'">1</a></li>';
            $html .= '<li class="disabled"><span>...</span></li>';
        }

        for ($i=$start;$i<=$end;$i++)
            $html .= '<li'.($opt['page']===$i?' class="active"':'').
                '><a href="?limit='.$opt['limit'].'&page='.$i.$suffix.'">'.$i.
                '</a></li>';

        if ($end<$opt['totalPage']) {
            $html .= '<li class="disabled"><span>...</span></li>';
            $html .= '<li'.($opt['page']===$totalPage?' class="active"':'').
                '><a href="?limit='.$opt['limit'].'&page='.$opt['totalPage'].$suffix.'">'.
                $opt['totalPage'].'</a></li>';
        }

        $html .= '<li'.(($opt['recordCount']===0 || $opt['page']===$opt['totalPage'])?' class="disabled"':'').
            '><a href="'.(($opt['recordCount']===0 || $opt['page']===$opt['totalPage'])?'#':'?limit='.$opt['limit'].
            '&page='.($opt['page']+1).$suffix). '">&raquo;</a></li>';
        $html .= '</ul>';

        return $html;
    }

    /**
     * Generate html list menu, use F3::$ALIASES and F3::$ALIAS
     * @param  array $menu
     * @return string html
     */
    public function nav($menu, $full = false)
    {
        $fw = Base::instance();
        $menu || $menu = [];
        $active = $fw['ALIAS'];
        $baseUrl = $full?rtrim(Shap::url(),'/'):'';
        isset($menu['items']) || $menu['items'] = [];
        isset($menu['level']) || $menu['level'] = -1;
        $menu['level']++;
        $eol = "\n";

        $str = sprintf('<ul class="%s">',
            ($menu['level']>0?'dropdown-menu':$menu['class'])).$eol;
        foreach ($menu['items'] as $key => $value) {
            if (isset($value['show']) && !$value['show'])
                continue;

            isset($value['label']) || $value['label'] = $key;
            $hasChild = isset($value['items']) && count($value['items'])>0;
            $liAttr   = [
                'class'=>[],
                ];
            $aAttr    = [
                'href'=>'#'===$key[0]?'javascript:;':$baseUrl.Shap::path($key,
                    isset($value['args'])?$value['args']:[]),
                'class'=>[],
                'data-toggle'=>[],
                ];
            if ($menu['level'] < 1 and $hasChild) {
                array_push($liAttr['class'], 'dropdown');
                $value['label'] .= ' <span class="caret"></span>';
            }
            $child = '';
            if ($hasChild) {
                $value['level']  = $menu['level'] + 1;
                $child = $eol.$this->nav($value, $full).$eol;
                array_push($aAttr['class'], 'dropdown-toggle');
                array_push($aAttr['data-toggle'], 'dropdown');
                $aAttr['role'] = 'button';
                $aAttr['aria-expanded'] = 'false';
            }

            if ($active === $key || strpos($child, 'class="active"')!==false) {
                array_push($liAttr['class'], 'active');
                $value['label'] = $value['label'].' <span class="sr-only">(current)</span>';
            }

            $str .= '<li';
            foreach ($liAttr as $key2 => $value2)
                !$value2 || $str .= ' '.$key2.'="'.
                    (is_array($value2)?implode(' ', $value2):$value2).'"';
            $str .= '><a';
            foreach ($aAttr as $key2 => $value2)
                !$value2 || $str .= ' '.$key2.'="'.
                    (is_array($value2)?implode(' ', $value2):$value2).'"';
            $str .= '>'.$value['label'].'</a>';
            $str .= $child;
            $str .= '</li>'.$eol;
        }
        $str .= '</ul>';

        return $str;
    }
}
