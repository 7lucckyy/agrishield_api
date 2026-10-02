const menuButton = document.querySelector('[data-menu-button]');
const header = document.querySelector('[data-header]');
const menuLabel = document.querySelector('[data-menu-label]');

const setMenuState = (isOpen) => {
    header?.classList.toggle('menu-open', isOpen);
    document.body.classList.toggle('menu-locked', isOpen);
    menuButton?.setAttribute('aria-expanded', String(isOpen));
    menuButton?.setAttribute('aria-label', isOpen ? menuButton.dataset.closeLabel : menuButton.dataset.openLabel);
    if (menuLabel) menuLabel.textContent = isOpen ? menuButton?.dataset.menuLabelOpen : menuButton?.dataset.menuLabelClosed;
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

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const depthCards = document.querySelectorAll('[data-motion="depth-card"]');

if (! reducedMotion.matches && window.matchMedia('(pointer: fine)').matches) {
    depthCards.forEach((card) => {
        card.addEventListener('pointermove', (event) => {
            const bounds = card.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - 0.5;
            const y = (event.clientY - bounds.top) / bounds.height - 0.5;
            card.style.setProperty('--depth-rotate-x', `${(-y * 2.4).toFixed(2)}deg`);
            card.style.setProperty('--depth-rotate-y', `${(x * 3).toFixed(2)}deg`);
        });
        card.addEventListener('pointerleave', () => {
            card.style.removeProperty('--depth-rotate-x');
            card.style.removeProperty('--depth-rotate-y');
        });
    });
}

const parallaxMedia = document.querySelectorAll('[data-parallax-media]');

if (parallaxMedia.length && ! reducedMotion.matches) {
    let parallaxFrame;
    const updateParallax = () => {
        parallaxFrame = undefined;
        const viewportCenter = window.innerHeight / 2;

        parallaxMedia.forEach((image) => {
            const bounds = image.parentElement.getBoundingClientRect();
            const distance = (bounds.top + bounds.height / 2 - viewportCenter) / window.innerHeight;
            image.style.setProperty('--media-shift', `${Math.max(-18, Math.min(18, distance * -18)).toFixed(1)}px`);
        });
    };
    const requestParallaxUpdate = () => {
        if (! parallaxFrame) parallaxFrame = window.requestAnimationFrame(updateParallax);
    };

    window.addEventListener('scroll', requestParallaxUpdate, { passive: true });
    window.addEventListener('resize', requestParallaxUpdate);
    updateParallax();
}

const fieldTerrain = document.querySelector('[data-field-terrain]');

if (fieldTerrain) {
    import('three').then((THREE) => {
        const canvas = fieldTerrain.querySelector('[data-field-terrain-canvas]');

        if (! canvas) return;

        let renderer;
        try {
            renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true, powerPreference: 'high-performance' });
        } catch {
            fieldTerrain.classList.add('terrain-unavailable');
            return;
        }

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(34, 1, 0.1, 40);
        const parcelGroup = new THREE.Group();
        const lineMaterial = new THREE.LineBasicMaterial({ color: 0xf5df8b, transparent: true, opacity: 0.48 });
        const palette = [0x1f5c3b, 0x2f7549, 0x3f8757, 0x548f5d, 0x265f42, 0x6b925a];
        const plots = [
            [-1.62, 1.02, 1.62, 1.2, 0.2], [0.06, 1.08, 1.55, 1.06, 0.34], [1.57, 1.03, 1.34, 1.2, 0.15],
            [-1.72, -0.18, 1.34, 1.05, 0.28], [-0.25, -0.08, 1.46, 1.18, 0.42], [1.45, -0.2, 1.7, 1.03, 0.24],
            [-1.48, -1.3, 1.78, 0.94, 0.17], [0.27, -1.28, 1.53, 0.96, 0.3], [1.68, -1.34, 1.08, 0.82, 0.12],
        ];

        plots.forEach(([x, y, width, height, elevation], index) => {
            const geometry = new THREE.BoxGeometry(width, height, elevation, 1, 1, 1);
            const material = new THREE.MeshStandardMaterial({
                color: palette[index % palette.length],
                roughness: 0.92,
                metalness: 0,
            });
            const plot = new THREE.Mesh(geometry, material);
            plot.position.set(x, y, elevation / 2);
            parcelGroup.add(plot);

            const edges = new THREE.LineSegments(new THREE.EdgesGeometry(geometry), lineMaterial);
            edges.position.copy(plot.position);
            parcelGroup.add(edges);
        });

        const boundaryPoints = [
            [-2.52, 1.73], [-0.8, 1.77], [0.72, 1.72], [2.5, 1.55], [2.58, 0.2],
            [2.35, -1.86], [0.62, -1.92], [-1.12, -1.85], [-2.48, -1.44], [-2.65, 0.05], [-2.52, 1.73],
        ].map(([x, y]) => new THREE.Vector3(x, y, 0.5));
        const boundary = new THREE.Line(
            new THREE.BufferGeometry().setFromPoints(boundaryPoints),
            new THREE.LineBasicMaterial({ color: 0xe8c760, transparent: true, opacity: 0.9 }),
        );
        parcelGroup.add(boundary);

        [[-1.05, 0.65, 0.55], [0.37, -0.14, 0.72], [1.72, 0.56, 0.44]].forEach(([x, y, z]) => {
            const marker = new THREE.Mesh(
                new THREE.SphereGeometry(0.075, 16, 12),
                new THREE.MeshBasicMaterial({ color: 0xffdf72 }),
            );
            marker.position.set(x, y, z);
            parcelGroup.add(marker);

            const stem = new THREE.Line(
                new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(x, y, 0.05), new THREE.Vector3(x, y, z)]),
                new THREE.LineBasicMaterial({ color: 0xffdf72, transparent: true, opacity: 0.68 }),
            );
            parcelGroup.add(stem);
        });

        parcelGroup.rotation.set(-0.92, 0, -0.16);
        parcelGroup.position.y = -0.2;
        scene.add(parcelGroup);
        scene.add(new THREE.HemisphereLight(0xf4f0cf, 0x071b13, 2.1));

        const keyLight = new THREE.DirectionalLight(0xffedaf, 3.4);
        keyLight.position.set(-3, 4, 7);
        scene.add(keyLight);

        camera.position.set(0, 0.2, 8.2);
        camera.lookAt(0, -0.15, 0);
        renderer.setClearColor(0x000000, 0);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.6));
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.08;

        let pointerX = 0;
        let pointerY = 0;
        let isVisible = true;
        let animationFrame;

        const resize = () => {
            const bounds = fieldTerrain.getBoundingClientRect();
            if (bounds.width === 0 || bounds.height === 0) return;

            renderer.setSize(bounds.width, bounds.height, false);
            camera.aspect = bounds.width / bounds.height;
            camera.updateProjectionMatrix();
            renderer.render(scene, camera);
        };
        const render = (time = 0) => {
            if (! isVisible) {
                animationFrame = undefined;
                return;
            }

            const targetX = -0.92 + pointerY * 0.045;
            const targetZ = -0.16 + pointerX * 0.065;
            parcelGroup.rotation.x += (targetX - parcelGroup.rotation.x) * 0.045;
            parcelGroup.rotation.z += (targetZ - parcelGroup.rotation.z) * 0.045;
            parcelGroup.position.y = -0.2 + Math.sin(time * 0.00055) * 0.035;
            renderer.render(scene, camera);
            animationFrame = window.requestAnimationFrame(render);
        };

        fieldTerrain.addEventListener('pointermove', (event) => {
            const bounds = fieldTerrain.getBoundingClientRect();
            pointerX = ((event.clientX - bounds.left) / bounds.width - 0.5) * 2;
            pointerY = ((event.clientY - bounds.top) / bounds.height - 0.5) * 2;
        });
        fieldTerrain.addEventListener('pointerleave', () => {
            pointerX = 0;
            pointerY = 0;
        });

        if ('ResizeObserver' in window) {
            const resizeObserver = new ResizeObserver(resize);
            resizeObserver.observe(fieldTerrain);
        } else {
            window.addEventListener('resize', resize);
        }

        if ('IntersectionObserver' in window) {
            const visibilityObserver = new IntersectionObserver(([entry]) => {
                isVisible = entry.isIntersecting;
                if (isVisible && ! reducedMotion.matches && ! animationFrame) {
                    animationFrame = window.requestAnimationFrame(render);
                }
            }, { threshold: 0.05 });
            visibilityObserver.observe(fieldTerrain);
        }

        fieldTerrain.classList.add('terrain-ready');
        resize();
        if (reducedMotion.matches) {
            renderer.render(scene, camera);
        } else {
            animationFrame = window.requestAnimationFrame(render);
        }
    }).catch(() => fieldTerrain.classList.add('terrain-unavailable'));
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
