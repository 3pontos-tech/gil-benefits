<?php

namespace TresPontosTech\PanelCompany\Rules;

use App\Models\Users\Detail;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use TresPontosTech\Company\Models\Company;

class UniqueAtCompany implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $column = Str::afterLast($attribute, '.');

        /** @var Company $company */
        $company = filament()->getTenant();

        $employee = Detail::query()->where($column, '=', $value)->first();

        if (is_null($employee)) {
            return;
        }

        $exists = $company->employees()->where('user_id', $employee->user_id)->exists();

        if ($exists) {
            $fail(__('panel-company::validation.unique_at_company'));
        }
    }
}
