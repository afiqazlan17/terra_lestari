<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Jualan</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 24px;">
        <h2 style="margin: 0 0 4px; font-size: 18px;">{{ $project->name }}</h2>
        <p style="margin: 0 0 20px; color: #6b7280; font-size: 13px;">Ringkasan jualan {{ $date->translatedFormat('l, d M Y') }}</p>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Jualan</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">RM {{ number_format($summary['sales'], 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Belian</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">RM {{ number_format($summary['purchases'], 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; border-top: 1px solid #e5e7eb;">Untung Kasar</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; border-top: 1px solid #e5e7eb; color: {{ $summary['profit'] >= 0 ? '#16a34a' : '#dc2626' }};">
                    RM {{ number_format($summary['profit'], 2) }}
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Bilangan Order</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">{{ $summary['orderCount'] }}</td>
            </tr>
        </table>

        @if ($summary['topItem'] || $summary['peakHour'])
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 8px;">
                @if ($summary['topItem'])
                    <tr>
                        <td style="padding: 6px 0; color: #6b7280;">Item paling laku</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold;">{{ $summary['topItem'] }}</td>
                    </tr>
                @endif
                @if ($summary['peakHour'])
                    <tr>
                        <td style="padding: 6px 0; color: #6b7280;">Jam paling sibuk</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold;">{{ $summary['peakHour'] }}</td>
                    </tr>
                @endif
            </table>
        @endif

        <p style="margin: 24px 0 0; color: #9ca3af; font-size: 12px;">
            Emel automatik dari sistem Sajian Baginda.
        </p>
    </div>
</body>
</html>
