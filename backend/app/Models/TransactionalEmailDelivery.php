<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionalEmailDelivery extends Model
{
    protected $table = 'transactional_email_deliveries';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}