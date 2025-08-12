document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);

    // Target query parameters
    const triggerParams = ['status', 'validate'];

    // Check if any of the target parameters exist
    const shouldTrigger = triggerParams.some(param => urlParams.has(param));

    if (!shouldTrigger) return;

    // List of popup IDs with delay in milliseconds
    const popupConfigs = [
        { id: 8149, delay: 5000 },    // 5 seconds
        { id: 8144, delay: 6000 }   // 6 seconds
        // Add more popups here like: { id: 1234, delay: 10000 }
    ];

    popupConfigs.forEach(config => {
        const popupSelector = '#jet-popup-' + config.id;

        const checkPopup = setInterval(function () {
            const popup = document.querySelector(popupSelector + '.jet-popup--show-state');

            if (popup && getComputedStyle(popup).display !== 'none') {
                clearInterval(checkPopup);

                setTimeout(function () {
                    const closeBtn = popup.querySelector('.jet-popup__close-button');

                    if (closeBtn) {
                        closeBtn.click();
                    } else {
                        popup.classList.remove('jet-popup--show-state', 'jet-popup--active', 'jet-popup--animation-fade');
                        popup.style.display = 'none';
                        document.body.classList.remove('jet-popup-opened');
                    }

                    // Remove matching query params from URL
                    triggerParams.forEach(param => urlParams.delete(param));
                    const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                    window.history.replaceState({}, '', newUrl);

                }, config.delay);
            }
        }, 300);
    });
});
