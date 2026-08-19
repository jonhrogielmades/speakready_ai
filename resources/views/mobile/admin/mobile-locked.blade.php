<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#0f172a">
      <title>Admin Desktop Only | SpeakReady AI</title>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/mobile/admin/mobile-locked.css?v=1') }}" data-page-style="admin-mobile-locked">
   </head>
   <body>
      <main class="admin-mobile-lock" role="main" aria-labelledby="adminMobileLockTitle">
         <div class="brand">
            <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
            <div>
               <strong>SpeakReady AI</strong>
               <span>Admin Portal</span>
            </div>
         </div>

         <div class="lock-icon" aria-hidden="true">
            <i class="fa-solid fa-desktop"></i>
         </div>

         <h1 id="adminMobileLockTitle">Admin is temporarily desktop only.</h1>
         <p>
            Mobile admin access is locked while the mobile interface is being fixed.
            Please open the admin portal on a desktop or laptop browser.
         </p>

         <div class="actions">
            <form action="{{ route('logout') }}" method="POST">
               @csrf
               <button class="btn-lock btn-secondary-lock w-100" type="submit">
                  <i class="fa-solid fa-right-from-bracket"></i>
                  Log out
               </button>
            </form>
         </div>
      </main>
   </body>
</html>
