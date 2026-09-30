{{-- Website fix list H7: a click-to-chat WhatsApp button, fixed so it's
     reachable from anywhere on the site, not just the contact page. Reads
     the same "phone" setting the header/footer already show, so there's
     one number to keep updated, not two. --}}
@php
    $waNumber = preg_replace('/[^0-9]/', '', (string) setting('phone'));
@endphp
@if ($waNumber)
    <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="bn-whatsapp-fab" aria-label="Chat on WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
    <style>
        .bn-whatsapp-fab{
            position:fixed; right:18px; bottom:18px; z-index:999;
            width:56px; height:56px; border-radius:50%; background:#25D366;
            display:flex; align-items:center; justify-content:center;
            box-shadow:0 4px 16px rgba(0,0,0,.25); color:#fff; font-size:1.7rem;
            transition:transform .15s ease;
        }
        .bn-whatsapp-fab:hover{ transform:scale(1.08); color:#fff; }
        @media (max-width:576px){
            .bn-whatsapp-fab{ right:14px; bottom:14px; width:50px; height:50px; font-size:1.5rem; }
        }
    </style>
@endif
