document.querySelectorAll('[data-hero-video-play]').forEach((button) => {
    const video = button.closest('.hero-video-stage')?.querySelector('video');
    const source = video?.querySelector('source[data-src]');
    if (!video || !source) return;

    button.addEventListener('click', () => {
        if (!source.src) {
            source.src = source.dataset.src;
            video.load();
        }

        video.controls = true;
        video.addEventListener('playing', () => {
            button.hidden = true;
        }, { once: true });

        const playAttempt = video.play();
        playAttempt?.catch(() => {
            button.hidden = true;
        });
    });
});
