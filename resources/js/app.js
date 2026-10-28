import './bootstrap';

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    let submitting = false;

    form.addEventListener('submit', (event) => {
        if (submitting) {
            event.preventDefault();

            return;
        }

        const effectiveDate = form.querySelector('[name="effective_date"]')?.value;
        const confirmation = effectiveDate
            ? `${form.dataset.confirm} Effective date: ${effectiveDate}.`
            : form.dataset.confirm;

        if (!window.confirm(confirmation)) {
            event.preventDefault();

            return;
        }

        submitting = true;
        const submitButton = form.querySelector('[type="submit"]');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = form.dataset.confirmSubmitLabel ?? 'Submitting…';
        }
    });
});

document.querySelectorAll('form[data-attendance-punch]').forEach((form) => {
    const submitButton = form.querySelector('[type="submit"]');
    const message = document.querySelector('#geolocation-message');
    const diagnostics = document.querySelector('#attendance-diagnostics');
    let requestingLocation = false;
    let locationReady = false;

    const showDiagnostics = (values) => {
        if (!diagnostics) {
            return;
        }

        diagnostics.open = true;
        Object.entries(values).forEach(([key, value]) => {
            const target = diagnostics.querySelector(`[data-diagnostic="${key}"]`);

            if (target) {
                target.textContent = String(value);
            }
        });
    };

    form.addEventListener('submit', (event) => {
        if (locationReady) {
            return;
        }

        event.preventDefault();

        if (requestingLocation) {
            return;
        }

        if (!navigator.geolocation) {
            message.textContent = 'Location is unavailable in this browser. Use a supported browser and try again.';
            showDiagnostics({
                category: 'browser_geolocation_unavailable',
                browser_error_code: 'Unavailable',
                reported_accuracy_meters: 'Not available',
                latitude_valid: 'No reading',
                longitude_valid: 'No reading',
                accuracy_valid: 'No reading',
                server_rejection: 'Not submitted',
                server_validation_fields: 'None',
            });

            return;
        }

        requestingLocation = true;
        submitButton.disabled = true;
        submitButton.textContent = 'Getting location…';
        message.textContent = 'Requesting your location…';

        navigator.geolocation.getCurrentPosition((position) => {
            const { latitude, longitude, accuracy } = position.coords;
            const latitudeValid = Number.isFinite(latitude) && latitude >= -90 && latitude <= 90;
            const longitudeValid = Number.isFinite(longitude) && longitude >= -180 && longitude <= 180;
            const accuracyIsPositiveFinite = Number.isFinite(accuracy) && accuracy > 0;
            const accuracyValid = accuracyIsPositiveFinite && accuracy <= 100;
            const reportedAccuracy = Number.isFinite(accuracy) ? `${accuracy.toFixed(1)} metres` : 'Not available';

            // GeolocationCoordinates.accuracy is already in metres; the server uses the same unit.
            if (!latitudeValid || !longitudeValid || !accuracyValid) {
                requestingLocation = false;
                submitButton.disabled = false;
                submitButton.textContent = form.dataset.retryLabel ?? 'Retry location request';
                const category = !latitudeValid || !longitudeValid
                    ? 'browser_coordinates_failed_basic_validation'
                    : !accuracyIsPositiveFinite
                        ? 'browser_accuracy_invalid'
                        : 'browser_accuracy_exceeds_100_metres';
                showDiagnostics({
                    category,
                    browser_error_code: 'None',
                    reported_accuracy_meters: reportedAccuracy,
                    latitude_valid: latitudeValid ? 'Pass' : 'Fail',
                    longitude_valid: longitudeValid ? 'Pass' : 'Fail',
                    accuracy_valid: accuracyValid ? 'Pass' : 'Fail',
                    server_rejection: 'Not submitted; rejected by browser checks',
                    server_validation_fields: 'None',
                });
                message.textContent = !latitudeValid || !longitudeValid
                    ? `The browser returned invalid coordinate values. Request a fresh reading with “${submitButton.textContent}”.`
                    : `The location reading is too inaccurate. Move to a place with a clearer GPS signal, then select “${submitButton.textContent}” for a fresh reading.`;

                return;
            }

            form.querySelector('[name="latitude"]').value = String(latitude);
            form.querySelector('[name="longitude"]').value = String(longitude);
            form.querySelector('[name="accuracy_meters"]').value = String(accuracy);
            locationReady = true;
            message.textContent = 'Location received. Sending your punch for server validation…';
            showDiagnostics({
                category: 'browser_checks_passed_waiting_for_server',
                browser_error_code: 'None',
                reported_accuracy_meters: reportedAccuracy,
                latitude_valid: 'Pass',
                longitude_valid: 'Pass',
                accuracy_valid: 'Pass',
                server_rejection: 'Pending',
                server_validation_fields: 'Pending',
            });
            HTMLFormElement.prototype.submit.call(form);
        }, (error) => {
            requestingLocation = false;
            submitButton.disabled = false;
            submitButton.textContent = form.dataset.retryLabel ?? 'Retry location request';
            const categories = {
                [error.PERMISSION_DENIED]: 'browser_permission_denied',
                [error.POSITION_UNAVAILABLE]: 'browser_position_unavailable',
                [error.TIMEOUT]: 'browser_location_timeout',
            };
            showDiagnostics({
                category: categories[error.code] ?? 'browser_geolocation_error',
                browser_error_code: error.code,
                reported_accuracy_meters: 'Not available',
                latitude_valid: 'No reading',
                longitude_valid: 'No reading',
                accuracy_valid: 'No reading',
                server_rejection: 'Not submitted',
                server_validation_fields: 'None',
            });
            message.textContent = error.code === error.PERMISSION_DENIED
                ? `Location permission was denied. Allow location access in your browser, then select “${submitButton.textContent}”.`
                : error.code === error.POSITION_UNAVAILABLE
                    ? `Your location could not be determined. Check that location services are enabled, then select “${submitButton.textContent}”.`
                    : error.code === error.TIMEOUT
                        ? `The location request timed out. Try again where your device has a better signal by selecting “${submitButton.textContent}”.`
                        : `Your location could not be read. Check your device settings, then select “${submitButton.textContent}”.`;
        }, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0,
        });
    });
});

const navigationToggle = document.querySelector('#navigation-toggle');
const navigation = document.querySelector('#primary-navigation');
const navigationClose = document.querySelector('#navigation-close');
const navigationBackdrop = document.querySelector('#navigation-backdrop');
const authenticatedShell = document.querySelector('#authenticated-shell');
const pageContent = navigation?.nextElementSibling;
const skipLink = document.querySelector('a[href="#main-content"]');

if (navigationToggle && navigation && navigationClose && navigationBackdrop && authenticatedShell && pageContent) {
    const desktopQuery = window.matchMedia('(min-width: 48rem)');
    const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const originalBodyOverflow = document.body.style.overflow;
    let mobileOpen = false;
    let desktopCollapsed = false;

    const setInert = (element, inert) => {
        if (inert) {
            element.setAttribute('inert', '');
        } else {
            element.removeAttribute('inert');
        }
    };

    const updateNavigation = () => {
        const desktop = desktopQuery.matches;

        if (desktop) {
            navigation.classList.remove('hidden', 'mobile-drawer-open', 'mobile-drawer-closed');
            navigation.setAttribute('aria-hidden', 'false');
            setInert(navigation, false);
            authenticatedShell.classList.toggle('sidebar-collapsed', desktopCollapsed);
            navigationBackdrop.classList.remove('navigation-backdrop-open');
            navigationBackdrop.setAttribute('aria-hidden', 'true');
            setInert(navigationBackdrop, true);
            setInert(pageContent, false);
            if (skipLink) {
                setInert(skipLink, false);
            }
            document.body.style.overflow = originalBodyOverflow;
            navigationToggle.setAttribute('aria-expanded', desktopCollapsed ? 'false' : 'true');
            mobileOpen = false;

            return;
        }

        authenticatedShell.classList.remove('sidebar-collapsed');
        if (mobileOpen) {
            navigation.classList.remove('hidden');
            navigation.classList.add('mobile-drawer-closed');
            window.requestAnimationFrame(() => {
                if (mobileOpen && !desktopQuery.matches) {
                    navigation.classList.remove('mobile-drawer-closed');
                    navigation.classList.add('mobile-drawer-open');
                }
            });
        } else {
            navigation.classList.remove('mobile-drawer-open');
            navigation.classList.add('mobile-drawer-closed');
            window.setTimeout(() => {
                if (!mobileOpen && !desktopQuery.matches) {
                    navigation.classList.add('hidden');
                }
            }, reducedMotionQuery.matches ? 0 : 250);
        }
        navigation.setAttribute('aria-hidden', mobileOpen ? 'false' : 'true');
        setInert(navigation, !mobileOpen);
        navigationBackdrop.classList.toggle('navigation-backdrop-open', mobileOpen);
        navigationBackdrop.setAttribute('aria-hidden', mobileOpen ? 'false' : 'true');
        setInert(navigationBackdrop, !mobileOpen);
        setInert(pageContent, mobileOpen);
        if (skipLink) {
            setInert(skipLink, mobileOpen);
        }
        document.body.style.overflow = mobileOpen ? 'hidden' : originalBodyOverflow;
        navigationToggle.setAttribute('aria-expanded', mobileOpen ? 'true' : 'false');
    };

    const closeMobileNavigation = (restoreFocus = false) => {
        if (desktopQuery.matches || !mobileOpen) {
            return;
        }

        mobileOpen = false;
        updateNavigation();

        if (restoreFocus) {
            navigationToggle.focus();
        }
    };

    navigationToggle.addEventListener('click', () => {
        if (desktopQuery.matches) {
            desktopCollapsed = !desktopCollapsed;
        } else {
            mobileOpen = !mobileOpen;
        }

        updateNavigation();

        if (mobileOpen) {
            navigationClose.focus();
        }
    });

    navigationClose.addEventListener('click', () => closeMobileNavigation(true));
    navigationBackdrop.addEventListener('click', () => closeMobileNavigation(true));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !desktopQuery.matches && mobileOpen) {
            closeMobileNavigation(true);
        }
    });

    desktopQuery.addEventListener('change', () => {
        if (!desktopQuery.matches && navigation.contains(document.activeElement)) {
            navigationToggle.focus();
        }

        mobileOpen = false;
        desktopCollapsed = false;
        updateNavigation();

        if (desktopQuery.matches && document.activeElement === navigationClose) {
            navigationToggle.focus();
        }
    });

    updateNavigation();
}
