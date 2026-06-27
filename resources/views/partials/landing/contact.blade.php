         <!-- CONTACT US -->
         <section id="contact" class="sp position-relative">
            <div class="container position-relative" style="z-index:1">
               <div class="row g-5 justify-content-center">
                  <div class="col-lg-5 rv">
                     <span class="slbl">Contact Us</span>
                     <h2 class="stitle mb-4">Get in <span class="gt">Touch</span></h2>
                     <p style="font-size:1.05rem;color:var(--tx2);margin-bottom:30px">Have questions or need support? We're here to help you on your journey to interview success.</p>
                     
                     <div class="d-flex flex-column gap-4">
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-envelope" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Email Address</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;"></p>
                             </div>
                         </div>
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-phone" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Contact Number</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">09066544727</p>
                             </div>
                         </div>
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-location-dot" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Location</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">Pinut-an, San Ricardo, Southern Leyte, Philippines</p>
                             </div>
                         </div>
                     </div>
                  </div>
                  <div class="col-lg-5 rv" style="transition-delay:.1s">
                     <div class="gc p-4 p-md-5 h-100">
                         @if(session('contact_success'))
                             <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: #059669; border-radius: 12px; padding: 15px;">
                                 <i class="fa-solid fa-circle-check fs-5 me-3"></i>
                                 <div>
                                     <strong>Success!</strong> {{ session('contact_success') }}
                                 </div>
                                 <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="filter: brightness(0.5);"></button>
                             </div>
                         @endif
                         <form action="{{ route('contact.send') }}" method="POST">
                             @csrf
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Name</label>
                                 <input type="text" name="name" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your Full Name" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Email</label>
                                 <input type="email" name="email" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="you@example.com" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Subject</label>
                                 <input type="text" name="subject" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="How can we help?" required>
                             </div>
                             <div class="mb-4">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Message</label>
                                 <textarea name="message" class="form-control" rows="4" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your message here..." required></textarea>
                             </div>
                             <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">Send Message</button>
                         </form>
                     </div>
                  </div>
               </div>
            </div>
         </section>

