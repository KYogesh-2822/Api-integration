<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weather</title>
        <script src="https://js.stripe.com/v3/"></script>


    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background: #f1f5f9; }
        .maps-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .maps-row > section {
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        @media (max-width: 700px) {
            .maps-row { grid-template-columns: 1fr; }
        }
        .map-label {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 5;
            background: rgba(255,255,255,0.9);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .map-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: #e2e8f0;
            color: #64748b;
            font-size: 13px;
            text-align: center;
            padding: 16px;
        }
        .day-card { text-align: center; }
        .day-card .icon { width: 56px; height: 56px; }
        .temp-big { font-size: 3rem; font-weight: 600; line-height: 1; }
        .capitalize { text-transform: capitalize; }
    </style>
</head>
<body>

    <div class="container py-4" style="max-width: 760px;">

        <header class="d-flex align-items-center justify-content-between gap-3 mb-4">
            <h1 class="h3 m-0">Weather</h1>
            <form id="search-form" class="d-flex gap-2 position-relative" style="flex:1; max-width:420px;">
                <input id="search-input" type="text" class="form-control" placeholder="Search city, town, district…" autocomplete="off" />
                <button type="submit" class="btn btn-dark">Go</button>
                <div id="search-results" class="list-group position-absolute shadow-sm" style="top:100%; left:0; right:64px; z-index:10; display:none; margin-top:4px;"></div>
            </form>
        </header>


         <h1>Stripe Payment</h1>
        <p>Amount: $10.00</p>
        
        <!-- Stripe Elements container -->
        <div id="card-element" style="width: 300px; margin: 20px auto;"></div>
        <div id="message"></div>
        
        <button id="pay-button">Pay Now</button>


                    
            @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
            @elseif(session('success'))
            <div class="alert alert-green">{{ session('success') }}</div>
            @endif


        @auth

            @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}">
            @else
                <img src="{{ asset('images/images.png') }}" alt="default avatar">
            @endif
            <h1>User: {{ auth()->user()->name }}</h1>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            @endauth
            {{-- your existing email/password form here --}}

            <hr>
            <p class="text-center">Or continue with</p>

            <a href="{{ route('social.redirect', 'google') }}" class="btn btn-outline-danger w-100 mb-2"><img src="{{ url('/images/svg/icons8-google.svg') }}" width="20">
                Sign in with Google
            </a>

            <a href="{{ route('social.redirect', 'facebook') }}" class="btn btn-outline-primary w-100"><img src="{{ url('/images/svg/icons8-facebook.svg')}}" width="20"> 
                Sign in with Facebook
            </a>



        <section id="current-card" class="card shadow-sm mb-4">
            <div class="card-body">
                <p class="text-muted m-0">Loading current weather…</p>
            </div>
        </section>

        <section id="forecast-strip" class="row g-3 mb-4">
            <p class="text-muted col-12">Loading forecast…</p>
        </section>

        <div class="maps-row mb-3">
            <section id="map-leaflet" class="shadow-sm">
                <span class="map-label">OpenStreetMap</span>
            </section>
            <section id="map-google" class="shadow-sm">
                <span class="map-label">Google Maps</span>
                @if(! config('services.google_maps.key'))
                    <div class="map-placeholder">
                        curruntly disable.
                    </div>
                @endif
            </section>
        </div>

        <p id="status" class="text-muted small"></p>





         <a href="{{route('profile')}}">click here to test</a>

    </div>






    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @if(config('services.google_maps.key'))
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initGoogleMap">
        </script>
    @endif

    <script>
        // ---------- tiny helpers ----------
        const $ = (id) => document.getElementById(id);
        const setStatus = (msg) => { $('status').textContent = msg; };
        const iconUrl = (code) => `https://openweathermap.org/img/wn/${code}@2x.png`;

        async function fetchJson(url) {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`Request failed: ${res.status}`);
            return res.json();
        }

        // ---------- renderers ----------
        function renderCurrent(data) {
            $('current-card').innerHTML = `
                <div class="card-body">
                    <div class="text-muted">${data.city}</div>
                    <div class="d-flex align-items-center gap-3 mt-1">
                        <div class="temp-big">${Math.round(data.temperature)}°C</div>
                        <div class="text-muted capitalize">${data.condition}</div>
                    </div>
                </div>
            `;
        }

        function renderForecast(data) {
            const cards = data.days.map(d => `
                <div class="col">
                    <div class="card day-card shadow-sm h-100">
                        <div class="card-body p-2">
                            <div class="text-muted small">${d.date}</div>
                            <img class="icon" src="${iconUrl(d.icon)}" alt="${d.condition}" />
                            <div>${Math.round(d.temp_min)}° / ${Math.round(d.temp_max)}°</div>
                            <div class="text-muted small capitalize">${d.condition}</div>
                        </div>
                    </div>
                </div>
            `).join('');
            $('forecast-strip').innerHTML = cards;
        }

        // ---------- Leaflet map ----------
        let lMap, lMarker;
        function setLeafletMap(lat, lon, label) {
            if (!lMap) {
                lMap = L.map('map-leaflet').setView([lat, lon], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(lMap);
                lMarker = L.marker([lat, lon]).addTo(lMap);
            } else {
                lMap.setView([lat, lon], 10);
                lMarker.setLatLng([lat, lon]);
            }
            if (label) lMarker.bindPopup(label).openPopup();
        }

        // ---------- Google map ----------
        // The Google SDK loads asynchronously and calls window.initGoogleMap when ready.
        // If we get coords before the SDK is loaded, stash them in pendingCoords so
        // initGoogleMap can apply them on arrival.
        let gMap, gMarker, gReady = false, pendingCoords = null;

        window.initGoogleMap = () => {
            gReady = true;
            if (pendingCoords) {
                setGoogleMap(...pendingCoords);
                pendingCoords = null;
            }
        };

        function setGoogleMap(lat, lon, label) {
            if (!window.google || !gReady) {
                // SDK not loaded yet (or no API key configured) — remember coords
                pendingCoords = [lat, lon, label];
                return;
            }
            if (!gMap) {
                gMap = new google.maps.Map(document.getElementById('map-google'), {
                    center: { lat, lng: lon },
                    zoom: 10,
                    mapTypeControl: false,
                    streetViewControl: false,
                });
                gMarker = new google.maps.Marker({
                    position: { lat, lng: lon },
                    map: gMap,
                    title: label || '',
                });
            } else {
                gMap.setCenter({ lat, lng: lon });
                gMarker.setPosition({ lat, lng: lon });
                if (label) gMarker.setTitle(label);
            }
        }

        function setMaps(lat, lon, label) {
            setLeafletMap(lat, lon, label);
            setGoogleMap(lat, lon, label);
        }

        // ---------- loaders ----------
        async function loadByCoords(lat, lon) {
            try {
                setStatus('Loading…');
                const [current, forecast] = await Promise.all([
                    fetchJson(`/api/weather/coords?lat=${lat}&lon=${lon}`),
                    fetchJson(`/api/forecast/coords?lat=${lat}&lon=${lon}`),
                ]);
                renderCurrent(current);
                renderForecast(forecast);
                setMaps(current.lat, current.lon, current.city);
                setStatus('');
            } catch (err) {
                setStatus('Could not load weather: ' + err.message);
            }
        }

        async function loadByCity(city) {
            try {
                setStatus('Loading…');
                const [current, forecast] = await Promise.all([
                    fetchJson(`/api/weather/${encodeURIComponent(city)}`),
                    fetchJson(`/api/forecast/${encodeURIComponent(city)}`),
                ]);
                renderCurrent(current);
                renderForecast(forecast);
                setMaps(current.lat, current.lon, current.city);
                setStatus('');
            } catch (err) {
                setStatus(`Could not find "${city}". Try another spelling.`);
            }
        }

        // ---------- typeahead search ----------
        let lastResults = [];
        let debounceTimer;

        function placeLabel(p) {
            return [p.name, p.state, p.country].filter(Boolean).join(', ');
        }

        function renderResults(places) {
            const box = $('search-results');
            lastResults = places;
            if (places.length === 0) {
                box.innerHTML = '<div class="list-group-item text-muted small">No matches</div>';
                box.style.display = 'block';
                return;
            }
            box.innerHTML = places.map((p, i) => `
                <button type="button" class="list-group-item list-group-item-action" data-index="${i}">
                    ${placeLabel(p)}
                </button>
            `).join('');
            box.style.display = 'block';
        }

        async function searchPlaces(q) {
            try {
                const places = await fetchJson(`/api/places?q=${encodeURIComponent(q)}`);
                renderResults(places);
            } catch (err) {
                // typeahead errors are silent — don't pollute the UI for a bad keystroke
            }
        }

        $('search-input').addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const q = e.target.value.trim();
            if (q.length < 2) {
                $('search-results').style.display = 'none';
                return;
            }
            debounceTimer = setTimeout(() => searchPlaces(q), 300);
        });

        // Click a result row → load that place
        $('search-results').addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-index]');
            if (!btn) return;
            const place = lastResults[parseInt(btn.dataset.index, 10)];
            if (!place) return;
            $('search-input').value = placeLabel(place);
            $('search-results').style.display = 'none';
            loadByCoords(place.lat, place.lon);
        });

        // Submit / Enter → pick the first result if there is one
        $('search-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const first = $('search-results').querySelector('button[data-index]');
            if (first) first.click();
        });

        // Click outside → close the dropdown
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#search-form')) {
                $('search-results').style.display = 'none';
            }
        });

        // ---------- on page load ----------
        function init() {
            if (!navigator.geolocation) {
                setStatus('Geolocation not supported. Showing Delhi.');
                loadByCity('Delhi');
                return;
            }
            setStatus('Asking for your location…');
            navigator.geolocation.getCurrentPosition(
                (pos) => loadByCoords(pos.coords.latitude, pos.coords.longitude),
                ()    => { setStatus('Location denied. Showing Delhi.'); loadByCity('Delhi'); }
            );
        }

        init();




         const stripe = Stripe('{{ config('services.stripe.key') }}');
        const elements = stripe.elements();
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');

        const payButton = document.getElementById('pay-button');
        const messageDiv = document.getElementById('message');

        payButton.addEventListener('click', async () => {
            payButton.disabled = true;
            messageDiv.textContent = 'Processing...';
            
            // 1. Get client secret from our server
            const response = await fetch('/create-payment-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                messageDiv.textContent = 'Error: ' + data.message;
                payButton.disabled = false;
                return;
            }
            
            // 2. Confirm the payment with Stripe
            const { error, paymentIntent } = await stripe.confirmCardPayment(data.clientSecret, {
                payment_method: {
                    card: cardElement
                }
            });
            
            if (error) {
                messageDiv.textContent = 'Payment failed: ' + error.message;
                payButton.disabled = false;
            } else if (paymentIntent.status === 'succeeded') {
                // Send payment data to server to save
                const saveResponse = await fetch('/save-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        payment_intent_id: paymentIntent.id,
                        amount: 1000,
                        currency: 'usd'
                    })
                });
                
                if (saveResponse.ok) {
                    messageDiv.textContent = 'Payment successful and saved!';
                    messageDiv.style.color = 'green';
                } else {
                    messageDiv.textContent = 'Payment succeeded but failed to save record.';
                }
                payButton.disabled = false;
            }
        });



    </script>
</body>
</html>
