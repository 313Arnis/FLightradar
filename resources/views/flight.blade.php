<!DOCTYPE html>
<html>
<head>
    <title>Flight Map API</title>
    <link href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" rel="stylesheet" />
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        #map { position: absolute; top: 0; bottom: 0; width: 100%; }

        #plane-info {
            position: absolute;
            top: 20px;
            left: -350px;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 0;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            min-width: 260px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: left 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        #plane-info.active {
            left: 20px;
        }

        .info-header {
            background: #1a237e;
            color: white;
            padding: 12px 15px;
            position: relative;
        }

        .info-header h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        #close-btn {
            position: absolute;
            top: 10px;
            right: 12px;
            cursor: pointer;
            background: rgba(255,255,255,0.2);
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 16px;
            transition: 0.2s;
        }
        #close-btn:hover { background: rgba(255,255,255,0.4); }

        #info-content {
            padding: 15px;
            line-height: 1.5;
        }

        .info-item {
            margin-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 6px;
        }

        .info-label { font-weight: bold; color: #777; font-size: 11px; text-transform: uppercase; display: block; }
        .info-value { color: #000; font-size: 14px; font-weight: 500; }
        
        .callsign-highlight {
            font-size: 22px;
            font-weight: 800;
            color: #1a237e;
            display: block;
            margin-top: 2px;
        }

        .status-on-ground { color: #d9534f; font-weight: bold; }
        .status-in-air { color: #5cb85c; font-weight: bold; }
    </style>
</head>
<body>

    <div id="plane-info">
        <div class="info-header">
            <h3>Lidmašīnas dati</h3>
            <span id="close-btn" onclick="closeInfo()">×</span>
        </div>
        <div id="info-content"></div>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/maplibre-gl/dist/maplibre-gl.js"></script>
    <script>
        const airlineColors = {
            'BTI': '#99CC00', 'CSS': '#FFD700', 'LOT': '#1A237E', 'THY': '#C62828',
            'NSZ': '#E91E63', 'RYR': '#0033AD', 'SWR': '#E1001A', 'TVF': '#5CB12C',
            'JST': '#FF6600', 'EXS': '#ED1C24', 'ADO': '#00A1E4', 'AXB': '#D22630',
            'EJU': '#FF4500', 'EZY': '#FF9500', 'AIQ': '#ED1C24', 'AIC': '#FF0000',
            'KXU': '#0054A6', 'IGO': '#00428A', 'GOA': '#22409A', 'TAP': '#119F49',
            'SAA': '#E0AA3E', 'VTI': '#621B41', 'CMD': '#FF00FF', 'ASA': '#00426A',
            'DLH': '#002F5B', 'EWG': '#C71585', 'PGT': '#FFFF00', 'AZU': '#003399',
            'DAL': '#E01933', 'CFG': '#FCD116', 'TAM': '#E11A27', 'LPE': '#C10017',
            'LNE': '#A00014', 'ANA': '#004482', 'AAL': '#1B365D', 'AUA': '#EE1C23',
            'SAS': '#0030AD', 'VOE': '#D30101', 'BAW': '#072147', 'AEE': '#0D2139',
            'WZZ': '#D91870', 'WMT': '#B0135B', 'LNI': '#DA251D', 'DAH': '#8D1B1B',
            'LAO': '#00205B', 'NBT': '#DC143C', 'EJA': '#54565B', 'HUE': '#CC0000',
            'DGX': '#080808', 'AWQ': '#7C0A02', 'LOG': '#9400D3', 'AKJ': '#FF6F00',
            'CJT': '#E31E24', 'UPS': '#351C15', 'UAL': '#1D589C', 'FSY': '#20B2AA',
            'NOZ': '#D0021B', 'ANZ': '#222222', 'JAL': '#D90011', 'BAF': '#7D7D7D',
            'FIN': '#003580', 'VLG': '#F7D117', 'AFL': '#EB1C23', 'BEL': '#ED2939',
            'UAE': '#D71921', 'KLM': '#00A1DE', 'AFR': '#002366', 'ICE': '#FFDD00',
            'N92': '#4F4F4F', 'N13': '#4F4F4F', 'N80': '#4F4F4F', 'N70': '#4F4F4F',
            '230': '#4B5320'
        };

        function getAirlineColor(callsign) {
            if (!callsign) return '#333333';
            const prefix = callsign.substring(0, 3).toUpperCase();
            return airlineColors[prefix] || '#333333';
        }

        const map = new maplibregl.Map({
            container: 'map',
            style: 'https://demotiles.maplibre.org/style.json',
            center: [24.1, 56.9],
            zoom: 6
        });

        function closeInfo() {
            document.getElementById('plane-info').classList.remove('active');
        }

        map.on('load', async () => {
            try {
                const image = await map.loadImage('/plane.png');
                map.addImage('plane-icon', image.data, { sdf: true });
            } catch (err) {
                console.error("Nevarēja ielādēt /plane.png.", err);
            }

            map.addSource('aircrafts', {
                type: 'geojson',
                data: { type: 'FeatureCollection', features: [] }
            });

            map.addLayer({
                id: 'aircrafts-layer',
                type: 'symbol',
                source: 'aircrafts',
                layout: {
                    'icon-image': 'plane-icon',
                    'icon-size': 0.3, 
                    'icon-rotate': ['get', 'heading'],
                    'icon-rotation-alignment': 'map',
                    'text-field': ['get', 'callsign'],
                    'text-size': 10,
                    'text-offset': [0, 2],
                    // ŠEIT IR IZMAIŅAS, LAI RĀDĪTU VISAS LIDMAŠĪNAS:
                    'icon-allow-overlap': true,
                    'icon-ignore-placement': true,
                    'text-allow-overlap': true
                },
                paint: {
                    'icon-color': ['get', 'color']
                }
            });

            updateData();
            setInterval(updateData, 5000);
        });

        map.on('click', 'aircrafts-layer', (e) => {
            const p = e.features[0].properties;
            const infoBox = document.getElementById('plane-info');
            const infoContent = document.getElementById('info-content');

            const statusText = p.on_ground === 'true' || p.on_ground === true 
                ? '<span class="status-on-ground">Uz zemes</span>' 
                : '<span class="status-in-air">Gaisā</span>';

            infoContent.innerHTML = `
                <div class="info-item">
                    <span class="info-label">Statuss</span>
                    ${statusText}
                </div>
                <div class="info-item">
                    <span class="info-label">Reisa numurs</span>
                    <span class="callsign-highlight">${p.callsign}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Izlidošanas valsts</span>
                    <span class="info-value">${p.origin_country}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Augstums</span>
                    <span class="info-value">${p.altitude} m</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ātrums</span>
                    <span class="info-value">${p.velocity} m/s</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Lidojuma virziens</span>
                    <span class="info-value">${p.heading}°</span>
                </div>
            `;
            
            infoBox.classList.add('active');
        });

        map.on('mouseenter', 'aircrafts-layer', () => { map.getCanvas().style.cursor = 'pointer'; });
        map.on('mouseleave', 'aircrafts-layer', () => { map.getCanvas().style.cursor = ''; });

        async function updateData() {
            try {
                const response = await fetch('/flights-data');
                const states = await response.json(); 

                if (Array.isArray(states)) {
                    const geojson = {
                        type: 'FeatureCollection',
                        features: states.map(f => {
                            if (f[5] !== null && f[6] !== null) {
                                const callsign = f[1] ? f[1].trim() : 'N/A';
                                return {
                                    type: 'Feature',
                                    geometry: {
                                        type: 'Point',
                                        coordinates: [f[5], f[6]] 
                                    },
                                    properties: {
                                        icao24: f[0],
                                        callsign: callsign,
                                        color: getAirlineColor(callsign),
                                        origin_country: f[2],
                                        on_ground: f[8],
                                        altitude: f[7] ? Math.round(f[7]) : 0,
                                        velocity: f[9] ? Math.round(f[9]) : 0,
                                        heading: f[10] || 0
                                    }
                                };
                            }
                            return null;
                        }).filter(f => f !== null) 
                    };
                    map.getSource('aircrafts').setData(geojson);
                }
            } catch (error) {
                console.error("Datu kļūda:", error);
            }
        }
    </script>
</body>
</html>