document.addEventListener('DOMContentLoaded', function () {
    var languagePicker = document.querySelector('[data-language-picker]');

    if (languagePicker) {
        var languageTrigger = languagePicker.querySelector('summary');
        var loginTopbar = languagePicker.closest('.rn-login-topbar');
        var languageButtons = Array.prototype.slice.call(languagePicker.querySelectorAll('.rn-language-menu button'));
        var setLanguagePickerState = function () {
            var isOpen = languagePicker.open;
            languageTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.classList.toggle('language-picker-open', isOpen);
            if (loginTopbar) loginTopbar.classList.toggle('language-picker-open', isOpen);
        };

        languageTrigger.setAttribute('aria-expanded', languagePicker.open ? 'true' : 'false');
        languagePicker.addEventListener('toggle', setLanguagePickerState);
        languagePicker.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && languagePicker.open) {
                event.preventDefault();
                languagePicker.open = false;
                languageTrigger.focus();
                return;
            }

            if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && event.target === languageTrigger) {
                event.preventDefault();
                languagePicker.open = true;
                setLanguagePickerState();
                languageButtons[event.key === 'ArrowDown' ? 0 : languageButtons.length - 1]?.focus();
                return;
            }

            if (event.target.matches('.rn-language-menu button') && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
                event.preventDefault();
                var index = languageButtons.indexOf(event.target);
                var direction = event.key === 'ArrowDown' ? 1 : -1;
                languageButtons[(index + direction + languageButtons.length) % languageButtons.length]?.focus();
            }
        });
        document.addEventListener('pointerdown', function (event) {
            if (languagePicker.open && !languagePicker.contains(event.target)) {
                languagePicker.open = false;
                setLanguagePickerState();
            }
        });
        languagePicker.addEventListener('focusout', function () {
            window.setTimeout(function () {
                if (!languagePicker.contains(document.activeElement) && languagePicker.open) {
                    languagePicker.open = false;
                    setLanguagePickerState();
                }
            }, 0);
        });
    }

    var password = document.getElementById('password');
    var toggle = document.querySelector('.rn-password-toggle');

    if (password && toggle) {
        toggle.addEventListener('click', function () {
            var reveal = password.type === 'password';
            password.type = reveal ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            toggle.setAttribute('aria-label', reveal ? toggle.dataset.hideLabel : toggle.dataset.showLabel);
            password.focus({ preventScroll: true });
        });
    }

    var form = document.querySelector('.rn-login-form');
    var submit = form && form.querySelector('.rn-sign-in-button');
    if (form && submit) {
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) return;
            submit.disabled = true;
            submit.setAttribute('aria-busy', 'true');
        });
    }
});
