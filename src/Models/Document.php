<?php


namespace Hocuspocus\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    protected $table = 'documents';

    protected $guarded = [];

    public function setAttribute($key, $value)
    {
        if ($key === 'data' && is_string($value)) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $value);
            rewind($stream);
            parent::setAttribute($key, $stream);
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function scopeByModel($query, $object)
    {
        $query
            ->where('model_type', get_class($object))
            ->where('model_id', $object->id);
    }
}
