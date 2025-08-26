<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */

namespace App\Models{
    /**
     * @property int $id
     * @property int $consultant_id
     * @property int $voucher_id
     * @property \Illuminate\Support\Carbon $date
     * @property string $status
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \App\Models\Consultant $consultant
     * @property-read \App\Models\Users\User|null $user
     * @property-read \App\Models\Voucher $voucher
     *
     * @method static \Database\Factories\AppointmentFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereConsultantId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereDate($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereStatus($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereVoucherId($value)
     */
    class Appointment extends \Illuminate\Database\Eloquent\Model {}
}

namespace App\Models\Companies{
    /**
     * @property int $id
     * @property int $user_id
     * @property int|null $plan_id
     * @property string $name
     * @property string $slug
     * @property string $tax_id
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Users\User> $employees
     * @property-read int|null $employees_count
     * @property-read \App\Models\Users\User $owner
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Plans\Plan> $plans
     * @property-read int|null $plans_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VoucherRequest> $voucherRequests
     * @property-read int|null $voucher_requests_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher> $vouchers
     * @property-read int|null $vouchers_count
     *
     * @method static \Database\Factories\Companies\CompanyFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePlanId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSlug($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereTaxId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUserId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Company withoutTrashed()
     */
    class Company extends \Illuminate\Database\Eloquent\Model {}
}

namespace App\Models{
    /**
     * @property int $id
     * @property string $name
     * @property string $phone
     * @property string $email
     * @property string $description
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher> $appointments
     * @property-read int|null $appointments_count
     *
     * @method static \Database\Factories\ConsultantFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant whereDescription($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant whereEmail($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant wherePhone($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultant withoutTrashed()
     */
    class Consultant extends \Illuminate\Database\Eloquent\Model {}
}

namespace App\Models\Plans{
    /**
     * @property int $id
     * @property int $plan_id
     * @property string $name
     * @property int $price
     * @property string $type
     * @property int $quantity
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \App\Models\Plans\Plan $plan
     *
     * @method static \Database\Factories\Plans\ItemFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item wherePlanId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item wherePrice($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereQuantity($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereType($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withoutTrashed()
     */
    class Item extends \Illuminate\Database\Eloquent\Model {}
}

namespace App\Models\Plans{
    /**
     * @property int $id
     * @property int|null $company_id
     * @property string $name
     * @property int $price
     * @property \App\Enums\PlanTypeEnum $type
     * @property int $hours_included
     * @property string $description
     * @property string|null $renewal_date
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Companies\Company> $companies
     * @property-read int|null $companies_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Plans\Item> $items
     * @property-read int|null $items_count
     *
     * @method static \Database\Factories\Plans\PlanFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereCompanyId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereDescription($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereHoursIncluded($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan wherePrice($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereRenewalDate($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereType($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan withoutTrashed()
     */
    class Plan extends \Illuminate\Database\Eloquent\Model {}
}

namespace App\Models\Users{
    /**
     * @property int $id
     * @property int $user_id
     * @property int $company_id
     * @property string $document_id
     * @property string $tax_id
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \App\Models\Users\User $user
     *
     * @method static \Database\Factories\Users\DetailFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereCompanyId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereDocumentId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereTaxId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail whereUserId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Detail withoutTrashed()
     */
    class Detail extends \Illuminate\Database\Eloquent\Model {}
}

namespace App\Models\Users{
    /**
     * @property int $id
     * @property string $name
     * @property string $email
     * @property \Illuminate\Support\Carbon|null $email_verified_at
     * @property string $password
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property string|null $remember_token
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher> $appointments
     * @property-read int|null $appointments_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Companies\Company> $companies
     * @property-read int|null $companies_count
     * @property-read \App\Models\Users\Detail|null $detail
     * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
     * @property-read int|null $notifications_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Companies\Company> $ownedCompanies
     * @property-read int|null $owned_companies_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Appointment> $scheduledAppointments
     * @property-read int|null $scheduled_appointments_count
     *
     * @method static \Database\Factories\Users\UserFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
     */
    class User extends \Illuminate\Database\Eloquent\Model implements \Filament\Models\Contracts\FilamentUser, \Filament\Models\Contracts\HasTenants {}
}

namespace App\Models{
    /**
     * @property int $id
     * @property string $code
     * @property int $company_id
     * @property int|null $consultant_id
     * @property int|null $user_id
     * @property \App\Enums\VoucherStatusEnum $status
     * @property \Illuminate\Support\Carbon|null $valid_until
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property-read \App\Models\Companies\Company|null $company
     * @property-read \App\Models\Consultant|null $consultant
     * @property-read \App\Models\Users\User|null $user
     *
     * @method static \Database\Factories\VoucherFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCode($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCompanyId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereConsultantId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereStatus($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereUserId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereValidUntil($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher withoutTrashed()
     */
    class Voucher extends \Illuminate\Database\Eloquent\Model {}
}

namespace App\Models{
    /**
     * @property-read \App\Models\Companies\Company|null $company
     *
     * @method static \Database\Factories\VoucherRequestFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRequest newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRequest newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherRequest query()
     */
    class VoucherRequest extends \Illuminate\Database\Eloquent\Model {}
}
