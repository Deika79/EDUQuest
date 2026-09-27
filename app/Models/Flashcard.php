<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['position', 'front', 'back'])]
class Flashcard extends Model
{
    /** @return BelongsTo<MissionNode, $this> */
    public function node(): BelongsTo
    {
        return $this->belongsTo(MissionNode::class, 'node_id');
    }
}
