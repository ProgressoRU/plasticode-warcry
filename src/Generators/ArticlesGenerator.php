<?php

namespace App\Generators;

use App\Models\Article;
use App\Models\ArticleCategory;
use Plasticode\Generators\TaggableEntityGenerator;
use Plasticode\Generators\Traits\Publishable;
use Plasticode\Util\Strings;
use Respect\Validation\Validator;

class ArticlesGenerator extends TaggableEntityGenerator
{
    use Publishable
    {
        beforeSave as protected publishableBeforeSave;
    }

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

        $options['exclude'] = ['text'];
        $options['admin_template'] = 'articles';

        return $options;
    }

    public function afterLoad(array $item) : array
    {
        $item = parent::afterLoad($item);

        /** @var \App\Models\Article */
        $article = Article::get($item[$this->idField]);

        $item['name_en_esc'] = Strings::fromSpaces($article->nameEn);

        $cat = $article->category();

        if ($cat) {
            $item['cat_ru'] = $cat->nameRu;
            $item['cat_en'] = $cat->nameEn;
            $item['cat_en_esc'] = Strings::fromSpaces($cat->nameEn);
        }

        $game = $article->game();
        $parts = [$game->name];

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
        
        $item['select_title'] = '[' . $article->getId() . '] ' . $partsStr;
        
        $item['tokens'] = $game->name . ' ' . $article->nameRu;

        return $item;
    }
    
    public function beforeSave(array $data, $id = null) : array
    {
        $data = $this->publishableBeforeSave($data, $id);

        $nameEn = $data['name_en'] ?? null;

        if (!$nameEn) {
            $data['name_en'] = $data['name_ru'];
        }

        $data['cache'] = null;

        return $data;
    }

    public function afterSave(array $item, array $data) : void
    {
        parent::afterSave($item, $data);

        $this->notify($item, $data);
    }

    private function notify(array $item, array $data) : void
    {
        if ($this->isJustPublished($item, $data) && $item['announce'] == 1) {
            $cat = $item['cat'] ?? null;

            if ($cat) {
                $category = ArticleCategory::get($cat);

                if ($category) {
                    $catName = $category->nameEn;
                }
            }

            $url = $this->linker->article($item['name_en'], $catName);
            $url = $this->linker->abs($url);

            // $this->telegram->sendMessage(
            //     'warcry',
            //     '[Статья] <a href="' . $url . '">' . $item['name_ru'] . '</a>'
            // );
        }
    }
}
