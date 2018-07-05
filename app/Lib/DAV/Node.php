<?php

namespace App\Lib\DAV;


trait Node
{

    function getLastModified()
    {

        return filemtime($this->path);

    }

}
