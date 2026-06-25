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
}
.logo-loading-circle {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 4px solid var(--bd, #e2e8f0);
    border-top: 4px solid var(--pur, #7c3aed);
    animation: spin 1s linear infinite;
}
.logo-loading-wrapper img {
    width: 70px;
    height: 70px;
    object-fit: contain;
    animation: pulse 1.5s ease-in-out infinite;
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
