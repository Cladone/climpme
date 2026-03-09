<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatarUrl,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'plan' => collect($this->plan)->only(['id', 'name', 'features']),
            'default_domain' => $this->default_domain,
            'default_space' => $this->default_space,
            'default_stats' => $this->default_stats,
            'created_at' => $this->created_at
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
