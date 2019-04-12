<?php

namespace App\Models;

use Plasticode\Models\DbModel;

class Location extends DbModel
{
    // getters - one

	public static function getByName($name)
	{
		return self::query()
		    ->where('name', $name)
		    ->one();
	}
}
