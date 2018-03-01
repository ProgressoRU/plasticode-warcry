<?php

namespace App\Validation\Rules;

use Plasticode\Validation\Rules\TableFieldAvailable;

use App\Data\Tables;

class GameNameAvailable extends TableFieldAvailable {
	public function __construct($id = null) {
		parent::__construct(Tables::GAMES, 'name', $id);
	}
}
