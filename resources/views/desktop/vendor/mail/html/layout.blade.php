<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{{ config('app.name', 'SpeakReady AI') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
body {
background-color: #eef4fa !important;
}

.wrapper {
background-color: #eef4fa !important;
padding: 18px 0 28px 0;
}

.content {
max-width: 680px;
}

.inner-body {
max-width: 620px !important;
width: 620px !important;
}

.content-cell {
padding: 42px 48px !important;
}

.header {
padding: 26px 0 22px 0 !important;
}

.email-brand-name {
color: #123a63;
font-size: 17px;
font-weight: 800;
letter-spacing: .15px;
line-height: 1.2;
}

.email-brand-tagline {
color: #6b83a0;
font-size: 11px;
letter-spacing: .7px;
line-height: 1.4;
margin-top: 5px;
text-transform: uppercase;
}

.content-cell h1 {
color: #173b63 !important;
font-size: 26px !important;
font-weight: 800 !important;
letter-spacing: -.25px;
line-height: 1.2;
margin-bottom: 18px;
}

.content-cell p,
.content-cell li {
color: #526b86 !important;
font-size: 16px !important;
line-height: 1.7 !important;
}

.content-cell p {
margin-bottom: 18px;
}

.content-cell .action {
margin: 28px 0 10px 0 !important;
text-align: left !important;
}

.content-cell .button {
background-color: #1769e0 !important;
border: 0 !important;
border-radius: 9px !important;
box-shadow: 0 6px 14px rgba(23, 105, 224, .2);
font-size: 14px !important;
line-height: 1 !important;
padding: 14px 20px !important;
}

.content-cell .subcopy {
border-top-color: #e4edf6 !important;
margin-top: 30px !important;
padding-top: 20px !important;
}

.content-cell .subcopy p {
color: #8ca0b6 !important;
font-size: 12px !important;
line-height: 1.5 !important;
}

@media only screen and (max-width: 600px) {
.wrapper {
padding: 8px 0 18px 0;
}

.content {
width: 100% !important;
}

.inner-body {
border-radius: 14px !important;
width: calc(100% - 24px) !important;
}

.footer {
width: 100% !important;
}

.content-cell {
padding: 30px 24px !important;
}

.content-cell h1 {
font-size: 23px !important;
}

.content-cell p,
.content-cell li {
font-size: 15px !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}

.content-cell .button {
display: block !important;
text-align: center !important;
}
}
</style>
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{{ $header ?? '' }}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="620" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{{ Illuminate\Mail\Markdown::parse($slot) }}

{{ $subcopy ?? '' }}
</td>
</tr>
</table>
</td>
</tr>

{{ $footer ?? '' }}
</table>
</td>
</tr>
</table>
</body>
</html>

