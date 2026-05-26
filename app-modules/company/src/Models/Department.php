<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Company\Database\Factories\DepartmentFactory;
use TresPontosTech\Company\Enums\DepartmentCategory;

/** @use HasFactory<DepartmentFactory> */
class Department extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'category',
        'name',
    ];

    protected $casts = [
        'category' => DepartmentCategory::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
