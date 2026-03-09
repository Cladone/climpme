@section('site_title', formatTitle([__('Builder'), __('QR Code'), config('settings.title')]))

@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => request()->is('admin/*') ? route('admin.dashboard') : route('dashboard'),
     'title' => request()->is('admin/*') ? __('Admin') : __('Home')],
    ['url' => route('qr.index'), 'title' => __('QR Codes')],
    ['title' => __('Builder')],
]])

@php
    // Prefer model accessor; fall back to app URL + alias
    try { $shortUrl = $link->shortUrl; } catch (\Throwable $e) { $shortUrl = url($link->alias); }
    $shortUrlWithSrc = $shortUrl . (parse_url($shortUrl, PHP_URL_QUERY) ? '&' : '?') . 'src=qr';

    // If you stored styled options before, pass them down so we can rehydrate the UI
    $styledOptionsJson = isset($styles['styled'])
        ? (is_string($styles['styled']) ? $styles['styled'] : json_encode($styles['styled']))
        : null;

    // Parse styled JSON and merge into $styles for repopulation
    $styled = $styledOptionsJson ? json_decode($styledOptionsJson, true) : [];
    $styles = array_merge($styles, $styled ?? []);

    // Check if QR has been generated (e.g., based on styled data or logo existence)
    $isGenerated = isset($link->qr_logo_path) && Storage::disk('public')->exists($link->qr_logo_path) || !empty($styledOptionsJson);
@endphp

<style>
    /* Ensure the row has a relative position for sticky children */
    .row {
        position: relative;
    }

    /* Make the preview column sticky */
    .col-md-6:first-child {
        position: -webkit-sticky; /* Safari */
        position: sticky;
        top: 20px; /* Offset from the top, adjust as needed */
        align-self: flex-start;
        max-height: calc(100vh - 100px); /* Prevent overflow, adjust based on header/footer */
        overflow-y: auto; /* Add scroll if content exceeds height */
        z-index: 1; /* Ensure it stays above other content */
    }

    /* Ensure the card body has proper height */
    .col-md-6:first-child .card-body {
        min-height: 360px; /* Match existing style */
        display: flex;
        flex-direction: column;
        justify-content: center;
        width: 100%; /* Full width */
        box-sizing: border-box; /* Include padding in width */
        overflow: hidden; /* Prevent child overflow */
    }

    /* Adjust the customization column to account for sticky behavior */
    .col-md-6:last-child {
        padding-left: 15px; /* Maintain spacing */
    }

    /* Mobile adjustments */
    @media (max-width: 768px) {
        .col-md-6:first-child {
            position: static; /* Disable sticky on mobile for better usability */
            top: 0;
            width: 100%; /* Full width */
        }

        /* Ensure the preview card is centered within the column */
        .col-md-6:first-child .card {
            margin-left: auto;
            margin-right: auto;
        }

        .col-md-6:last-child {
            margin-top: 20px; /* Spacing between stacked sections */
        }

        /* Ensure the card fits the container */
        .col-md-6:first-child .card {
            width: 100%; /* Full width */
            max-width: 100%; /* Prevent overflow */
        }

        /* Constrain the QR preview (canvas or SVG) */
        #qr-preview {
            width: clamp(240px, 84vw, 420px); /* squeeze a bit on phones */
            aspect-ratio: 1 / 1;              /* keep square */
            margin: 0 auto;                   /* center within card */
            box-sizing: border-box;           /* include padding in width */
            overflow: hidden;                  /* prevent bleed */
        }

        #qr-preview canvas,
        #qr-preview svg {
            width: 100% !important;
            height: 100% !important;     /* fill the square */
            max-width: 100% !important;
            max-height: 100% !important;
        }

        /* Hide alias on mobile */
        .d-flex.align-items-center.justify-content-center.mt-3 .text-muted,
        .d-flex.align-items-center.justify-content-center.mt-3 code {
            display: none;
        }

        /* Add padding between preview and customize sections on mobile */
        .row {
            padding-bottom: 20px; /* Space between sections */
        }
    }
</style>

<div class="row">
    {{-- LEFT: Live Preview (Sticky) --}}
    <div class="col-md-6">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">{{ __('Preview') }}</h5>
            <div class="text-right small">
                <!-- No alias or Open short link here anymore -->
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-center" id="preview" style="min-height: 360px;">
                {{-- Single live preview (qr-code-styling) --}}
                <div id="qr-preview"></div>
            </div>
        </div>

        {{-- Download dropdown, alias, and open short link --}}
        <div class="d-flex align-items-center justify-content-center mt-3" id="download-area" style="gap: 1rem;">
            <div class="d-flex align-items-center" style="gap: 0.5rem;">
                <select id="download-format" class="custom-select custom-select-sm">
                    <option value="png">PNG</option>
                    <option value="svg">SVG</option>
                </select>
                <button id="download-btn" class="btn btn-outline-secondary btn-sm">
                    {{ __('Download') }}
                </button>
            </div>
            @if(isset($link->alias))
                <span class="text-muted small">{{ __('Alias:') }}</span>
                <code class="text-muted small">{{ $link->alias }}</code>
            @endif
            <a class="btn btn-outline-secondary btn-sm" href="{{ $shortUrl }}" target="_blank" rel="nofollow noopener">
                {{ __('Open short link') }}
            </a>
        </div>
    </div>

    {{-- RIGHT: Customize --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ __('Customize') }}</h5>
            </div>

            <div class="card-body">
                <form id="qr-form" action="{{ route('qr.generate', $link->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Domain selector --}}
                    @isset($domains)
                        <div class="form-group">
                            <label for="i-domain">{{ __('Domain') }}</label>
                            <select id="i-domain" name="domain_id" class="custom-select">
                                @foreach($domains as $d)
                                    <option value="{{ $d->id }}" @selected($link->domain_id == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                {{ __('Choose the domain for the short URL. The QR will point to this domain + your alias.') }}
                            </small>
                            @error('domain_id')
                                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    @endisset

                    {{-- Link-specific field (only type retained) --}}
                    <div class="form-group">
                        <label for="i-url">{{ __('Destination URL') }}</label>
                        <input type="url" id="i-url" name="url" class="form-control" placeholder="https://example.com" value="{{ isset($link->data['url']) ? $link->data['url'] : '' }}">
                        <small class="text-muted">{{ __('Enter the URL this QR code will link to.') }}</small>
                        @error('url')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Basics (full width until Style) --}}
                    <div class="form-group">
                        <label for="i-size">{{ __('Size (px)') }}</label>
                        <input type="number" id="i-size" name="size" class="form-control" min="128" max="2048" value="{{ $styles['size'] ?? 512 }}">
                    </div>

                    <div class="form-group">
                        <label for="i-color">{{ __('Foreground color') }}</label>
                        <input type="color" id="i-color" name="color" class="form-control" value="{{ $styles['color'] ?? '#000000' }}">
                    </div>

                    <div class="form-group">
                        <label for="i-bg">{{ __('Background color') }}</label>
                        <input type="color" id="i-bg" name="bg" class="form-control" value="{{ $styles['bg'] ?? '#FFFFFF' }}">
                    </div>

                    <div class="form-group">
                        <label for="i-ecc">{{ __('Error correction') }}</label>
                        <select class="custom-select" id="i-ecc" name="ecc">
                            @foreach(['L','M','Q','H'] as $ecc)
                                <option value="{{ $ecc }}" @selected(($styles['ecc'] ?? 'M')===$ecc)>{{ $ecc }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="i-margin">{{ __('Margin / Quiet zone') }}</label>
                        <input type="number" id="i-margin" name="margin" class="form-control" min="0" max="8" value="{{ $styles['margin'] ?? 1 }}">
                    </div>

                    {{-- Advanced styling (two-column layout) --}}
                    <hr>
                    <h6 class="mb-2">{{ __('Style') }}</h6>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="i-shape">{{ __('Module shape') }}</label>
                            <select id="i-shape" class="custom-select">
                                @foreach(['square','dots','rounded','classy','classy-rounded','extra-rounded'] as $sh)
                                    <option value="{{ $sh }}" @selected(($styles['shape'] ?? 'square')===$sh)>{{ ucfirst(str_replace('-', ' ', $sh)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label for="i-radius">{{ __('Module radius (0–1)') }}</label>
                            <input type="number" id="i-radius" class="form-control" step="0.05" min="0" max="1" value="{{ $styles['radius'] ?? 0.0 }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="i-eye-inner">{{ __('Inner eye style') }}</label>
                            <select id="i-eye-inner" class="custom-select">
                                @foreach(['square','dots'] as $in)
                                    <option value="{{ $in }}" @selected(($styles['inner'] ?? 'square')===$in)>{{ ucfirst($in) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label for="i-eye-outer">{{ __('Outer eye style') }}</label>
                            <select id="i-eye-outer" class="custom-select">
                                @foreach(['square','dots','extra-rounded'] as $out)
                                    <option value="{{ $out }}" @selected(($styles['outer'] ?? 'square')===$out)>{{ ucfirst(str_replace('-', ' ', $out)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="i-gradient-type">{{ __('Gradient (optional)') }}</label>
                            <select id="i-gradient-type" class="custom-select">
                                <option value="none" @selected(($styles['gradient']['type'] ?? 'none') === 'none')>None</option>
                                <option value="linear" @selected(($styles['gradient']['type'] ?? 'none') === 'linear')>Linear</option>
                                <option value="radial" @selected(($styles['gradient']['type'] ?? 'none') === 'radial')>Radial</option>
                            </select>
                        </div>
                        <div class="form-group col-6" id="gradient-stops" style="display:none;">
                            <label>{{ __('Gradient stops') }}</label>
                            <div class="d-flex" style="gap:.5rem;">
                                <input type="color" id="i-grad-from" value="{{ $styles['gradient']['from'] ?? '#000000' }}" class="form-control">
                                <input type="color" id="i-grad-to" value="{{ $styles['gradient']['to'] ?? '#00AA55' }}" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="i-logo-preview">{{ __('Logo (optional)') }}</label>
                        <input type="file" name="logo" id="i-logo-preview" class="form-control-file" accept="image/*">
                        <small class="text-muted d-block mt-1">
                            {{ __('Logo is applied in the live preview and saved with the QR when you click Generate / Update.') }}
                        </small>
                        @if(isset($link->qr_logo_path) && Storage::disk('public')->exists($link->qr_logo_path))
                            <div class="mt-2">
                                <p class="small text-muted">{{ __('Current logo:') }}</p>
                                <img src="{{ Storage::disk('public')->url($link->qr_logo_path) }}" alt="Current logo" style="max-width: 100px; max-height: 100px;">
                            </div>
                        @endif
                        @error('logo')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Hidden fields populated by JS just before submit --}}
                    <input type="hidden" name="styled_data_url" id="styled_data_url">
                    <input type="hidden" name="styled_options_json" id="styled_options_json">
                    <input type="hidden" name="is_generated" id="is_generated" value="{{ $isGenerated ? '1' : '0' }}">

                    <button id="btn-generate" type="submit" class="btn btn-primary">
                        {{ $isGenerated ? __('Update QR') : __('Generate QR') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Single preview implementation using qr-code-styling --}}
<script src="https://unpkg.com/qr-code-styling@1.6.0/lib/qr-code-styling.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const shortUrl = @json($shortUrlWithSrc ?? ($shortUrl ?? ''));
    const preview = document.getElementById('qr-preview');
    if (!preview) return;

    const alias = @json($link->alias ?? 'qr-code');
    const get = id => document.getElementById(id);
    const el = {
        form: get('qr-form'),
        btn: get('btn-generate'),
        size: get('i-size'),
        color: get('i-color'),
        bg: get('i-bg'),
        ecc: get('i-ecc'),
        margin: get('i-margin'),
        shape: get('i-shape'),
        eyeOuter: get('i-eye-outer'),
        eyeInner: get('i-eye-inner'),
        gradType: get('i-gradient-type'),
        gradFrom: get('i-grad-from'),
        gradTo: get('i-grad-to'),
        gradWrap: get('gradient-stops'),
        logoPreview: get('i-logo-preview'),
        dataUrl: get('styled_data_url'),
        optionsJson: get('styled_options_json'),
        downloadFormat: get('download-format'),
        downloadBtn: get('download-btn'),
        isGenerated: get('is_generated'),
    };

    // Guard: all critical elements must exist
    const required = ['form','size','color','bg','ecc','margin','shape','eyeOuter','eyeInner','gradType','gradFrom','gradTo','dataUrl','optionsJson','downloadFormat','downloadBtn', 'isGenerated'];
    for (const k of required) {
        if (!el[k]) { console.error('QR builder missing element:', k); return; }
    }

    // Initial visibility for gradient stops
    if (el.gradType.value !== 'none') {
        if (el.gradWrap) el.gradWrap.style.display = '';
    }

    const SUPPORTED = {
        dots: ['square','dots','rounded','classy','classy-rounded','extra-rounded'],
        sq:   ['square','dots','extra-rounded'],
        dot:  ['square','dots'],
        grad: ['none','linear','radial']
    };
    const pick = (v, list) => list.includes(v) ? v : list[0];

    // Initial logo if stored
    const storedLogo = @json(isset($link->qr_logo_path) && Storage::disk('public')->exists($link->qr_logo_path) ? Storage::disk('public')->url($link->qr_logo_path) : null);

    // Function to calculate QR size dynamically, accounting for margin
    function getQRSize() {
        const isMobile = window.innerWidth <= 768;
        const containerWidth = preview.parentElement.offsetWidth; // Get card-body width
        const padding = isMobile ? 30 : 0; // Account for 15px padding on each side on mobile
        const qrMargin = parseInt(el.margin.value || '4', 10) * 2; // Approximate margin impact (both sides)
        const userSize = parseInt(el.size.value || '512', 10);
        const maxSize = Math.min(containerWidth - padding - qrMargin, userSize); // Cap size, subtracting margin effect
        console.log('Container width:', containerWidth, 'QR Margin:', qrMargin, 'Calculated QR size:', maxSize); // Debug
        return maxSize; // Use calculated size
    }

    const qr = new QRCodeStyling({
        width: getQRSize(),
        height: getQRSize(),
        type: 'png', // Use PNG for preview (can switch to 'svg' if needed)
        data: shortUrl,
        margin: parseInt(el.margin.value || '4', 10), // Keep margin for breathing room
        qrOptions: { errorCorrectionLevel: el.ecc.value || 'M' },
        backgroundOptions: { color: el.bg.value || '#FFFFFF' }, // Default to white, adjust if red is intended
        dotsOptions: { color: el.color.value || '#000000', type: pick(el.shape.value, SUPPORTED.dots) },
        cornersSquareOptions: { type: pick(el.eyeOuter.value, SUPPORTED.sq), color: el.color.value || '#000000' },
        cornersDotOptions: { type: pick(el.eyeInner.value, SUPPORTED.dot), color: el.color.value || '#000000' },
        image: storedLogo,
        imageOptions: { crossOrigin: 'anonymous', margin: 0 }
    });
    qr.append(preview); // Append to the div

    function currentGradient() {
        const t = pick(el.gradType.value, SUPPORTED.grad);
        if (t === 'none') return null;
        return {
            type: t,
            colorStops: [
                { offset: 0, color: el.gradFrom.value || '#000000' },
                { offset: 1, color: el.gradTo.value || '#000000' }
            ]
        };
    }

    function updateQR() {
        const grad = currentGradient();
        if (el.gradWrap) el.gradWrap.style.display = (el.gradType.value === 'none') ? 'none' : '';
        const size = getQRSize();
        qr.update({
            width: size,
            height: size,
            type: 'png', // Use PNG for preview
            data: shortUrl,
            margin: parseInt(el.margin.value || '4', 10), // Keep margin for breathing room
            qrOptions: { errorCorrectionLevel: el.ecc.value || 'M' },
            backgroundOptions: { color: el.bg.value || '#FFFFFF' }, // Default to white
            dotsOptions: Object.assign(
                { color: el.color.value || '#000000', type: pick(el.shape.value, SUPPORTED.dots) },
                grad ? { gradient: grad } : {}
            ),
            cornersSquareOptions: Object.assign(
                { type: pick(el.eyeOuter.value, SUPPORTED.sq), color: el.color.value || '#000000' },
                grad ? { gradient: grad } : {}
            ),
            cornersDotOptions: Object.assign(
                { type: pick(el.eyeInner.value, SUPPORTED.dot), color: el.color.value || '#000000' },
                grad ? { gradient: grad } : {}
            )
        });
    }

    // Live preview logo
    if (el.logoPreview) {
        el.logoPreview.addEventListener('change', e => {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            qr.update({ image: url, imageOptions: { crossOrigin: 'anonymous', margin: 0 } });
        });
    }

    // Download handler
    el.downloadBtn.addEventListener('click', () => {
        const format = el.downloadFormat.value || 'png';
        qr.download({ name: `qr-${alias}`, extension: format });
    });

    // Bind changes
    [
        el.size, el.color, el.bg, el.ecc, el.margin,
        el.shape, el.eyeOuter, el.eyeInner, el.gradType, el.gradFrom, el.gradTo
    ].forEach(inp => inp && inp.addEventListener('input', updateQR));

    // Handle window resize to adjust QR size dynamically
    window.addEventListener('resize', updateQR);

    // Submit handler: export preview → set hidden fields → submit, update button text
    el.form.addEventListener('submit', async (ev) => {
        ev.preventDefault();

        // Persist options snapshot (helps keep selections after reload)
        const opts = {
            type: 'png', // Default for server-side
            size: parseInt(el.size.value || '512', 10),
            color: el.color.value,
            bg: el.bg.value,
            ecc: el.ecc.value,
            margin: parseInt(el.margin.value || '4', 10), // Keep margin
            shape: pick(el.shape.value, SUPPORTED.dots),
            outer: pick(el.eyeOuter.value, SUPPORTED.sq),
            inner: pick(el.eyeInner.value, SUPPORTED.dot),
            gradient: { type: pick(el.gradType.value, SUPPORTED.grad), from: el.gradFrom.value, to: el.gradTo.value }
        };
        el.optionsJson.value = JSON.stringify(opts);

        try {
            const blob = await qr.getRawData('png'); // Default to PNG for server
            const reader = new FileReader();
            reader.onloadend = () => {
                el.dataUrl.value = reader.result || ''; // data:image/png;base64,...
                // Update is_generated to 1 after first successful submission
                if (el.isGenerated.value === '0') {
                    el.isGenerated.value = '1';
                    el.btn.textContent = '{{ __('Update QR') }}';
                }
                el.form.submit(); // Continue normal POST
            };
            reader.readAsDataURL(blob);
        } catch (e) {
            console.error('qr.getRawData() failed, submitting without styled_data_url', e);
            el.dataUrl.value = '';
            // Update is_generated to 1 even on error for consistency
            if (el.isGenerated.value === '0') {
                el.isGenerated.value = '1';
                el.btn.textContent = '{{ __('Update QR') }}';
            }
            el.form.submit();
        }
    });

    // Initial render
    updateQR();
});
</script>