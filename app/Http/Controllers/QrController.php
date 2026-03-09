<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class QrController extends Controller
{
    /**
     * Display the QR code index page for a specific link.
     */
    public function index(Request $request, $id)
    {
        $link = Link::findOrFail($id);

        return view('qr.container', [
            'view' => 'index',
            'link' => $link,
        ]);
    }

    /**
     * Show the form for creating a new QR code.
     */
    public function create()
    {
        return view('qr.container', [
            'view' => 'create',
        ]);
    }

    /**
     * Store a newly created link for the QR code with name and type.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:link'], // Restricted to link type
        ]);

        $link = new Link;
        $link->type = 'link'; // Fixed to link type
        $link->mode = 'dynamic'; // Default to dynamic only
        $link->user_id = auth()->id() ?? null;
        $link->domain_id = 0; // Default domain

        // Initialize data for link type with empty URL
        $link->data = ['url' => '']; // Will be set in builder/generate
        $link->url = ''; // Initialize url column

        // Set the name as a custom field or title
        $link->title = $request->input('name');

        $link->alias = $this->generateAlias();
        while (Link::where('domain_id', $link->domain_id)->where('alias', $link->alias)->exists()) {
            $link->alias = $this->generateAlias();
        }

        $link->save();

        return redirect()->route('qr.builder', $link->id);
    }

    /**
     * Generate a unique alias for the link.
     */
    protected function generateAlias(int $len = 6): string
    {
        do {
            $code = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, $len);
        } while (Link::where('alias', $code)->exists());
        return $code;
    }

    /**
     * Show the builder page with domains and precomputed QR data.
     */
    public function builder($id)
    {
        $link = Link::findOrFail($id);

        $defaults = [
            'format' => 'png',
            'size'   => 512,
            'color'  => '#000000',
            'ecc'    => 'M',
            'margin' => 1,
            'logo'   => null,
        ];

        $styles = array_merge($defaults, (array)($link->qr_styles ?? []));
        $domains = Domain::whereIn('user_id', [0, auth()->id()])->orderBy('name')->get();

        // Compute initial QR data (short URL for preview, not the destination)
        $qrData = url($link->alias) . (parse_url(url($link->alias), PHP_URL_QUERY) ? '&' : '?') . 'src=qr';
        if (empty($qrData)) {
            $qrData = 'about:blank'; // Fallback for preview
        }

        return view('qr.container', [
            'view'    => 'builder',
            'link'    => $link,
            'styles'  => $styles,
            'domains' => $domains,
            'qrData'  => $qrData,
        ]);
    }

    /**
     * Generate or regenerate the QR code with the provided options.
     */
    public function generate($id, Request $request)
    {
        $link = Link::findOrFail($id);

        $baseValidation = [
            'size'      => 'integer|min:128|max:2048',
            'color'     => 'regex:/^#([A-Fa-f0-9]{6})$/',
            'bg'        => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/',
            'ecc'       => 'in:L,M,Q,H',
            'margin'    => 'integer|min:0|max:8',
            'domain_id' => 'nullable|integer|exists:domains,id',
            'url'       => 'required|url', // Validation for link type
            'logo'      => 'nullable|image|max:2048', // Logo file validation
            'styled_data_url'     => 'nullable|string',
            'styled_options_json' => 'nullable|string',
        ];

        $request->validate($baseValidation);

        $fields = $request->only(['url']);
        if ($request->hasFile('logo')) {
            if (isset($link->qr_logo_path) && Storage::disk('public')->exists($link->qr_logo_path)) {
                Storage::disk('public')->delete($link->qr_logo_path);
            }
            $logoPath = $request->file('logo')->store('qr-logos', 'public');
            $link->qr_logo_path = $logoPath;
        }

        $domainId = $request->input('domain_id', $link->domain_id);
        if ($domainId && $domainId != $link->domain_id) {
            $domain = Domain::find($domainId);
            if ($domain && ($domain->user_id == 0 || $domain->user_id == auth()->id())) {
                $link->domain_id = $domainId;
            } else {
                return back()->withErrors(['domain_id' => 'Invalid domain selection. Choose a different domain or change alias.']);
            }
        }

        // Update both url column and data array
        $link->url = $fields['url']; // Set the url column for redirect compatibility
        $link->data = array_merge($link->data ?? [], $fields); // Keep data array for consistency

        // Inputs
        $format = 'png'; // Hardcoded
        $size   = (int) $request->input('size', 512);
        $color  = $request->input('color', '#000000');
        $bg     = $request->input('bg', '#FFFFFF');
        $ecc    = $request->input('ecc', 'M');
        $margin = (int) $request->input('margin', 1);

        // If domain relation was preloaded, drop it so accessor recomputes
        if ($link->relationLoaded('domain')) {
            $link->unsetRelation('domain');
        }

        // Use the destination URL from the form for the QR target
        $target = $link->url;

        // Save image
        $filePath = "qr/{$link->id}.{$format}";

        if ($request->filled('styled_data_url') && preg_match('#^data:image/(png|svg\+xml);base64,#', $request->styled_data_url)) {
            $decoded = base64_decode(substr($request->styled_data_url, strpos($request->styled_data_url, ',') + 1));
            Storage::disk('public')->put($filePath, $decoded);
        } else {
            // Fallback: server-side basic QR with logo if provided
            [$r, $g, $b] = sscanf($color, "#%02x%02x%02x");
            [$br, $bg, $bb] = sscanf($bg, "#%02x%02x%02x");
            $qrBinary = QrCode::format($format)
                ->size($size)
                ->margin($margin)
                ->errorCorrection($ecc)
                ->color($r, $g, $b)
                ->backgroundColor($br, $bg, $bb)
                ->merge($link->qr_logo_path ?? null, 0.3) // Merge logo if exists
                ->generate($target);
            Storage::disk('public')->put($filePath, $qrBinary);
        }

        // Persist data and styles
        $prevStyles = is_array($link->qr_styles) ? $link->qr_styles : [];
        $newStyles = array_filter([
            'format' => $format,
            'size'   => $size,
            'color'  => $color,
            'bg'     => $bg,
            'ecc'    => $ecc,
            'margin' => $margin,
        ], fn ($v) => !is_null($v) && $v !== '');

        if ($request->filled('styled_options_json')) {
            $newStyles['styled'] = $request->input('styled_options_json');
        }

        $link->qr_styles = array_merge($prevStyles, $newStyles);
        $link->qr_generated = true;
        $link->qr_path = $filePath;
        $link->save();

        return back()
            ->with('status', 'QR updated.')
            ->with('qr_url', Storage::disk('public')->url($filePath));
    }

    /**
     * Display the QR code library.
     */
    public function library(Request $request)
    {
        $q    = trim($request->query('q', ''));
        $sort = $request->query('sort', 'created_at_desc');

        $links = Link::query()
            ->where('type', 'link') // Filter for link type only
            ->where('qr_generated', true)
            ->when(!(auth()->check() && auth()->user()->role == 1), function ($qry) {
                $qry->where('user_id', auth()->id());
            })
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                       ->orWhere('data->url', 'like', "%{$q}%")
                       ->orWhere('alias', 'like', "%{$q}%");
                });
            });

        $sort === 'created_at_asc'
            ? $links->orderBy('created_at', 'asc')
            : $links->orderBy('created_at', 'desc');

        $links = $links->paginate(25)->appends($request->query());

        return view('qr.container', [
            'view'  => 'list',
            'links' => $links,
            'q'     => $q,
            'sort'  => $sort,
        ]);
    }

    /**
     * Download the generated QR code.
     */
    public function download($id, Request $request)
    {
        $link = Link::findOrFail($id);

        $format = $request->query('format', $link->qr_styles['format'] ?? 'png');
        $path = $link->qr_path;

        if (!$path || !Storage::disk('public')->exists($path) || !str_ends_with($path, ".{$format}")) {
            return redirect()->route('qr.builder', $link->id)
                ->with('error', 'Generate the QR in this format first.');
        }

        return response()->download(
            Storage::disk('public')->path($path),
            "qr-{$link->id}.{$format}"
        );
    }
}