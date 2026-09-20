<?php

namespace Whilesmart\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Whilesmart\Admin\Support\TemplateRegistry;

class MailTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return app(TemplateRegistry::class)->serialize($this->resource);
    }
}
