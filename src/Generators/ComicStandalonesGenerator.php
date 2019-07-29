<?php

namespace App\Generators;

use App\Models\ComicStandalone;
use Plasticode\Generators\TaggableEntityGenerator;
use Plasticode\Traits\Publishable;

class ComicStandalonesGenerator extends TaggableEntityGenerator
{
    use Publishable;

    public function getOptions() : array
    {
        $options = parent::getOptions();
        
        $options['admin_template'] = 'entity_with_upload';
        $options['admin_args'] = [
            'upload_path' => 'admin.comics.upload',
        ];
        
        return $options;
    }
    
    public function beforeSave(array $data, $id = null) : array
    {
        $data = parent::beforeSave($data, $id);

        $data = $this->publishIfNeeded($data);

        return $data;
    }

    public function afterLoad(array $item) : array
    {
        $item = parent::afterLoad($item);
        
        $comic = ComicStandalone::get($item['id']);
        
        $item['page_url'] = $comic->pageUrl();
        $item['context_field'] = 'comic_standalone_id';

        return $item;
    }
}
