<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Files extends Model
{

    const CREATED_AT = 'ctime';

    const UPDATED_AT = 'mtime';

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(env('TABLE_PREFIX') . 'files');
    }

}
