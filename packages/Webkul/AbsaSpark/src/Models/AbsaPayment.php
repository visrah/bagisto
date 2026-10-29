<?php

namespace Webkul\AbsaSpark\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\AbsaSpark\Contracts\AbsaPayment as AbsaPaymentContract;
use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\Sales\Models\OrderProxy;

class AbsaPayment extends Model implements AbsaPaymentContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'absa_payments';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => PaymentStatus::class,
        'raw_request' => 'array',
        'raw_response' => 'array',
        'meta' => 'array',
    ];

    /**
     * Get the order this payment pays for.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }
}
