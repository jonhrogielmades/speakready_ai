         <!-- NAVBAR -->
         <nav id="nbar">
            <div class="container">
               <div class="d-flex align-items-center justify-content-between w-100">
                  <a href="#" class="d-flex align-items-center gap-2 text-truncate" style="font-size:1.2rem;font-weight:700;color:var(--tx); max-width: calc(100vw - 120px);">
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: #ffffff; padding: 0; flex-shrink: 0;">
                     <span class="text-truncate">SpeakReady AI</span>
                  </a>
                  <div class="d-none d-xl-flex align-items-center gap-1 mx-auto">
                     <a href="#" class="nav-link">Home</a>
                     <a href="#features" class="nav-link">Features</a>
                     <a href="#how" class="nav-link">How It Works</a>
                     <a href="#benefits" class="nav-link">Interview Categories</a>
                     <a href="#developers" class="nav-link">Developers</a>
                     <a href="#faq" class="nav-link">FAQ</a>
                     <a href="#contact" class="nav-link">Contact Us</a>
                  </div>
                  <div class="d-flex align-items-center gap-2 flex-shrink-0">
                     <button class="boc d-flex align-items-center justify-content-center" id="thbtn" style="width:38px;height:38px;padding:0;border-radius:12px" aria-label="Toggle theme">
                     <i class="fa-solid fa-sun" id="suni" style="display:none"></i>
                     <i class="fa-solid fa-moon" id="mooni"></i>
                     </button>
                     <button class="boc px-3 py-2 d-none d-sm-flex align-items-center gap-1" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('login')">
                     <i class="fa-regular fa-user fa-sm"></i> Login
                     </button>
                     <button class="bgrd btn px-3 py-2 d-none d-sm-flex align-items-center gap-1" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">
                     Register <i class="fa-solid fa-arrow-right fa-sm"></i>
                     </button>
                     <button class="boc d-xl-none px-2 py-2" id="mbtog" style="border-radius:10px">
                     <i class="fa-solid fa-bars" id="barIcon"></i>
                     <i class="fa-solid fa-xmark" id="xIcon" style="display:none"></i>
                     </button>
                  </div>
               </div>
            </div>
         </nav>
         <div id="mbmenu">
            <a href="#" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Home</a>
            <a href="#features" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Features</a>
            <a href="#how" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">How It Works</a>
            <a href="#benefits" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Interview Categories</a>
            <a href="#developers" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Developers</a>
            <a href="#faq" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">FAQ</a>
            <a href="#contact" class="nav-link d-block py-3">Contact Us</a>
            <div class="d-flex gap-2 mt-3">
               <button class="boc flex-fill py-2 btn" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('login')">Login</button>
               <button class="bgrd flex-fill py-2 btn" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">Register</button>
            </div>
         </div>
