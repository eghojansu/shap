<?php

namespace ShapApp;

use Registry;

class Loader
{
    private $ns;

    public function __construct($ns)
    {
        $this->ns = $ns;
    }

    public function __get($name)
    {
        $id = $this->ns.ucfirst($name);
        if (Registry::exists($id))
            return Registry::get($id);

        return Registry::set($id, new $id);
    }
}
