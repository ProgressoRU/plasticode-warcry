<?php

namespace App\Validation\Rules;

use Plasticode\Validation\Rules\TableFieldAvailable;

use App\Data\Tables;

class GalleryAuthorAliasAvailable extends TableFieldAvailable {
	public function __construct($id = null) {
		parent::__construct(Tables::GALLERY_AUTHORS, 'alias', $id);
	}
}
