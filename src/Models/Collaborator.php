<?php


namespace Hocuspocus\Models;


use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Hocuspocus\Contracts\Collaborative;
use PDO;
use Illuminate\Support\Facades\DB;

class Collaborator extends Model
{
    protected $table = 'collaborators';

    protected $guarded = [];

    public static function boot()
    {
        static::deleted(fn($collaborator) => $collaborator->documents->each->delete());

        parent::boot();
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Connect the user from to given collaborative document
     * @param Collaborative $document
     */
    public function connectTo(Collaborative $document): void
    {
        $this->getOrCreateDocument($document)->update([
            'connected' => true,
            'connected_at' => now(),
        ]);
    }

    /**
     * Update the data of the given collaborative document
     * @param Collaborative $document
     * @param string $data
     */
    public function updateDocumentData(Collaborative $document, string $data): void
    {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare('UPDATE documents SET data = ? WHERE collaborator_id = ? AND model_type = ? AND model_id = ?');

        $stmt->bindValue(1, $data, PDO::PARAM_LOB);
        $stmt->bindValue(2, $this->id);
        $stmt->bindValue(3, get_class($document));
        $stmt->bindValue(4, $document->id);

        $stmt->execute();
    }

    /**
     * Disconnect the user from the given collaborative document
     * @param Collaborative $document
     */
    public function disconnectFrom(Collaborative $document): void
    {
        $this->getOrCreateDocument($document)->update([
            'connected' => false,
        ]);
    }

    /**
     * Get or create the document pivot table entry
     * @param Collaborative $document
     * @return Document
     */
    public function getOrCreateDocument(Collaborative $document): Document
    {
        $pivot = $this->documents()->byModel($document)->first();

        if (!$pivot) {
            $existingDoc = Document::where('model_type', get_class($document))
                ->where('model_id', $document->id)
                ->whereNotNull('data')
                ->first();

            $pivot = $this->documents()->create([
                'model_type' => get_class($document),
                'model_id' => $document->id,
                'connected' => true,
                'connected_at' => now(),
                'data' => $existingDoc ? $existingDoc->data : null,
            ]);
        }

        return $pivot;
    }

    /**
     * Get collaborator by the given token
     * @param string $token
     * @return Collaborator
     * @throws ModelNotFoundException
     */
    public static function token(string $token): Collaborator
    {
        return static::where('token', $token)->firstOrFail();
    }

    /**
     *  Generate a cryptographically secure token
     * @throws Exception
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
