<?php

namespace TresPontosTech\Consultants\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentShare extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'document_share';

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
        return (bool)$this->active;
    }

    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
