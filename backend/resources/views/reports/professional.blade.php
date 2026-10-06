<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page { margin: 28px 34px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 10px;
            line-height: 1.45;
        }
        .brand {
            padding: 14px 16px;
            color: #fff;
            background: #7c2d12;
            border-radius: 8px;
        }
        .brand h1 { margin: 0; font-size: 20px; }
        .brand p { margin: 4px 0 0; color: #ffedd5; }
        .meta {
            width: 100%;
            margin: 14px 0;
            border-collapse: collapse;
        }
        .meta td {
            padding: 4px 7px;
            border-bottom: 1px solid #e5e7eb;
        }
        .meta td:first-child {
            width: 30%;
            font-weight: bold;
            color: #7c2d12;
        }
        .summary {
            width: 100%;
            margin: 12px 0 18px;
            border-collapse: separate;
            border-spacing: 5px;
        }
        .summary td {
            padding: 10px;
            text-align: center;
            background: #fff7ed;
            border: 1px solid #fed7aa;
        }
        .summary strong {
            display: block;
            color: #9a3412;
            font-size: 15px;
        }
        h2 {
            margin: 16px 0 7px;
            color: #7c2d12;
            font-size: 13px;
        }
        table.data {
            width: 100%;
            margin: 7px 0 15px;
            border-collapse: collapse;
        }
        table.data th {
            padding: 6px;
            color: #fff;
            background: #9a3412;
            text-align: left;
        }
        table.data td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        .chart {
            margin: 8px 0 16px;
            padding: 10px;
            border: 1px solid #e5e7eb;
        }
        .bar-row { margin: 6px 0; }
        .bar-label { width: 22%; display: inline-block; }
        .bar-track {
            display: inline-block;
            width: 60%;
            height: 10px;
            background: #f1f5f9;
            vertical-align: middle;
        }
        .bar-value {
            height: 10px;
            background: #f97316;
        }
        .bar-number {
            width: 12%;
            display: inline-block;
            text-align: right;
        }
        .limitations {
            margin-top: 16px;
            padding: 9px 11px;
            color: #854d0e;
            background: #fefce8;
            border: 1px solid #fde68a;
        }
        .footer {
            margin-top: 22px;
            padding-top: 8px;
            color: #64748b;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="brand">
        <h1>CricIntel AI — {{ $report['title'] }}</h1>
        <p>{{ $report['subtitle'] }}</p>
    </div>

    <table class="meta">
        @foreach ($report['metadata'] as $label => $value)
            @if ($value !== null && $value !== '')
                <tr>
                    <td>{{ $label }}</td>
                    <td>{{ $value }}</td>
                </tr>
            @endif
        @endforeach
    </table>

    @if (!empty($report['summary']))
        <table class="summary">
            <tr>
                @foreach ($report['summary'] as $item)
                    <td>
                        <strong>{{ $item['value'] }}</strong>
                        {{ $item['label'] }}
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @foreach ($report['sections'] as $section)
        <h2>{{ $section['heading'] }}</h2>
        <p>{{ $section['body'] }}</p>
    @endforeach

    @foreach ($report['charts'] as $chart)
        @php
            $max = max(array_merge([1], array_map('floatval', $chart['values'])));
        @endphp
        <div class="chart">
            <h2>{{ $chart['title'] }}</h2>
            @foreach ($chart['labels'] as $index => $label)
                @php
                    $value = (float) ($chart['values'][$index] ?? 0);
                    $width = min(100, ($value / $max) * 100);
                @endphp
                <div class="bar-row">
                    <span class="bar-label">{{ $label }}</span>
                    <span class="bar-track">
                        <span class="bar-value" style="display:block;width:{{ $width }}%"></span>
                    </span>
                    <span class="bar-number">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    @endforeach

    @foreach ($report['tables'] as $table)
        <h2>{{ $table['title'] }}</h2>
        <table class="data">
            <thead>
                <tr>
                    @foreach ($table['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($table['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ is_scalar($cell) || $cell === null ? $cell : json_encode($cell) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($table['columns']) }}">No data matched the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    @if (!empty($report['limitations']))
        <div class="limitations">
            <strong>Limitations</strong>
            <ul>
                @foreach ($report['limitations'] as $limitation)
                    <li>{{ $limitation }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="footer">
        Generated by CricIntel AI Professional Reporting. This export reflects stored CricIntel data and selected filters at generation time.
    </div>
</body>
</html>
