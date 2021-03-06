<?php

namespace App\Generators;

use App\Models\Article;
use App\Models\ArticleCategory;
use Plasticode\Generators\TaggableEntityGenerator;
use Plasticode\Traits\Publishable;
use Plasticode\Util\Strings;
use Respect\Validation\Validator;

class ArticlesGenerator extends TaggableEntityGenerator
{
    use Publishable;

    public function getRules(array $data, $id = null) : array
    {
        $rules = parent::getRules($data, $id);

        $rules['name_ru'] = $this->rule('text');//->regex($cyr("'\(\):\-\.\|,\?!—«»"));
        $rules['parent_id'] = Validator::nonRecursiveParent($this->entity, $id);

        if (array_key_exists('name_en', $data) && array_key_exists('cat', $data)) {
            $rules['name_en'] = $this->rule('text')
                //->regex($this->rules->lat("':\-"))
                ->articleNameCatAvailable($data['cat'], $id);
        }
        
        return $rules;
    }
    
    public function getOptions() : array
    {
        $options = parent::getOptions();
        
        $options['exclude'] = [ 'text' ];
        $options['admin_template'] = 'articles';
        
        return $options;
    }
    
    public function afterLoad(array $item) : array
    {
        $item = parent::afterLoad($item);
        
        $article = Article::get($item['id']);
        
        $item['name_en_esc'] = Strings::fromSpaces($article->nameEn);

        $cat = $article->category();
        
        if ($cat) {
            $item['cat_ru'] = $cat->nameRu;
            $item['cat_en'] = $cat->nameEn;
            $item['cat_en_esc'] = Strings::fromSpaces($cat->nameEn);
        }
        
        $game = $article->game();
        $parts = [ $game->name ];

        $parent = $article->parent();

        if ($parent && $parent->noBreadcrumb != 1) {
            $parentParent = $parent->parent();
            
            if ($parentParent && $parentParent->noBreadcrumb != 1) {
                $parts[] = '...';
            }

            $parts[] = $parent->nameRu;
        }
        
        $parts[] = $article->nameRu;
        $partsStr = implode(' » ', $parts);
        
        $item['select_title'] = "[{$article->getId()}] {$partsStr}";
        
        $item['tokens'] = $game->name . ' ' . $article->nameRu;

        return $item;
    }
    
    public function beforeSave(array $data, $id = null) : array
    {
        $data = parent::beforeSave($data, $id);
        
        $data['cache'] = null;

        $data = $this->publishIfNeeded($data);
        
        return $data;
    }

    public function afterSave(array $item, array $data) : void
    {
        parent::afterSave($item, $data);
        
        if (!$item->name_en) {
            $item->name_en = $item->name_ru;
            $item->save();
        }

        $this->notify($item, $data);
    }

    private function notify(array $item, array $data) : void
    {
        if ($this->isJustPublished($item, $data) && $item->announce == 1) {
            if ($item->cat) {
                $cat = ArticleCategory::get($item->cat);
                
                if ($cat) {
                    $catName = $cat->nameEn;
                }
            }
            
            $url = $this->linker->article($item->name_en, $catName);
            $url = $this->linker->abs($url);
            
            /*$this->telegram->sendMessage('warcry', "Опубликована статья:
<a href=\"{$url}\">{$item->name_ru}</a>");*/
        }
    }
}
