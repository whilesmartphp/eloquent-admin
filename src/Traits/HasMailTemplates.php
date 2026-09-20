<?php

namespace Whilesmart\Admin\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Admin\Models\MailTemplate;

trait HasMailTemplates
{
    public function mailTemplates(): MorphMany
    {
        return $this->morphMany(config('admin.models.mail_template', MailTemplate::class), 'owner');
    }
}
