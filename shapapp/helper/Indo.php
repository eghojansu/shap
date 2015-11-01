<?php

namespace ShapApp\helper;

class Indo
{
    public $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni',
        'Juli','Agustus','September','Oktober','November','Desember'];

    public function sambungKata($parts)
    {
        $counter = -1;
        $beforeLast = count($parts)-2;
        $concated = '';
        foreach ($parts as $key => $value)
            $concated .= $value.(++$counter===$beforeLast?' DAN ':(($counter<$beforeLast)?', ':''));
        return $concated;
    }

    public function namaBulan($no)
    {
        return isset($this->bulan[$no])?$this->bulan[$no]:$no;
    }

    public function noBulan($nama)
    {
        return false===($no = array_search($nama, $this->bulan))?$nama:$no;
    }

    public function rupiah($number)
    {
        return number_format($number, 2, ',', '.');
    }
}
