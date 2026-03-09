<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidateLinkStatsPasswordRequest;
use App\Models\Link;
use App\Models\Stat;
use App\Traits\DateRangeTrait;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use League\Csv as CSV;

class StatController extends Controller
{
    use DateRangeTrait;

    /**
     * Show the Overview stats page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, $id)
    {
        $link = Link::where('id', $id)->firstOrFail();

        $now = Carbon::now();
        $range = $this->range($link->created_at);

        // If the link is from a Guest
        if (!$link->user) {
            return view('stats.container', ['view' => 'guest', 'link' => $link, 'now' => $now]);
        }

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $rangeMap = $this->calcAllDates(Carbon::createFromFormat('Y-m-d', $range['from'])->format($range['format']), Carbon::createFromFormat('Y-m-d', $range['to'])->format($range['format']), $range['unit'], $range['format'], 0);

        $clicksMap = Stat::select([
                DB::raw("date_format(CONVERT_TZ(`created_at`, '" . CarbonTimeZone::create(config('app.timezone'))->toOffsetName() . "', '" . CarbonTimeZone::create($request->user()->timezone ?? config('app.timezone'))->toOffsetName() . "'), '" . str_replace(['Y', 'm', 'd', 'H'], ['%Y', '%m', '%d', '%H'], $range['format']) . "') as `date_result`, COUNT(*) as `aggregate`")
            ])
            ->where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->groupBy('date_result')
            ->orderBy('date_result', 'asc')
            ->get()
            ->mapWithKeys(function ($row) use ($request, $range) {
                return [strval(Carbon::createFromFormat($range['format'], $row->date_result)->format($range['format'])) => $row->aggregate];
            })->all();

        // Merge the results with the pre-calculated possible time range
        $clicksMap = array_replace($rangeMap, $clicksMap);

        $totalClicks = 0;
        foreach ($clicksMap as $value) {
            $totalClicks = $totalClicks + $value;
        }

        $totalClicksOld = Stat::where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from_old'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to_old'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])->count();

        $referrers = $this->getReferrers($request, $link, $range, null, null, 'count', 'desc')
            ->limit(5)
            ->get();

        $countries = $this->getCountries($request, $link, $range, null, null, 'count', 'desc')
            ->limit(5)
            ->get();

        $browsers = $this->getBrowsers($request, $link, $range, null, null, 'count', 'desc')
            ->limit(5)
            ->get();

        $operatingSystems = $this->getOperatingSystems($request, $link, $range, null, null, 'count', 'desc')
            ->limit(5)
            ->get();

        return view('stats.container', ['view' => 'overview', 'link' => $link, 'now' => $now, 'range' => $range, 'referrers' => $referrers, 'clicksMap' => $clicksMap, 'countries' => $countries, 'browsers' => $browsers, 'operatingSystems' => $operatingSystems, 'totalClicks' => $totalClicks, 'totalClicksOld' => $totalClicksOld]);
    }

    /**
     * Show the Referrers stats page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function referrers(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $now = Carbon::now();
        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $total = Stat::selectRaw('COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->first();

        $referrers = $this->getReferrers($request, $link, $range, $search, $searchBy, $sortBy, $sort)
            ->paginate($perPage)
            ->appends(['from' => $range['from'], 'to' => $range['to'], 'search' => $search, 'search_by' => $searchBy, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('stats.container', ['view' => 'referrers', 'link' => $link, 'now' => $now, 'range' => $range, 'export' => 'stats.export.referrers', 'referrers' => $referrers, 'total' => $total]);
    }

    /**
     * Show the Countries stats page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function countries(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $now = Carbon::now();
        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $total = Stat::selectRaw('COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->first();

        $countriesChart = $this->getCountries($request, $link, $range, $search, $searchBy, $sortBy, $sort)
            ->get();

        $countries = $this->getCountries($request, $link, $range, $search, $searchBy, $sortBy, $sort)
            ->paginate($perPage)
            ->appends(['from' => $range['from'], 'to' => $range['to'], 'search' => $search, 'search_by' => $searchBy, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('stats.container', ['view' => 'countries', 'link' => $link, 'now' => $now, 'range' => $range, 'export' => 'stats.export.countries', 'countries' => $countries, 'countriesChart' => $countriesChart, 'total' => $total]);
    }

    /**
     * Show the Cities stats page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function cities(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $now = Carbon::now();
        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $total = Stat::selectRaw('COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->first();

        $cities = $this->getCities($request, $link, $range, $search, $searchBy, $sortBy, $sort)
            ->paginate($perPage)
            ->appends(['from' => $range['from'], 'to' => $range['to'], 'search' => $search, 'search_by' => $searchBy, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('stats.container', ['view' => 'cities', 'link' => $link, 'now' => $now, 'range' => $range, 'export' => 'stats.export.cities', 'cities' => $cities, 'total' => $total]);
    }

    /**
     * Show the Languages stats page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function languages(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $now = Carbon::now();
        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $total = Stat::selectRaw('COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->first();

        $languages = $this->getLanguages($request, $link, $range, $search, $searchBy, $sortBy, $sort)
            ->paginate($perPage)
            ->appends(['from' => $range['from'], 'to' => $range['to'], 'search' => $search, 'search_by' => $searchBy, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('stats.container', ['view' => 'languages', 'link' => $link, 'now' => $now, 'range' => $range, 'export' => 'stats.export.languages', 'languages' => $languages, 'total' => $total]);
    }

    /**
     * Show the Operating Systems stats page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function operatingSystems(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $now = Carbon::now();
        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $total = Stat::selectRaw('COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->first();

        $operatingSystems = $this->getOperatingSystems($request, $link, $range, $search, $searchBy, $sortBy, $sort)
            ->paginate($perPage)
            ->appends(['from' => $range['from'], 'to' => $range['to'], 'search' => $search, 'search_by' => $searchBy, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('stats.container', ['view' => 'operating-systems', 'link' => $link, 'now' => $now, 'range' => $range, 'export' => 'stats.export.operating_systems', 'operatingSystems' => $operatingSystems, 'total' => $total]);
    }

    /**
     * Show the Browsers stats page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function browsers(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $now = Carbon::now();
        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $total = Stat::selectRaw('COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->first();

        $browsers = $this->getBrowsers($request, $link, $range, $search, $searchBy, $sortBy, $sort)
            ->paginate($perPage)
            ->appends(['from' => $range['from'], 'to' => $range['to'], 'search' => $search, 'search_by' => $searchBy, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('stats.container', ['view' => 'browsers', 'link' => $link, 'now' => $now, 'range' => $range, 'export' => 'stats.export.browsers', 'browsers' => $browsers, 'total' => $total]);
    }

    /**
     * Show the Devices stats page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function devices(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $now = Carbon::now();
        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $total = Stat::selectRaw('COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->first();

        $devices = $this->getDevices($request, $link, $range, $search, $searchBy, $sortBy, $sort)
            ->paginate($perPage)
            ->appends(['from' => $range['from'], 'to' => $range['to'], 'search' => $search, 'search_by' => $searchBy, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('stats.container', ['view' => 'devices', 'link' => $link, 'now' => $now, 'range' => $range, 'export' => 'stats.export.devices', 'devices' => $devices, 'total' => $total]);
    }

    /**
     * Export the Referrers stats.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|CSV\Writer
     * @throws CSV\CannotInsertRecord
     */
    public function exportReferrers(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';

        return $this->exportCSV($request, $link, __('Referrers'), $range, __('URL'), __('Clicks'), $this->getReferrers($request, $link, $range, $search, $searchBy, $sortBy, $sort)->get());
    }

    /**
     * Export the Countries stats.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|CSV\Writer
     * @throws CSV\CannotInsertRecord
     */
    public function exportCountries(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';

        return $this->exportCSV($request, $link, __('Countries'), $range, __('Name'), __('Clicks'), $this->getCountries($request, $link, $range, $search, $searchBy, $sortBy, $sort)->get());
    }

    /**
     * Export the Cities stats.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|CSV\Writer
     * @throws CSV\CannotInsertRecord
     */
    public function exportCities(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';

        return $this->exportCSV($request, $link, __('Cities'), $range, __('Name'), __('Clicks'), $this->getCities($request, $link, $range, $search, $searchBy, $sortBy, $sort)->get());
    }

    /**
     * Export the Languages stats.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|CSV\Writer
     * @throws CSV\CannotInsertRecord
     */
    public function exportLanguages(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';

        return $this->exportCSV($request, $link, __('Languages'), $range, __('Name'), __('Clicks'), $this->getLanguages($request, $link, $range, $search, $searchBy, $sortBy, $sort)->get());
    }

    /**
     * Export the Operating Systems stats.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|CSV\Writer
     * @throws CSV\CannotInsertRecord
     */
    public function exportOperatingSystems(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';

        return $this->exportCSV($request, $link, __('Operating systems'), $range, __('Name'), __('Clicks'), $this->getOperatingSystems($request, $link, $range, $search, $searchBy, $sortBy, $sort)->get());
    }

    /**
     * Export the Browsers stats.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|CSV\Writer
     * @throws CSV\CannotInsertRecord
     */
    public function exportBrowsers(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';

        return $this->exportCSV($request, $link, __('Browsers'), $range, __('Name'), __('Clicks'), $this->getBrowsers($request, $link, $range, $search, $searchBy, $sortBy, $sort)->get());
    }

    /**
     * Export the Devices stats.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|CSV\Writer
     * @throws CSV\CannotInsertRecord
     */
    public function exportDevices(Request $request, $id)
    {
        $link = Link::where([['id', '=', $id], ['user_id', '<>', 0]])->firstOrFail();

        if ($this->guard($link)) {
            return view('stats.password', ['link' => $link]);
        }

        $range = $this->range($link->created_at);
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['value']) ? $request->input('search_by') : 'value';
        $sortBy = in_array($request->input('sort_by'), ['count', 'value']) ? $request->input('sort_by') : 'count';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        
        return $this->exportCSV($request, $link, __('Devices'), $range, __('Type'), __('Clicks'), $this->getDevices($request, $link, $range, $search, $searchBy, $sortBy, $sort)->get());
    }

    /**
     * Get the Referrers.
     *
     * @param $request
     * @param $link
     * @param $range
     * @param null $search
     * @param null $sort
     * @return mixed
     */
    private function getReferrers($request, $link, $range, $search = null, $searchBy = null, $sortBy = null, $sort = null)
    {
        return Stat::selectRaw('`referrer` as `value`, COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->search($search, 'referrer');
            })
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->groupBy('value')
            ->orderBy($sortBy, $sort);
    }

    /**
     * Get the Countries.
     *
     * @param $request
     * @param $link
     * @param $range
     * @param null $search
     * @param null $sort
     * @return mixed
     */
    private function getCountries($request, $link, $range, $search = null, $searchBy = null, $sortBy = null, $sort = null)
    {
        return Stat::selectRaw('`country` as `value`, COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->search($search, 'country');
            })
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->groupBy('value')
            ->orderBy($sortBy, $sort);
    }

    /**
     * Get the Cities.
     *
     * @param $request
     * @param $link
     * @param $range
     * @param null $search
     * @param null $sort
     * @return mixed
     */
    private function getCities($request, $link, $range, $search = null, $searchBy = null, $sortBy = null, $sort = null)
    {
        return Stat::selectRaw('`city` as `value`, COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->search($search, 'city');
            })
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->groupBy('value')
            ->orderBy($sortBy, $sort);
    }

    /**
     * Get the Languages.
     *
     * @param $request
     * @param $link
     * @param $range
     * @param null $search
     * @param null $sort
     * @return mixed
     */
    private function getLanguages($request, $link, $range, $search = null, $searchBy = null, $sortBy = null, $sort = null)
    {
        return Stat::selectRaw('`language` as `value`, COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->search($search, 'language');
            })
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->groupBy('value')
            ->orderBy($sortBy, $sort);
    }

    /**
     * Get the Operating Systems.
     *
     * @param $request
     * @param $link
     * @param $range
     * @param null $search
     * @param null $sort
     * @return mixed
     */
    private function getOperatingSystems($request, $link, $range, $search = null, $searchBy = null, $sortBy = null, $sort = null)
    {
        return Stat::selectRaw('`operating_system` as `value`, COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->search($search, 'operating_system');
            })
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->groupBy('value')
            ->orderBy($sortBy, $sort);
    }

    /**
     * Get the Browsers.
     *
     * @param $request
     * @param $link
     * @param $range
     * @param null $search
     * @param null $sort
     * @return mixed
     */
    private function getBrowsers($request, $link, $range, $search = null, $searchBy = null, $sortBy = null, $sort = null)
    {
        return Stat::selectRaw('`browser` as `value`, COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->search($search, 'browser');
            })
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->groupBy('value')
            ->orderBy($sortBy, $sort);
    }

    /**
     * Get the Devices.
     *
     * @param $request
     * @param $link
     * @param $range
     * @param null $search
     * @param null $sort
     * @return mixed
     */
    private function getDevices($request, $link, $range, $search = null, $searchBy = null, $sortBy = null, $sort = null)
    {
        return Stat::selectRaw('`device` as `value`, COUNT(1) as `count`')
            ->where('link_id', '=', $link->id)
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->search($search, 'device');
            })
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->groupBy('value')
            ->orderBy($sortBy, $sort);
    }

    /**
     * Export data in CSV format.
     *
     * @param $request
     * @param $link
     * @param $title
     * @param $range
     * @param $name
     * @param $count
     * @param $results
     * @return CSV\Writer
     * @throws CSV\CannotInsertRecord
     */
    private function exportCSV($request, $link, $title, $range, $name, $count, $results)
    {
        if ($link->user->cannot('dataExport', ['App\Models\User'])) {
            abort(403);
        }

        $now = Carbon::now();
        
        $content = CSV\Writer::createFromFileObject(new \SplTempFileObject);

        // Generate the header
        $content->insertOne([__('URL'), str_replace(['http://', 'https://'], '', (str_replace(['http://', 'https://'], '', $link->domain->url ?? config('app.url'))) . '/' . $link->alias)]);
        $content->insertOne([__('Type'), $title]);
        $content->insertOne([__('Interval'), $range['from'] . ' - ' . $range['to']]);
        $content->insertOne([__('Date'), $now->tz($request->user()->timezone ?? config('app.timezone'))->format(__('Y-m-d') . ' H:i:s')]);
        $content->insertOne([__('URL'), $request->fullUrl()]);
        $content->insertOne([__(' ')]);

        // Generate the summary
        $content->insertOne([__('Clicks'), Stat::where('link_id', '=', $link->id)
            ->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m-d', $range['from'], $request->user()->timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('Y-m-d', $range['to'], $request->user()->timezone ?? config('app.timezone'))->endOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            ])
            ->count()]);
        $content->insertOne([__(' ')]);

        // Generate the content
        $content->insertOne([__($name), __($count)]);
        foreach ($results as $result) {
            $content->insertOne($result->toArray());
        }

        // Set the output BOM
        $content->setOutputBOM(CSV\Reader::BOM_UTF8);

        return response((string)$content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Transfer-Encoding' => 'binary',
            'Content-Disposition' => 'attachment; filename="' . formatTitle([$link->alias, str_replace(['http://', 'https://'], '', ($link->domain->url ?? config('app.url'))), $title, $range['from'], $range['to'], config('settings.title')]) . '.csv"'
        ]);
    }

    /**
     * Validate the Model's password.
     *
     * @param ValidateLinkStatsPasswordRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function validatePassword(ValidateLinkStatsPasswordRequest $request, $id)
    {
        session([md5($id) => true]);
        return redirect()->back();
    }

    /**
     * Guard the Model.
     *
     * @param $model
     * @return bool
     */
    private function guard($model)
    {
        // If the model is not set to public
        if($model->privacy !== 0) {
            $user = Auth::user();

            // If the model's privacy is set to private
            if ($model->privacy == 1) {
                // If the user is not authenticated
                // Or if the user is not the owner of the model and not an admin
                if ($user == null || $user->id != $model->user_id && $user->role != 1) {
                    abort(403);
                }
            }

            // If the model's privacy is set to password
            if ($model->privacy == 2) {
                // If there's no password validation in the current session
                if (!session(md5($model->id))) {
                    // If the user is not authenticated
                    // Or if the user is not the owner of the link and not an admin
                    if ($user == null || $user->id != $model->user_id && $user->role != 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
