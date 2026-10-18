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
background-color: #f1f6fb !important;
}

.wrapper {
background-color: #f1f6fb !important;
padding: 24px 0 36px 0;
}

.content {
max-width: 680px;
}

.inner-body {
background-color: #ffffff !important;
border: 1px solid #e1ebf4 !important;
border-radius: 22px !important;
box-shadow: 0 18px 42px rgba(35, 77, 119, .12);
overflow: hidden;
max-width: 620px !important;
width: 620px !important;
}

.body {
background-color: #f1f6fb !important;
border-color: #f1f6fb !important;
}

.content-cell {
padding: 48px 54px !important;
text-align: center !important;
}

.header {
padding: 20px 0 24px 0 !important;
text-align: center !important;
}

.header a {
display: inline-block !important;
text-align: center !important;
}

.email-brand-mark {
background-color: #ffffff;
border: 1px solid #d7e6f5;
border-radius: 50%;
box-shadow: 0 10px 22px rgba(38, 91, 145, .16);
height: 72px;
padding: 7px;
width: 72px;
}

.email-brand-name {
color: #123a63;
font-size: 18px;
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
font-size: 30px !important;
font-weight: 800 !important;
letter-spacing: -.25px;
line-height: 1.18;
margin: 0 auto 16px !important;
max-width: 540px;
text-align: center !important;
}

.content-cell p,
.content-cell li {
color: #526b86 !important;
font-size: 16px !important;
line-height: 1.7 !important;
text-align: center !important;
}

.content-cell p {
margin: 0 auto 18px;
max-width: 540px;
text-align: center !important;
}

.content-cell ul,
.content-cell ol {
display: inline-block;
margin: 0 auto 18px;
text-align: left !important;
}

.content-cell .action {
margin: 30px 0 14px 0 !important;
text-align: center !important;
}

.content-cell .button {
background-color: #1769e0 !important;
border: 0 !important;
border-radius: 12px !important;
box-shadow: 0 9px 18px rgba(23, 105, 224, .24);
display: inline-block !important;
font-size: 14px !important;
font-weight: 700 !important;
line-height: 1 !important;
padding: 15px 24px !important;
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
max-width: 520px;
text-align: center !important;
}

.footer .content-cell {
padding: 22px 24px 4px !important;
text-align: center !important;
}

.footer .content-cell p {
color: #8ca0b6 !important;
font-size: 12px !important;
line-height: 1.5 !important;
text-align: center !important;
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
padding: 34px 24px !important;
}

.content-cell h1 {
font-size: 24px !important;
}

.content-cell p,
.content-cell li {
font-size: 15px !important;
}

.email-brand-mark {
height: 64px;
padding: 6px;
width: 64px;
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

