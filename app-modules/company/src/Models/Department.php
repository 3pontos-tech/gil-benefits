<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use TresPontosTech\Company\Database\Factories\DepartmentFactory;
use TresPontosTech\Company\Enums\DepartmentCategory;

/**
 * @property string $id
 * @property string $company_id
 * @property DepartmentCategory $category
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int $total
 */
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
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

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
