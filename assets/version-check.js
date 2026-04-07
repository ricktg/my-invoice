(() => {
    const chip = document.getElementById('version-chip');
    const current = document.getElementById('version-chip-current');
    const updateBadge = document.getElementById('version-update-badge');
    const modalBackdrop = document.getElementById('version-modal-backdrop');
    const modalClose = document.getElementById('version-modal-close');
    const modalSummary = document.getElementById('version-modal-summary');
    const modalUpdateContent = document.getElementById('version-modal-update-content');
    const modalNoUpdateContent = document.getElementById('version-modal-no-update-content');
    const versionNoUpdateMessage = document.getElementById('version-no-update-message');
    const versionCurrentValue = document.getElementById('version-current-value');
    const versionLatestValue = document.getElementById('version-latest-value');
    const versionReleaseLink = document.getElementById('version-release-link');
    const versionCommandBash = document.getElementById('version-command-bash');
    const versionCommandPs = document.getElementById('version-command-ps');

    if (!chip || !current || !updateBadge || !modalBackdrop || !modalClose || !modalSummary || !modalUpdateContent || !modalNoUpdateContent || !versionNoUpdateMessage || !versionCurrentValue || !versionLatestValue || !versionReleaseLink || !versionCommandBash || !versionCommandPs) {
        return;
    }

    const versionCheckUrl = chip.dataset.versionCheckUrl;
    if (!versionCheckUrl) {
        return;
    }

    let latestCheck = null;
    const formatVersionLabel = (version) => {
        const value = String(version || '').trim();
        if (value === '') {
            return '-';
        }

        return value.startsWith('v') || value.startsWith('V') ? value : `v${value}`;
    };

    const openModal = () => {
        modalBackdrop.hidden = false;
    };

    const closeModal = () => {
        modalBackdrop.hidden = true;
    };

    const runVersionCheck = async () => {
        try {
            const response = await fetch(versionCheckUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                return null;
            }

            return await response.json();
        } catch (e) {
            return null;
        }
    };

    const applyCheckResult = (data) => {
        if (!data) {
            return;
        }

        current.textContent = `v${data.current_version}`;
        if (data.has_update) {
            chip.classList.add('version-chip-has-update');
            updateBadge.hidden = false;
        } else {
            chip.classList.remove('version-chip-has-update');
            updateBadge.hidden = true;
        }
    };

    const renderModalFromCheck = (data) => {
        if (!data) {
            modalSummary.textContent = 'Não foi possível verificar versão agora.';
            modalUpdateContent.hidden = true;
            modalNoUpdateContent.hidden = true;
            return;
        }

        if (data.has_update) {
            modalSummary.textContent = 'Existe uma versão mais nova disponível.';
            modalUpdateContent.hidden = false;
            modalNoUpdateContent.hidden = true;
            versionCurrentValue.textContent = formatVersionLabel(data.current_version);
            versionLatestValue.textContent = formatVersionLabel(data.latest_version);
            if (data.release_url) {
                versionReleaseLink.hidden = false;
                versionReleaseLink.href = data.release_url;
            } else {
                versionReleaseLink.hidden = true;
                versionReleaseLink.removeAttribute('href');
            }
            versionCommandBash.textContent = `./scripts/upgrade.sh ${data.latest_version}`;
            versionCommandPs.textContent = `.\\scripts\\upgrade.ps1 -Version ${data.latest_version}`;
            return;
        }

        modalSummary.textContent = data.error ? 'Sem atualização (verificação parcial).' : 'Sistema atualizado.';
        versionNoUpdateMessage.textContent = data.error
            ? `Você já está na versão mais recente, mas a verificação retornou: ${data.error}`
            : `Você já está na versão mais recente (${formatVersionLabel(data.latest_version || data.current_version)}).`;
        modalUpdateContent.hidden = true;
        modalNoUpdateContent.hidden = false;
    };

    runVersionCheck().then((data) => {
        latestCheck = data;
        applyCheckResult(data);
    });

    chip.addEventListener('click', async () => {
        chip.disabled = true;
        modalSummary.textContent = 'Verificando versões...';
        modalUpdateContent.hidden = true;
        modalNoUpdateContent.hidden = true;
        openModal();

        const data = await runVersionCheck();
        latestCheck = data ?? latestCheck;
        if (!latestCheck) {
            renderModalFromCheck(null);
            chip.disabled = false;
            return;
        }

        applyCheckResult(latestCheck);
        renderModalFromCheck(latestCheck);
        chip.disabled = false;
    });

    modalClose.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', (event) => {
        if (event.target === modalBackdrop) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modalBackdrop.hidden) {
            closeModal();
        }
    });
})();
