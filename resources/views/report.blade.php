<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Compliance Report - Act 843</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }

        h1 {
            color: #1e293b;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 5px;
        }

        h2 {
            color: #334155;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
            font-weight: bold;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-high {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-medium {
            background: #fef9c3;
            color: #a16207;
        }

        .badge-low {
            background: #dcfce7;
            color: #166534;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <h1>Ghana Data Protection Act 843 – Compliance Report</h1>
    <p><strong>Period:</strong> {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</p>
    <p><strong>Generated:</strong> {{ now()->format('d M Y H:i:s') }}</p>
    <p><strong>Report Type:</strong> {{ ucfirst($type) }}</p>

    <h2>Overall Compliance Health</h2>
    <p>Compliance Score: <strong>{{ $health['score'] }}/100</strong> (Grade {{ $health['grade'] }})</p>
    <p>Password Policy: {{ $health['password_policy']['status'] }}</p>
    <p>Data Retention: {{ $health['data_retention']['status'] }}</p>

    <h2>Security Event Summary</h2>
    <table>
        <tr>
            <th>Total Events</th>
            <td>{{ $stats['total_events'] }}</td>
        </tr>
        <tr>
            <th>High Risk</th>
            <td>{{ $stats['high_risk'] }}</td>
        </tr>
        <tr>
            <th>Medium Risk</th>
            <td>{{ $stats['medium_risk'] }}</td>
        </tr>
        <tr>
            <th>Low Risk</th>
            <td>{{ $stats['low_risk'] }}</td>
        </tr>
        <tr>
            <th>Unique IPs</th>
            <td>{{ $stats['unique_ips'] }}</td>
        </tr>
        <tr>
            <th>Average Risk Score</th>
            <td>{{ $stats['avg_score'] }}</td>
        </tr>
    </table>

    <h2>Top 10 Risky IPs</h2>
    <table>
        <thead>
            <tr>
                <th>IP Address</th>
                <th>Risk Score</th>
                <th>Risk Level</th>
                <th>Country</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topIps as $ip)
                <tr>
                    <td>{{ $ip->ip }}</td>
                    <td>{{ $ip->score }}</td>
                    <td>{{ $ip->risk_level }}</td>
                    <td>{{ $ip->country ?? 'Unknown' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Recommendations</h2>
    @if(!empty($health['recommendations']))
        <ul>
            @foreach($health['recommendations'] as $rec)
                <li>{{ $rec }}</li>
            @endforeach
        </ul>
    @else
        <p>No recommendations – system is compliant.</p>
    @endif

    <div class="footer">
        This report was generated automatically by the Act 843 Compliance Monitoring SDK.<br>
        It reflects detection‑only intelligence; no blocking or enforcement is performed.
    </div>
</body>

</html>