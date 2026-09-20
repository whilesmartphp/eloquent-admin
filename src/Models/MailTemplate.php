<?php

namespace Whilesmart\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailTemplate extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['enabled' => 'boolean', 'metadata' => 'array'];

    public function getTable(): string
    {
        return config('admin.mail_templates_table', 'admin_mail_templates');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
