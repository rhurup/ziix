<?php

namespace App\Services;

class MovieFilter extends \RecursiveFilterIterator
{
    public function __construct($iterator)
    {
        parent::__construct($iterator);
    }

    public function accept()
    {
        if ($this->current()->isDir()) {
            return true;
        }

        return $this->current()->isFile() && preg_match("/\.mkv|.mp4$/ui", $this->getFilename());
    }

    public function __toString()
    {
        return $this->current()->getFilename();
    }

}
