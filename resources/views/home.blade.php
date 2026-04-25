<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weather</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background: #f1f5f9; }
        #map { height: 400px; border-radius: 12px; overflow: hidden; }
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

        <section id="current-card" class="card shadow-sm mb-4">
            <div class="card-body">
                <p class="text-muted m-0">Loading current weather…</p>
            </div>
        </section>

        <section id="forecast-strip" class="row g-3 mb-4">
            <p class="text-muted col-12">Loading forecast…</p>
        </section>

        <section id="map" class="shadow-sm mb-3"></section>

        <p id="status" class="text-muted small"></p>

    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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

        // ---------- map ----------
        let map, marker;
        function setMap(lat, lon, label) {
            if (!map) {
                map = L.map('map').setView([lat, lon], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                marker = L.marker([lat, lon]).addTo(map);
            } else {
                map.setView([lat, lon], 10);
                marker.setLatLng([lat, lon]);
            }
            if (label) marker.bindPopup(label).openPopup();
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
                setMap(current.lat, current.lon, current.city);
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
                setMap(current.lat, current.lon, current.city);
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
    </script>
</body>
</html>
