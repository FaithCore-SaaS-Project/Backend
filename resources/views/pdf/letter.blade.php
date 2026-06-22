<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Letter</title>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; padding: 40px; color: #333; line-height: 1.6; }
        .header { border-bottom: 2px solid #5B3DF5; padding-bottom: 20px; margin-bottom: 40px; }
        .church-name { font-size: 24px; font-weight: bold; color: #4C1D95; margin: 0; }
        .church-address { font-size: 12px; color: #666; margin: 5px 0 0 0; }
        .date { text-align: right; font-size: 14px; margin-bottom: 30px; }
        .recipient-box { margin-bottom: 40px; }
        .recipient-name { font-weight: bold; margin: 0; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
        .content { font-size: 14px; white-space: pre-wrap; margin-bottom: 50px; }
        .signature { margin-top: 50px; }
        .signature-line { border-top: 1px solid #ccc; width: 200px; padding-top: 5px; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="church-name">{{ $church->name }}</h1>
        <p class="church-address">{{ $church->address ?? 'Church Address' }}</p>
    </div>
    
    <div class="date">
        {{ $letter->issue_date instanceof \DateTimeInterface ? $letter->issue_date->format('F j, Y') : date('F j, Y', strtotime($letter->issue_date)) }}
    </div>
    
    <div class="recipient-box">
        <p class="recipient-name">To: {{ $letter->recipient }}</p>
        @if($letter->recipient_email)
            <p style="margin:0; font-size:12px; color:#666;">{{ $letter->recipient_email }}</p>
        @endif
        @if($letter->recipient_phone)
            <p style="margin:0; font-size:12px; color:#666;">{{ $letter->recipient_phone }}</p>
        @endif
    </div>
    
    <div class="title">{{ $letter->title }}</div>
    
    <div class="content">{{ $letter->content }}</div>
    
    <div class="signature">
        <p>Sincerely,</p>
        <div class="signature-line">
            <strong>{{ $letter->sent_by }}</strong><br>
            {{ $church->name }}
        </div>
    </div>
</body>
</html>
