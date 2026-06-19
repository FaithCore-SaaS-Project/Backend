<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Member Directory</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>FaithCore SaaS</h1>
        <h2>Member Directory</h2>
        <p>Total Members: {{ $total }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Status</th>
                <th>Join Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $member)
            <tr>
                <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                <td>{{ $member->email }}</td>
                <td>{{ $member->phone }}</td>
                <td>{{ ucfirst($member->gender) }}</td>
                <td>{{ ucfirst($member->status) }}</td>
                <td>{{ $member->join_date }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
