<?php

namespace App\Models;

use App\Models\Traits\Names;
use Plasticode\Collection;
use Plasticode\Models\DbModel;
use Plasticode\Models\Traits\Description;
use Plasticode\Models\Traits\FullPublish;
use Plasticode\Models\Traits\Stamps;
use Plasticode\Models\Traits\Tags;

abstract class Comic extends DbModel
{
    use Description, FullPublish, Stamps, Tags, Names;
    
    // PROPS

    public abstract function pages() : Collection;
    
    public abstract function createPage();
    
    public function prev() : ?self
    {
        return null;
    }
    
    public function next() : ?self
    {
        return null;
    }

    public function pageByNumber($number)
    {
        return $this->pages()->where('number', $number)->first();
    }
    
    public function count() : int
    {
        return $this->pages()->count();
    }
    
    public function first()
    {
        return $this->pages()->first();
    }
    
    public function last()
    {
        return $this->pages()->last();
    }
    
    public function cover()
    {
        return $this->first();
    }

    public function maxPageNumber($exceptId = null) : int
    {
        $max = $this->pages(true)
            ->where(
                function ($page) use ($exceptId) {
                    return $page->id != $exceptId;
                }
            )
            ->asc('number')
            ->last();
        
        return $max ? $max->number : 0;
    }
}
