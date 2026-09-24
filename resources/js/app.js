const menuButton = document.querySelector('[data-menu-button]');
const header = document.querySelector('[data-header]');
const menuLabel = document.querySelector('[data-menu-label]');

const setMenuState = (isOpen) => {
    header?.classList.toggle('menu-open', isOpen);
    document.body.classList.toggle('menu-locked', isOpen);
    menuButton?.setAttribute('aria-expanded', String(isOpen));
    menuButton?.setAttribute('aria-label', isOpen ? 'Close main menu' : 'Open main menu');
    if (menuLabel) menuLabel.textContent = isOpen ? 'Close' : 'Menu';
};

menuButton?.addEventListener('click', () => {
    setMenuState(! header?.classList.contains('menu-open'));
});

document.querySelectorAll('.site-nav a, .field-nav a, .field-header-actions a').forEach((link) => {
    link.addEventListener('click', () => {
        setMenuState(false);
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && header?.classList.contains('menu-open')) {
        setMenuState(false);
        menuButton?.focus();
    }
});

const sidebar = document.querySelector('[data-sidebar]');
document.querySelector('[data-admin-menu]')?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
});

const reveals = document.querySelectorAll('.reveal');

if ('IntersectionObserver' in window && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    reveals.forEach((element) => observer.observe(element));
} else {
    reveals.forEach((element) => element.classList.add('visible'));
}

const voiceRecorder = document.querySelector('[data-voice-recorder]');

if (voiceRecorder) {
    const recordButton = voiceRecorder.querySelector('[data-record-button]');
    const stopButton = voiceRecorder.querySelector('[data-stop-button]');
    const status = voiceRecorder.querySelector('[data-recording-status]');
    const timer = voiceRecorder.querySelector('[data-recording-timer]');
    const input = voiceRecorder.querySelector('[data-audio-input]');
    const preview = voiceRecorder.querySelector('[data-recording-preview]');
    const audio = voiceRecorder.querySelector('[data-audio-preview]');
    let mediaRecorder;
    let mediaStream;
    let chunks = [];
    let elapsed = 0;
    let ticker;

    const updateTimer = () => {
        const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
        const seconds = String(elapsed % 60).padStart(2, '0');
        timer.textContent = `${minutes}:${seconds}`;
    };

    const stopRecording = () => {
        if (mediaRecorder?.state === 'recording') mediaRecorder.stop();
    };

    recordButton?.addEventListener('click', async () => {
        if (! navigator.mediaDevices?.getUserMedia || ! window.MediaRecorder) {
            status.textContent = 'Recording is unavailable—upload a voice note below';
            input?.focus();
            return;
        }

        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const preferredType = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/webm'].find((type) => MediaRecorder.isTypeSupported(type));
            mediaRecorder = new MediaRecorder(mediaStream, preferredType ? { mimeType: preferredType } : undefined);
            chunks = [];
            elapsed = 0;
            updateTimer();

            mediaRecorder.addEventListener('dataavailable', (event) => {
                if (event.data.size > 0) chunks.push(event.data);
            });
            mediaRecorder.addEventListener('stop', () => {
                clearInterval(ticker);
                mediaStream?.getTracks().forEach((track) => track.stop());
                const mimeType = mediaRecorder.mimeType || 'audio/webm';
                const extension = mimeType.includes('mp4') ? 'm4a' : 'webm';
                const blob = new Blob(chunks, { type: mimeType });
                const file = new File([blob], `field-voice-${Date.now()}.${extension}`, { type: mimeType });
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                audio.src = URL.createObjectURL(blob);
                preview.hidden = false;
                status.textContent = 'Voice note ready';
                recordButton.classList.remove('recording');
                recordButton.disabled = false;
                stopButton.disabled = true;
            });

            mediaRecorder.start();
            status.textContent = 'Recording… speak clearly';
            recordButton.classList.add('recording');
            recordButton.disabled = true;
            stopButton.disabled = false;
            ticker = window.setInterval(() => {
                elapsed += 1;
                updateTimer();
                if (elapsed >= 180) stopRecording();
            }, 1000);
        } catch {
            status.textContent = 'Microphone permission was not granted—upload a voice note below';
            input?.focus();
        }
    });

    stopButton?.addEventListener('click', stopRecording);
    input?.addEventListener('change', () => {
        if (input.files?.length) status.textContent = `${input.files[0].name} ready`;
    });
}
