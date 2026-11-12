
const gameSounds = {
    footsteps: {
    query: 'footsteps',
    volume: 0.35
},
    interaction: {
        query: 'cartoon pop',
        volume: 0.55
    },
    success: {
        query: 'happy cartoon success jingle',
        volume: 0.6
    },
    mistake: {
        query: 'funny cartoon wrong answer buzzer',
        volume: 0.5
    },
    caught: {
        query: 'surprise',
        volume: 0.65
    },
    treasure: {
        query: 'magical sparkle victory sound',
        volume: 0.7
    }
};

const soundCache = {};

async function loadGameSound(soundName) {
    if (soundCache[soundName]) {
        return soundCache[soundName];
    }

    const sound = gameSounds[soundName];

    if (!sound) {
        return null;
    }

    const request = fetch(
        '../api/freesound_search.php?query=' +
        encodeURIComponent(sound.query)
    )
        .then(response => {
            if (!response.ok) {
                throw new Error('Sound search failed.');
            }

            return response.json();
        })
        .then(data => {
            if (!data.success || !data.results.length) {
                throw new Error('No matching sounds found.');
            }

            // Prefer CC0 sounds to avoid attribution requirements.
            const cc0Sound = data.results.find(result =>
                result.license &&
                result.license.includes('publicdomain/zero/1.0')
            );

            if (!cc0Sound) {
    throw new Error('No CC0 sound found for: ' + sound.query);
}

const selectedSound = cc0Sound;
const preview = selectedSound.previews?.['preview-hq-mp3'];

            if (!preview) {
                throw new Error('Sound preview unavailable.');
            }

            return {
                audio: new Audio(preview),
                license: selectedSound.license,
                creator: selectedSound.username,
                name: selectedSound.name,
                volume: sound.volume
            };
        });

    soundCache[soundName] = request;

    try {
        return await request;
    } catch (error) {
        delete soundCache[soundName];
        console.warn('Ghost Heist sound:', error);
        return null;
    }
}

async function playGameSound(soundName) {
    const sound = await loadGameSound(soundName);

    if (!sound) {
        return;
    }

    try {
        sound.audio.pause();
        sound.audio.currentTime = 0;
        sound.audio.volume = sound.volume;
        await sound.audio.play();
    } catch (error) {
        console.warn('Could not play sound:', error);
    }
}

// Ghost Heist background music
const backgroundMusic = new Audio(
    'https://cdn.freesound.org/previews/714/714736_15232790-hq.mp3'
);

backgroundMusic.loop = true;
backgroundMusic.volume = 0.05;
backgroundMusic.preload = 'auto';

let backgroundMusicStarted = false;
let backgroundMusicStarting = false;

function startBackgroundMusic() {
    if (backgroundMusicStarted || backgroundMusicStarting) {
        return;
    }

    backgroundMusicStarting = true;

    backgroundMusic.play()
        .then(() => {
            backgroundMusicStarted = true;
            document.removeEventListener('keydown', startBackgroundMusic);
            document.removeEventListener('pointerdown', startBackgroundMusic);
        })
        .catch(error => {
            backgroundMusicStarting = false;
            console.warn('Background music could not start:', error);
        });
}

document.addEventListener('keydown', startBackgroundMusic);
document.addEventListener('pointerdown', startBackgroundMusic);