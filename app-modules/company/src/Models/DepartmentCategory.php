<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TresPontosTech\Company\Database\Factories\DepartmentCategoryFactory;

/** @use HasFactory<DepartmentCategoryFactory> */
class DepartmentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'category_id');
    }
}
