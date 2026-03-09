<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Link;
use Illuminate\Auth\Access\HandlesAuthorization;

class LinkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any links.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Link  $link
     * @return mixed
     */
    public function view(User $user, Link $link)
    {
        //
    }

    /**
     * Determine whether the user can create links.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function create(User $user): bool
    {
        if ($user->active_plan->features->links == -1) {
            return true;
        } elseif($user->active_plan->features->links > 0) {
            // Set the count for multi links counter
            $mCount = 0;

            // If the request is for a multi links creation
            if (request()->input('multiple_links')) {
                // Get the links
                $links = preg_split('/\n|\r/', request()->input('urls'), -1, PREG_SPLIT_NO_EMPTY);

                // If the request contains more than one link
                if (count(preg_split('/\n|\r/', request()->input('urls'), -1, PREG_SPLIT_NO_EMPTY)) > 1) {

                    // Get the links count, and subtract 1 value, the remaining will be used to emulate the total links count against the limit
                    $mCount = (count($links)-1);
                }
            }

            // If the total links count (including multi links, if any in the request) exceeds the limits
            if (($user->linksCount+$mCount) < $user->active_plan->features->links) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can update the link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Link  $link
     * @return mixed
     */
    public function update(User $user, Link $link)
    {
        //
    }

    /**
     * Determine whether the user can delete the link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Link  $link
     * @return mixed
     */
    public function delete(User $user, Link $link)
    {
        //
    }

    /**
     * Determine whether the user can restore the link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Link  $link
     * @return mixed
     */
    public function restore(User $user, Link $link)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Link  $link
     * @return mixed
     */
    public function forceDelete(User $user, Link $link)
    {
        //
    }

    /**
     * Determine whether the user can use Spaces.
     *
     * @param User $user
     * @return bool
     */
    public function spaces(User $user): bool
    {
        return $user->active_plan->features->spaces != 0;
    }

    /**
     * Determine whether the user can use Domains.
     *
     * @param User $user
     * @return bool
     */
    public function domains(User $user): bool
    {
        return $user->active_plan->features->domains != 0;
    }

    /**
     * Determine whether the user can use Pixels.
     *
     * @param User $user
     * @return bool
     */
    public function pixels(User $user): bool
    {
        return $user->active_plan->features->pixels != 0;
    }

    /**
     * Determine whether the user can see the Link Stats.
     *
     * @param ?User $user
     * @return bool
     */
    public function stats(?User $user): bool
    {
        return !$user || $user->active_plan->features->link_stats == 1;
    }

    /**
     * Determine whether the user can use Targeting.
     *
     * @param User $user
     * @return bool
     */
    public function targets(User $user): bool
    {
        return $user->active_plan->features->link_targeting == 1;
    }

    /**
     * Determine whether the user can use UTM.
     *
     * @param User $user
     * @return bool
     */
    public function utm(User $user): bool
    {
        return $user->active_plan->features->link_utm == 1;
    }

    /**
     * Determine whether the user can use Redirect Password.
     *
     * @param User $user
     * @return bool
     */
    public function redirectPassword(User $user): bool
    {
        return $user->active_plan->features->link_password == 1;
    }

    /**
     * Determine whether the user can use Expire.
     *
     * @param User $user
     * @return bool
     */
    public function expiration(User $user): bool
    {
        return $user->active_plan->features->link_expiration == 1;
    }

    /**
     * Determine whether the user can use Global Domains.
     *
     * @param User $user
     * @return bool
     */
    public function globalDomains(User $user): bool
    {
        return $user->active_plan->features->global_domains == 1;
    }

    /**
     * Determine whether the user can use Deep Links.
     *
     * @param User $user
     * @return bool
     */
    public function deepLinks(User $user): bool
    {
        return $user->active_plan->features->link_deep == 1;
    }
}
