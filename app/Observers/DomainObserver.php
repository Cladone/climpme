<?php

namespace App\Observers;

use App\Models\Domain;
use App\Traits\WebhookTrait;

class DomainObserver
{
    use WebhookTrait;

    /**
     * Handle the Domain "created" event.
     *
     * @param  \App\Models\Domain  $domain
     * @return void
     */
    public function created(Domain $domain)
    {
        $this->callWebhook(config('settings.webhook_domain_created'), [
            'id' => $domain->id,
            'name' => $domain->name,
            'action' => 'created'
        ]);
    }

    /**
     * Handle the Domain "deleted" event.
     *
     * @param  \App\Models\Domain  $domain
     * @return void
     */
    public function deleted(Domain $domain)
    {
        $this->callWebhook(config('settings.webhook_domain_deleted'), [
            'id' => $domain->id,
            'name' => $domain->name,
            'action' => 'deleted'
        ]);
    }

    /**
     * Handle the Domain "deleting" event.
     *
     * @param  \App\Models\Domain  $domain
     * @return void
     */
    public function deleting(Domain $domain)
    {
        // Delete all the related links
        foreach ($domain->links as $link) {
            $link->delete();
        }
    }
}
