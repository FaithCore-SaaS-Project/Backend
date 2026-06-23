<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate</title>
    <style>
        body { font-family: Georgia, serif; padding: 48px; text-align: center; background: #fffdf4; }
        .border-box { border: 6px solid #f0c060; border-radius: 12px; padding: 36px; }
        .church-name { font-size: 13px; letter-spacing: 3px; color: #64748b; text-transform: uppercase; margin: 0; }
        .cert-type { font-size: 22px; font-weight: 900; color: #4C1D95; text-transform: uppercase; margin: 10px 0; }
        .certifies { font-style: italic; color: #64748b; margin-top: 20px; }
        .recipient { font-size: 28px; font-style: italic; margin: 10px 0; }
        .footer-church { color: #5B3DF5; font-weight: 800; margin-top: 20px; }
        .footer-box { border-top: 1px dashed #d1d5db; margin-top: 20px; padding-top: 12px; }
        .issued-by { color: #94a3b8; font-size: 12px; margin: 5px 0; }
    </style>
</head>
<body>
    <div class="border-box">
        <div style="margin-bottom: 20px;">
            @if(isset($church->logo_path) && $church->logo_path)
                <img src="{{ public_path('storage/' . $church->logo_path) }}" alt="Church Logo" style="max-height: 80px;">
            @else
                <div style="width: 80px; height: 80px; border-radius: 50%; background-color: #5B3DF5; color: white; display: inline-block; line-height: 80px; font-size: 24px; font-weight: bold;">
                    {{ substr($church->name, 0, 1) }}
                </div>
            @endif
        </div>
        <h1 class="church-name">{{ $church->name }}</h1>
        <h2 class="cert-type">{{ $certificate->type }} Certificate</h2>
        <p class="certifies">This is to certify that</p>
        <h3 class="recipient">{{ $certificate->recipient }}</h3>
        <p class="footer-church">{{ $church->name }}</p>
        <div class="footer-box">
            <p class="issued-by">Issued by: <strong>{{ $certificate->issued_by }}</strong></p>
            <p class="issued-by">{{ $certificate->issued_date instanceof \DateTimeInterface ? $certificate->issued_date->format('F j, Y') : date('F j, Y', strtotime($certificate->issued_date)) }}</p>
        </div>
    </div>
</body>
</html>
