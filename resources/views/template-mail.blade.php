<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f5f7f6;font-family:Arial,sans-serif;color:#1f2937">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center" style="padding:32px 16px">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border-radius:14px"><tr><td style="padding:32px;line-height:1.6">
{!! $bodyHtml !!}
@if ($ctaLabel && $ctaUrl)<p style="margin:28px 0 0"><a href="{{ $ctaUrl }}" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#047844;color:#fff;text-decoration:none;font-weight:600">{{ $ctaLabel }}</a></p>@endif
</td></tr></table>
</td></tr></table>
</body>
</html>
