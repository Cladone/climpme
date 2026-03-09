<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LinkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'alias' => $this->alias,
            'url' => $this->url,
            'short_url' => $this->shortUrl,
            'title' => $this->title,
            'targets_type' => $this->targets_type,
            'targets' => $this->targets,
            'last_rotation' => $this->last_rotation,
            'sensitive_content' => $this->sensitive_content,
            'privacy' => $this->privacy,
            'password' => ($this->password ? true : false),
            'redirect_password' => ($this->redirect_password ? true : false),
            'active_period_start_at' => $this->active_period_start_at,
            'active_period_end_at' => $this->active_period_end_at,
            'clicks_limit' => $this->clicks_limit,
            'expiration_url' => $this->expiration_url,
            'clicks' => $this->clicks,
            'space' => $this->space,
            'domain' => $this->domain,
            'pixels' => $this->pixels,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'status' => 200
        ];
    }
}
