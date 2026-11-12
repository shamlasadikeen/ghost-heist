
(() => {
    const MUSIC_KEY = 'ghostHeistSiteMusicPosition';
    const ENABLED_KEY = 'ghostHeistSiteMusicEnabled';

    const siteMusic = new Audio(
        'https://cdn.freesound.org/previews/721/721059_15232790-hq.mp3'
    );

    siteMusic.loop = true;
    siteMusic.volume = 0.03;
    siteMusic.preload = 'auto';

    let musicStarted = false;

    function savePosition() {
        if (Number.isFinite(siteMusic.currentTime)) {
            sessionStorage.setItem(
                MUSIC_KEY,
                String(siteMusic.currentTime)
            );
        }
    }

    function startMusic() {
        if (musicStarted) return;

        siteMusic.play()
            .then(() => {
                musicStarted = true;
                sessionStorage.setItem(ENABLED_KEY, 'true');

                document.removeEventListener('click', startMusic);
                document.removeEventListener('keydown', startMusic);
            })
            .catch(error => {
                console.warn('Website music autoplay was blocked:', error);
            });
    }

    siteMusic.addEventListener('loadedmetadata', () => {
        const savedPosition = Number(
            sessionStorage.getItem(MUSIC_KEY)
        );

        if (
            Number.isFinite(savedPosition) &&
            savedPosition >= 0 &&
            savedPosition < siteMusic.duration
        ) {
            siteMusic.currentTime = savedPosition;
        }

        // Automatically resume if music was previously started.
        if (sessionStorage.getItem(ENABLED_KEY) === 'true') {
            startMusic();
        }
    });

    siteMusic.addEventListener('timeupdate', savePosition);
    window.addEventListener('pagehide', savePosition);

    // Try autoplay when the page loads.
    if (sessionStorage.getItem(ENABLED_KEY) === 'true') {
        startMusic();
    }

    // Retry if the browser blocks autoplay.
    document.addEventListener('click', startMusic);
    document.addEventListener('keydown', startMusic);
})();
