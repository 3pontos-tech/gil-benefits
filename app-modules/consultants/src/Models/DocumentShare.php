<?php

namespace TresPontosTech\Consultants\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TresPontosTech\Consultants\Database\Factories\DocumentShareFactory;

/**
 * @property int $id
 * @property string $document_id
 * @property string $consultant_id
 * @property string $employee_id
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(DocumentShareFactory::class)]
class DocumentShare extends Model
{
    /** @use HasFactory<DocumentShareFactory> */
    use HasFactory;

    protected $table = 'document_shares';

    protected $fillable = [
        'document_id',
        'consultant_id',
        'employee_id',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'bool',
        ];
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * @return BelongsTo<Consultant, $this>
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
