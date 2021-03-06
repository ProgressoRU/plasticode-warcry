<?php

namespace App\Models;

use Plasticode\Collection;
use Plasticode\Models\DbModel;
use Plasticode\Query;

class ForumTag extends DbModel
{
    // queries
    
    private static function topicQuery() : Query
    {
        return self::query()
            ->where('tag_meta_app', 'forums')
            ->where('tag_meta_area', 'topics');
    }
    
    public static function getByForumTopic($topicId) : Query
    {
        return self::topicQuery()
            ->where('tag_meta_id', $topicId);
    }
    
    // getters
    
    public static function getForumTopicIdsByTag(string $tag) : Collection
    {
        return self::topicQuery()
            ->whereRaw('(lcase(tag_text) = ?)', [ $tag ])
            ->all()
            ->extract('tag_meta_id');
    }
}
