<?php

namespace App\Lib\DAV;

use Sabre\DAV;

class File extends DAV\File
{

    private $path;

    function __construct($path)
    {

        $this->path = $path;

    }

    function getName()
    {

        return basename($this->path);

    }

    function get()
    {

        return fopen($this->path, 'r');

    }

    function getSize()
    {

        return filesize($this->path);

    }

    function getETag()
    {

        return '"' . sha1(
                fileinode($this->path) .
                filesize($this->path) .
                filemtime($this->path)
            ) . '"';

    }

}