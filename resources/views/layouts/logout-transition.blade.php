<style>
#logoutTransitionOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: var(--bg, #ffffff);
    z-index: 999999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
#logoutTransitionOverlay.active {
    opacity: 1;
    visibility: visible;
}
.logo-loading-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 30px;
    background: linear-gradient(180deg, #ffffff, #eff6ff);
    border: 1px solid rgba(96, 165, 250, 0.26);
    isolation: isolate;
    overflow: hidden;
    box-shadow:
        0 0 0 4px rgba(255, 255, 255, 0.62),
        0 18px 36px rgba(37, 99, 235, 0.16);
}
.logo-loading-circle {
    position: absolute;
    inset: 0;
    border-radius: 30px;
    border: 4px solid var(--bd, #e2e8f0);
    border-top: 4px solid var(--pur, #7c3aed);
    border-right-color: rgba(14, 165, 233, 0.78);
    animation: spin 1s linear infinite;
}
.logo-loading-wrapper img {
    width: 78px;
    height: 78px;
    object-fit: contain;
    border-radius: 22px;
    filter: drop-shadow(0 0 1px rgba(255, 255, 255, 0.9));
    animation: pulse 1.5s ease-in-out infinite;
}
@media (max-width: 575px) {
    .logo-loading-wrapper {
        width: 104px;
        height: 104px;
        border-radius: 26px;
    }
    .logo-loading-circle {
        border-width: 3px;
        border-radius: 26px;
    }
    .logo-loading-wrapper img {
        width: 66px;
        height: 66px;
        border-radius: 18px;
    }
}
/* Ensure animations are defined, though they might exist from guest.blade.php, it's safe to define them here too */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes pulse {
    0% { transform: scale(0.9); opacity: 0.8; }
    50% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(0.9); opacity: 0.8; }
}
</style>

<div id="logoutTransitionOverlay">
    <div class="logo-loading-wrapper">
        <div class="logo-loading-circle"></div>
        <img src="{{ asset('img/logo.png') }}" alt="Logging out...">
    </div>
    <h4 style="color:var(--tx); font-weight:600; font-size:1.2rem; letter-spacing:0.5px;">Logging out...</h4>
    <p style="color:var(--tx3); font-size:0.9rem;">Please wait</p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutForms = document.querySelectorAll('form[action="{{ route('logout') }}"]');
        logoutForms.forEach(form => {
            form.addEventListener('submit', function() {
                const overlay = document.getElementById('logoutTransitionOverlay');
                if (overlay) overlay.classList.add('active');
            });
        });
    });
</script>
