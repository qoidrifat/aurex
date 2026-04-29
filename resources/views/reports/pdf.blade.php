<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AUREX Style Report · #{{ $analysis->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', 'Helvetica Neue', Arial, sans-serif; color: #1C1C1C; background: #F5F5F5; margin: 0; padding: 40px; }
        .page { max-width: 820px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E5E5E5; padding: 48px; }
        h1 { font-size: 32px; margin: 0 0 4px; letter-spacing: -0.02em; }
        h2 { font-size: 18px; margin: 28px 0 8px; color: #1C1C1C; }
        p  { font-size: 14px; line-height: 1.6; color: #3C3C3C; margin: 0 0 12px; }
        .meta { color: #666; font-size: 12px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #E5E5E5; padding-bottom: 20px; margin-bottom: 28px; }
        .score { text-align: right; }
        .score .value { font-size: 44px; font-weight: 700; color: #B7410E; line-height: 1; }
        .score .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.2em; color: #888; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #FAFAFA; border: 1px solid #EEE; border-radius: 12px; padding: 18px; }
        .dot { display: inline-block; width: 34px; height: 34px; border-radius: 999px; margin-right: 8px; vertical-align: middle; border: 1px solid rgba(0,0,0,0.08); }
        .badge { display: inline-block; border: 1px solid #DDD; background: #F5F5F5; padding: 4px 10px; font-size: 11px; border-radius: 999px; margin: 2px 4px 2px 0; }
        ul { padding-left: 18px; }
        li { margin-bottom: 6px; font-size: 13px; color: #3C3C3C; }
        footer { margin-top: 40px; border-top: 1px solid #EEE; padding-top: 16px; font-size: 11px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div>
                <h1>AUREX Style Report</h1>
                <p class="meta">Analysis #{{ $analysis->id }} · {{ $analysis->created_at->toFormattedDateString() }}</p>
            </div>
            <div class="score">
                <div class="value">{{ $analysis->style_score ?? '—' }}</div>
                <div class="label">Style Score</div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h2 style="margin-top:0">Face shape</h2>
                <p style="font-size:18px; font-weight:600; text-transform:capitalize;">{{ $analysis->face_shape ?? '—' }}</p>
                <p>{{ $report->face_shape_summary }}</p>
            </div>
            <div class="card">
                <h2 style="margin-top:0">Skin undertone</h2>
                <p style="font-size:18px; font-weight:600; text-transform:capitalize;">{{ $analysis->skin_undertone ?? '—' }}</p>
                <p>{{ $report->color_summary }}</p>
            </div>
        </div>

        <h2>Hairstyle recommendations</h2>
        <p>{{ $report->hairstyle_summary }}</p>
        <div>
            @foreach ($analysis->recommendations->where('type', 'hairstyle') as $h)
                <span class="badge">{{ $h->label }}</span>
            @endforeach
        </div>

        <h2>Color palette</h2>
        <p>{{ $report->color_summary }}</p>
        <div>
            @foreach ($analysis->recommendations->where('type', 'color') as $c)
                <span style="display:inline-block; margin-right:14px; vertical-align:middle;">
                    <span class="dot" style="background-color: {{ $c->hex_color ?? '#888' }};"></span>
                    <span style="text-transform:capitalize; font-size:12px;">{{ $c->label }}</span>
                </span>
            @endforeach
        </div>

        <h2>Outfit suggestions</h2>
        <p>{{ $report->outfit_summary }}</p>
        <ul>
            @foreach ($analysis->recommendations->where('type', 'outfit') as $o)
                <li>{{ $o->label }}</li>
            @endforeach
        </ul>

        <h2>Style improvement tips</h2>
        <pre style="white-space:pre-wrap; font-family:inherit; font-size:13px; color:#3C3C3C;">{{ $report->improvement_tips }}</pre>

        <footer>
            AUREX — AI Style Intelligence · Generated {{ now()->toFormattedDateString() }}<br>
            Print this page with your browser (Ctrl/Cmd + P) to save as PDF.
        </footer>
    </div>
</body>
</html>
