<?php

namespace TresPontosTech\Consultants;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Consultants\Models\Consultant;

class DocumentShare extends Pivot
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'document_share';

    protected $fillable = [
        'document_id',
        'consultant_id',
        'employee_id',
    ];

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
