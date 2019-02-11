<?php

namespace App\Generators;

use Respect\Validation\Validator as v;

use Plasticode\Generators\EntityGenerator;

use App\Models\Game;

class GamesGenerator extends EntityGenerator
{
	public function getRules($data, $id = null)
	{
	    $rules = parent::getRules($data, $id);
	    
	    $rules['icon'] = $this->optional('url');
	    $rules['name'] = $this->rule('text')->gameNameAvailable($id);
	    $rules['alias'] = $this->optional('alias')->gameAliasAvailable($id);
	    $rules['news_forum_id'] = $this->optional('posInt');
	    $rules['main_forum_id'] = $this->optional('posInt');
	    $rules['position'] = $this->optional('posInt');
	    $rules['parent_id'] = v::nonRecursiveParent($this->entity, $id);
	    
	    return $rules;
	}
	
	public function afterLoad($item)
	{
	    $item = parent::afterLoad($item);
	    
		$item['tags'] = $this->buildAutoTags($item);

		$parts = [];
		$cur = $item;
		
		while ($cur != null) {
		    $parts[] = $cur['name'];
		    $cur = Game::get($cur['parent_id']);
		}

		$item['select_title'] = implode(' » ', array_reverse($parts));
		
		$game = Game::get($item['id']);
		if (!$game) {
		    dd($item);
		}
		
		//$item['result_icon'] = $game->resultIcon();

		return $item;
	}
	
	private function buildAutoTags($item)
	{
	    $parts = [
	        $item['autotags']
        ];
	    
	    $parentId = $item['parent_id'];
	    
	    while ($parentId) {
	        $parent = Game::get($parentId);
	        if ($parent) {
	            $parts[] = $parent['autotags'];
	        }
	        
	        $parentId = $parent['parent_id'] ?? null;
	    }
	    
	    return implode(', ', array_reverse($parts));
	}
}