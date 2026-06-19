<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .stats { margin-bottom: 20px; }
        .stats p { margin: 5px 0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>FaithCore SaaS</h1>
        <h2>Attendance Report</h2>
    </div>

    <div class="stats">
        <p>Total Registered: {{ $total_registered }}</p>
        <p style="color: green;">Total Checked In: {{ $total_checked_in }}</p>
        <p style="color: red;">Total No Show: {{ $total_no_show }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Event</th>
                <th>Member Name</th>
                <th>Status</th>
                <th>Registration Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registrations as $reg)
            <tr>
                <td>{{ $reg->event->title ?? 'N/A' }}</td>
                <td>{{ $reg->member->first_name ?? '' }} {{ $reg->member->last_name ?? '' }}</td>
                <td>
                    @if($reg->status === 'checked_in')
                        <span style="color: green;">Checked In</span>
                    @elseif($reg->status === 'no_show')
                        <span style="color: red;">No Show</span>
                    @else
                        <span>Registered</span>
                    @endif
                </td>
                <td>{{ $reg->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
