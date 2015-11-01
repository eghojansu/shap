<?php

namespace ShapApp\helper;

use Base;
use F3;

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
}
