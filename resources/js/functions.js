'use strict';

bsCustomFileInput.init();

// Cookie law banner
document.querySelector('#cookie-banner-dismiss') && document.querySelector('#cookie-banner-dismiss').addEventListener('click', function () {
    setCookie('cookie_law', 1, new Date().getTime() + (10 * 365 * 24 * 60 * 60 * 1000), '/');
    document.querySelector('#cookie-banner').classList.add('d-none');
});

// Dark mode
document.querySelector('#dark-mode') && document.querySelector('#dark-mode').addEventListener('click', function (e) {
    e.preventDefault();

    // Update the sources
    document.querySelectorAll('[data-theme-target]').forEach(function (element) {
        element.setAttribute(element.dataset.themeTarget, document.querySelector('html').classList.contains('dark') ? element.dataset.themeLight : element.dataset.themeDark);
    });

    // Update the text
    this.querySelector('span').textContent = document.querySelector('html').classList.contains('dark') ? this.querySelector('span').dataset.textLight : this.querySelector('span').dataset.textDark;

    // Update the dark mode cookie
    setCookie('dark_mode', (document.querySelector('html').classList.contains('dark') ? 0 : 1), new Date().getTime() + (10 * 365 * 24 * 60 * 60 * 1000), '/');

    // Update the CSS class
    if (document.querySelector('html').classList.contains('dark')) {
        document.querySelector('html').classList.remove('dark');
    } else {
        document.querySelector('html').classList.add('dark');
    }
});

// Pricing plans
document.querySelector('#plan-month') && document.querySelector('#plan-month').addEventListener("click", function () {
    document.querySelectorAll('.plan-month').forEach(element => element.classList.add('d-block'));
    document.querySelectorAll('.plan-year').forEach(element => element.classList.remove('d-block'));
});

document.querySelector('#plan-year') && document.querySelector('#plan-year').addEventListener("click", function () {
    document.querySelectorAll('.plan-year').forEach(element => element.classList.add('d-block'));
    document.querySelectorAll('.plan-month').forEach(element => element.classList.remove('d-block'));
});

let updateSummary = (type) => {
    if (type == 'month') {
        document.querySelectorAll('.checkout-month').forEach(function (element) {
            element.classList.add('d-inline-block');
        });

        document.querySelectorAll('.checkout-year').forEach(function (element) {
            element.classList.remove('d-inline-block');
        });
    } else {
        document.querySelectorAll('.checkout-month').forEach(function (element) {
            element.classList.remove('d-inline-block');
        });

        document.querySelectorAll('.checkout-year').forEach(function (element) {
            element.classList.add('d-inline-block');
        });
    }
};

let updateBillingType = (value) => {
    // Show the offline instructions
    if (value == 'bank') {
        document.querySelector('#bank-instructions').classList.remove('d-none');
        document.querySelector('#bank-instructions').classList.add('d-block');
    }
    // Hide the offline instructions
    else {
        if (document.querySelector('#bank-instructions')) {
            document.querySelector('#bank-instructions').classList.add('d-none');
            document.querySelector('#bank-instructions').classList.remove('d-block');
        }
    }

    if (value == 'cryptocom' || value == 'coinbase' || value == 'bank') {
        document.querySelectorAll('.checkout-subscription').forEach(function (element) {
            element.classList.remove('d-block');
        });

        document.querySelectorAll('.checkout-subscription').forEach(function (element) {
            element.classList.add('d-none');
        });

        document.querySelectorAll('.checkout-one-time').forEach(function (element) {
            element.classList.add('d-block');
        });

        document.querySelectorAll('.checkout-one-time').forEach(function (element) {
            element.classList.remove('d-none');
        });
    } else {
        document.querySelectorAll('.checkout-subscription').forEach(function (element) {
            element.classList.remove('d-none');
        });

        document.querySelectorAll('.checkout-subscription').forEach(function (element) {
            element.classList.add('d-block');
        });

        document.querySelectorAll('.checkout-one-time').forEach(function (element) {
            element.classList.add('d-none');
        });

        document.querySelectorAll('.checkout-one-time').forEach(function (element) {
            element.classList.remove('d-block');
        });
    }
}

// Payment form
if (document.querySelector('#form-payment')) {
    let url = new URL(window.location.href);

    document.querySelectorAll('[name="interval"]').forEach(function (element) {
        if (element.checked) {
            updateSummary(element.value);
        }

        // Listen to interval changes
        element.addEventListener('change', function () {
            // Update the URL address
            url.searchParams.set('interval', element.value);

            history.pushState(null, null, url.href);

            updateSummary(element.value);
        });
    });

    document.querySelectorAll('[name="payment_processor"]').forEach(function (element) {
        if (element.checked) {
            updateBillingType(element.value);
        }

        // Listen to payment processor changes
        element.addEventListener('change', function () {
            // Update the URL address
            url.searchParams.set('payment', element.value);

            history.pushState(null, null, url.href);

            updateBillingType(element.value);
        });
    });

    // If the country value changes
    document.querySelector('#i-country').addEventListener('change', function () {
        // Remove the submit button
        document.querySelector('#form-payment').submit.remove();

        // Submit the form
        document.querySelector('#form-payment').submit();
    });
}

// Coupon form
if (document.querySelector('#form-coupon')) {
    document.querySelector('#i-type').addEventListener('change', function () {
        if (document.querySelector('#i-type').value == 1) {
            document.querySelector('#form-group-redeemable').classList.remove('d-none');
            document.querySelector('#form-group-discount').classList.add('d-none');
            document.querySelector('#i-percentage').setAttribute('disabled', 'disabled');
        } else {
            document.querySelector('#form-group-redeemable').classList.add('d-none');
            document.querySelector('#form-group-discount').classList.remove('d-none');
            document.querySelector('#i-percentage').removeAttribute('disabled');
        }
    });
}

// Handle the hiding of the displayed form containers
document.querySelectorAll('[data-show-container]').forEach(function (containerElement) {
    containerElement.querySelectorAll('[data-show-hide-action]').forEach(function (hideActionElement) {
        hideActionElement.addEventListener('click', function (e) {
            e.preventDefault();

            // Get all the show action elements
            document.querySelectorAll('[data-show="' + containerElement.dataset.showContainer + '"]').forEach(function (showActionElement) {
                // Display the show action elements
                showActionElement.classList.remove('d-none');
            });

            // Get all the content elements
            document.querySelectorAll('[data-show-content="' + containerElement.dataset.showContainer + '"]').forEach(function (showActionElement) {
                // Display the content elements
                showActionElement.classList.remove('d-none');
            });

            // Get all the displayed containers
            document.querySelectorAll('[data-show-container="' + containerElement.dataset.showContainer + '"]').forEach(function (containerElement) {
                // Display the show action elements
                containerElement.classList.add('d-none');
            });

            // Disable the displayed form inputs
            containerElement.querySelectorAll('[data-show-input]').forEach(function (containerInput) {
                containerInput.setAttribute('disabled', 'disabled');
            });
        });
    });
});

// Handle the showing of the form container to be shown
document.querySelectorAll('[data-show]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();

        // Get all the show action elements
        document.querySelectorAll('[data-show="' + element.dataset.show + '"]').forEach(function (showActionElement) {
            // Hide the show action element
            showActionElement.classList.add('d-none');
        });

        // Get all the content elements
        document.querySelectorAll('[data-show-content="' + element.dataset.show + '"]').forEach(function (showActionElement) {
            // Hide the content element
            showActionElement.classList.add('d-none');
        });

        // Get all the containers
        document.querySelectorAll('[data-show-container="' + element.dataset.show + '"]').forEach(function (elementContainer) {
            // Show the containers
            elementContainer.classList.remove('d-none');

            // Activate the inputs
            elementContainer.querySelectorAll('[data-show-input]').forEach(function (containerInput) {
                containerInput.removeAttribute('disabled');
            });
        });
    });
});

// Input disabling
document.querySelectorAll('[data-disable-input]').forEach(function (element) {
    element.addEventListener('change', function (e) {
        if (this.checked) {
            document.querySelector('#' + this.dataset.disableInput).setAttribute('disabled', 'disabled');
        } else {
            document.querySelector('#' + this.dataset.disableInput).removeAttribute('disabled');
        }
    });
});

// Whitelist SVG tags
jQuery.fn.tooltip.Constructor.Default.whiteList.svg = ['xmlns', 'class', 'viewbox', 'style'];
jQuery.fn.tooltip.Constructor.Default.whiteList.path = ['d'];
jQuery.fn.tooltip.Constructor.Default.whiteList.g = ['style'];
jQuery.fn.tooltip.Constructor.Default.whiteList.circle = ['cx', 'cy', 'r'];

// Clipboard
new ClipboardJS('[data-clipboard="true"]');

document.querySelectorAll('[data-clipboard-copy]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();

        try {
            let value = this.dataset.clipboardCopy;
            let tempInput = document.createElement('textarea');

            document.body.append(tempInput);

            // Set the input's value to the url to be copied
            tempInput.value = value;

            // Select the input's value to be copied
            tempInput.select();

            // Copy the url
            document.execCommand("copy");

            // Remove the temporary input
            tempInput.remove();
        } catch (e) {}
    });
});

// Tooltip
jQuery('[data-tooltip="true"]').tooltip({animation: true, trigger: 'hover', boundary: 'window'});

// Copy tooltip
jQuery('[data-tooltip-copy="true"]').tooltip({animation: true});

document.querySelectorAll('[data-tooltip-copy="true"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        // Update the tooltip
        jQuery(this).tooltip('hide').attr('data-original-title', this.dataset.textCopied).tooltip('show');
    });

    element.addEventListener('mouseleave', function () {
        this.setAttribute('data-original-title', this.dataset.textCopy);
    });
});

// Slide menu
document.querySelectorAll('.slide-menu-toggle').forEach(function (element) {
    element.addEventListener('click', function () {
        document.querySelector('#slide-menu').classList.toggle('active');
    });
});

// Table filters
document.querySelector('#search-filters') && document.querySelector('#search-filters').addEventListener('click', function (e) {
    e.stopPropagation();
});

// Toggle password visibility
document.querySelectorAll('[data-password]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        let passwordInput = document.querySelector('#' + this.dataset.password);

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            this.querySelector('svg:nth-child(1)').classList.add('d-none');
            this.querySelector('svg:nth-child(2)').classList.remove('d-none');

            jQuery(this).tooltip('hide').attr('data-original-title', this.dataset.passwordHide).tooltip('show');
        } else {
            passwordInput.type = 'password';

            this.querySelector('svg:nth-child(1)').classList.remove('d-none');
            this.querySelector('svg:nth-child(2)').classList.add('d-none');

            jQuery(this).tooltip('hide').attr('data-original-title', this.dataset.passwordShow).tooltip('show');
        }
    });
});

// Bulk selection
if (document.querySelector('#bulk-open')) {
    // Handle the bulk open action
    document.querySelector('#bulk-open').addEventListener('click', function () {
        document.querySelector('#bulk-actions-container').classList.remove('d-none');
        document.querySelector('#bulk-open-container').classList.add('d-none');
        // Show the checkboxes column
        document.querySelectorAll('[data-bulk-checkbox-column]').forEach(function (element) {
            element.classList.remove('d-none');
            element.classList.add('d-flex');
        });
    });
    // Handle the bulk close command
    document.querySelector('#bulk-close').addEventListener('click', function (e) {
        document.querySelector('#bulk-actions-container').classList.add('d-none');
        document.querySelector('#bulk-open-container').classList.remove('d-none')
        // Hide the checkboxes column
        document.querySelectorAll('[data-bulk-checkbox-column]').forEach(function (element) {
            element.classList.add('d-none');
            element.classList.remove('d-flex');
        });
    });
    // Handle the bulk check all
    document.querySelector('#bulk-check-all') && document.querySelector('#bulk-check-all').addEventListener('click', function () {
        if (this.checked) {
            document.querySelectorAll('[data-bulk-checkbox]').forEach(function (element) {
                // If the checkbox is not disabled
                if (!element.disabled) {
                    // Set all the checkboxes to checked
                    element.checked = true;
                }
            });
        } else {
            document.querySelectorAll('[data-bulk-checkbox]').forEach(function (element) {
                // Set all the checkboxes to unchecked
                element.checked = false;
            });
        }
    });
    document.querySelectorAll('[data-bulk-checkbox]').forEach(function (element) {
        element.addEventListener('change', function (e) {
            document.querySelector('#bulk-check-all').indeterminate = false;
            let checked, unchecked = false;
            // Check if any of the current bulk checkbox is unchecked
            document.querySelectorAll('[data-bulk-checkbox]').forEach(function (element) {
                if (element.checked === false) {
                    unchecked = true;
                } else {
                    checked = true;
                }
            });
            // If all checkboxes are checked
            if (checked && !unchecked) {
                document.querySelector('#bulk-check-all').checked = true;
            } else {
                // If there are both checked and unchecked checkboxes
                if (checked && unchecked) {
                    document.querySelector('#bulk-check-all').indeterminate = true;
                }
                document.querySelector('#bulk-check-all').checked = false;
            }
        });
    });
    // Get all the checkbox values
    document.querySelector('#bulk-dropdown').addEventListener('click', function () {
        let arr = [];
        document.querySelectorAll('[data-bulk-checkbox]').forEach(function (element) {
            if (element.checked) {
                arr.push(element.value);
            }
        });
        // Set the form action URL
        document.querySelector('#bulk-delete').setAttribute('data-action', (document.querySelector('#bulk-delete').dataset.actionOriginal).replace('\/id\/', '/' + JSON.stringify(arr) + '/'));

        // Set the form text description
        document.querySelector('#bulk-delete').setAttribute('data-text', (document.querySelector('#bulk-delete').dataset.textOriginal).replace('0', arr.length));

        // Set the submit button value
        document.querySelector('#bulk-delete').setAttribute('data-button-value', arr.length);
    });
}

/**
 * Handle the confirmation modal event.
 *
 * @param element
 */
let confirmationModalEvent = (element) => {
    element.addEventListener('click', function () {
        // Unset attributes if previously set
        document.querySelector('#modal-button').removeAttribute('name');
        document.querySelector('#modal-button').removeAttribute('value');

        // Set the attributes
        if (this.dataset.buttonName) {
            document.querySelector('#modal-button').setAttribute('name', this.dataset.buttonName);
        }
        if (this.dataset.buttonValue) {
            document.querySelector('#modal-button').setAttribute('value', this.dataset.buttonValue);
        }
        document.querySelector('#modal-label').textContent = this.dataset.title
        document.querySelector('#modal-button > span:nth-child(2)').textContent = this.dataset.title;
        document.querySelector('#modal-button').setAttribute('class', this.dataset.buttonClass);
        document.querySelector('#modal-text').textContent = this.dataset.text;
        document.querySelector('#modal-sub-text').textContent = this.dataset.subText;
        document.querySelector('#modal form').setAttribute('action', this.dataset.action);
    });
}

document.querySelectorAll('[data-target="#modal"]').forEach(function (element) {
    confirmationModalEvent(element);
});

// Button loader
document.querySelectorAll('[data-button-loader]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        // Stop the button from being re-submitted while loading
        if (this.classList.contains('disabled')) {
            e.preventDefault();
        }
        this.classList.add('disabled');
        this.querySelector('span:nth-child(1) > span').classList.remove('d-none');
        this.querySelector('span:nth-child(2)').classList.add('invisible');
    });
});

// Privacy selector
document.querySelectorAll('input[name="privacy"]').forEach(function (element) {
    element.addEventListener('click', function () {
        if (this.checked && this.value == 2) {
            document.querySelector('#input-password').classList.remove('d-none');
            document.querySelector('#input-password').classList.add('d-block')
        } else {
            document.querySelector('#input-password').classList.add('d-none');
            document.querySelector('#input-password').classList.remove('d-block')
        }
    });
});

// Share modal
document.querySelectorAll('.link-share').forEach(function (element) {
    element.addEventListener('click', function () {
        let url = this.dataset.url;
        let title = this.dataset.title;
        let qr = this.dataset.qr;

        document.querySelectorAll('#share-x, #share-facebook, #share-reddit, #share-pinterest, #share-linkedin, #share-tumblr, #share-email, #share-qr').forEach(function (element) {
            element.setAttribute('data-url', url);
            element.setAttribute('data-title', title);
            element.setAttribute('data-qr', qr);
        });
    });
});

document.querySelector('#share-x') && document.querySelector('#share-x').addEventListener('click', function (e) {
    e.preventDefault();

    popupCenter("https://x.com/intent/tweet?text="+encodeURIComponent(this.dataset.title)+"&url="+encodeURIComponent(this.dataset.url), encodeURIComponent(this.dataset.title), 550, 250);
});

document.querySelector('#share-facebook') && document.querySelector('#share-facebook').addEventListener('click', function (e) {
    e.preventDefault();

    popupCenter("https://www.facebook.com/sharer/sharer.php?u="+encodeURIComponent(this.dataset.url), encodeURIComponent(this.dataset.title), 550, 300);
});

document.querySelector('#share-reddit') && document.querySelector('#share-reddit').addEventListener('click', function (e) {
    e.preventDefault();

    popupCenter("https://www.reddit.com/submit?url="+encodeURIComponent(this.dataset.url), encodeURIComponent(this.dataset.title), 550, 530);
});

document.querySelector('#share-pinterest') && document.querySelector('#share-pinterest').addEventListener('click', function (e) {
    e.preventDefault();

    popupCenter("https://pinterest.com/pin/create/button/?url="+encodeURIComponent(this.dataset.url)+"&description="+encodeURIComponent(this.dataset.title), encodeURIComponent(this.dataset.title), 550, 300);
});

document.querySelector('#share-linkedin') && document.querySelector('#share-linkedin').addEventListener('click', function (e) {
    e.preventDefault();

    popupCenter("https://www.linkedin.com/sharing/share-offsite/?url="+encodeURIComponent(this.dataset.url), encodeURIComponent(this.dataset.title), 550, 300);
});

document.querySelector('#share-tumblr') && document.querySelector('#share-tumblr').addEventListener('click', function (e) {
    e.preventDefault();

    popupCenter("https://www.tumblr.com/widgets/share/tool/preview?posttype=link&canonicalUrl="+encodeURIComponent(this.dataset.url)+"&title="+encodeURIComponent(this.dataset.title), encodeURIComponent(this.dataset.title), 550, 300);
});

document.querySelector('#share-email') && document.querySelector('#share-email').addEventListener('click', function (e) {
    e.preventDefault();

    window.open("mailto:?Subject="+encodeURIComponent(this.dataset.title)+"&body="+encodeURIComponent(this.dataset.title)+" - "+encodeURIComponent(this.dataset.url), "_self");
});

document.querySelector('#share-qr') && document.querySelector('#share-qr').addEventListener('click', function (e) {
    e.preventDefault();

    popupCenter(this.dataset.qr, encodeURIComponent(this.dataset.title), 300, 300);
});

// Handle the Single URL form
document.querySelector('#single-link') && document.querySelector('#single-link').addEventListener("click", function () {
    document.querySelectorAll('.single-link').forEach(element => element.classList.add('d-block'));
    document.querySelectorAll('.multiple-links').forEach(element => element.classList.remove('d-block'));
    document.querySelector('#i-alias').removeAttribute('disabled');
    window.setTimeout(function () {
        document.querySelector('#i-url').focus();
    }, 0);
});

// Handle the Multiple URL form
document.querySelector('#multiple-links') && document.querySelector('#multiple-links').addEventListener("click", function () {
    document.querySelectorAll('.multiple-links').forEach(element => element.classList.add('d-block'));
    document.querySelectorAll('.single-link').forEach(element => element.classList.remove('d-block'));
    document.querySelector('#i-alias').setAttribute('disabled', 'disabled');
    window.setTimeout(function () {
        document.querySelector('#i-urls').focus();
    }, 0);
});

// Home copy button
document.querySelector('.home-copy') && document.querySelector('.home-copy').addEventListener('click', function () {
    this.querySelectorAll('span').forEach(function (element) {
        element.classList.toggle('d-none');
    });
    this.classList.add('btn-success');
    this.classList.remove('btn-primary');

    document.querySelector('#copy-form-container input').removeAttribute('style');

    setTimeout(function () {
        jQuery('#copy-form-container').fadeOut('done', function () {
            jQuery('#short-form-container').fadeIn();

            // Focus the shorten input
            document.querySelector('input[name="url"]').focus();
        });
    }, 500);

});

// Set dynamic height to the URLs text area
document.querySelector('#i-urls') && document.querySelector('#i-urls').addEventListener("input", (function () {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
    this.style.overflowY = 'hidden';
}), false);

// Info tooltip
jQuery('[data-toggle="tooltip-url"]').tooltip({animation: true, delay: {"show": 500, "hide": 100}});

// Handle the target type dropdown
if (document.querySelector('#form-link')) {
    document.querySelector('#i-targets-type').addEventListener('change', function () {
        document.querySelectorAll('[data-targets-container]').forEach(function (element) {
            // Clear any previous inputs
            element.querySelectorAll('[data-targets-inputs]').forEach(function (targetInputs) {
                targetInputs.innerHTML = '';
            });

            // Hide all section containers
            element.classList.add('d-none');

            // Show selected section container
            document.querySelector('[data-targets-container="' + (document.querySelector('#i-targets-type').value) + '"]').classList.remove('d-none');
        });
    });
}

// Handle dynamic field additions and deletions
document.querySelectorAll('[data-targets-container]').forEach(element => {
    element.addEventListener('click', function (e) {
        let targetsContainerName = this.dataset.targetsContainer;

        let valueName = 'targets';

        if (e.target.closest('.input-delete')) {
            // Delete the inputs parent container
            e.target.closest('.input-delete').parentNode.parentNode.remove();

            // If there are no inputs left, enable the dummy inputs
            if (element.querySelectorAll('input[data-input="value"]').length == 1) {
                if (element.querySelector('input[name="targets[empty][key]"]')) {
                    element.querySelector('input[name="targets[empty][key]"]').removeAttribute('disabled');
                }
                element.querySelector('input[name="targets[empty][value]"]').removeAttribute('disabled');
            }
        }

        if (e.target.closest('.input-add')) {
            // Clone the input template
            let input = document.querySelector('[data-targets-container="' + targetsContainerName + '"] [data-targets-input-template]').cloneNode(true);

            // Clean up the input
            input.classList.remove('d-none');
            input.removeAttribute('data-targets-input-template');

            let inputId = new Date().getTime();

            // Enable the inputs
            input.querySelectorAll('input, select').forEach(function (inputElement) {
                inputElement.removeAttribute('disabled');

                inputElement.setAttribute('name', valueName + '['+ inputId +'][' + inputElement.dataset.input + ']');
            });

            // Disable the dummy inputs
            element.querySelector('input[name="targets[empty][key]"]') && element.querySelector('input[name="targets[empty][key]"]').setAttribute('disabled', 'disabled');
            element.querySelector('input[name="targets[empty][value]"]') && element.querySelector('input[name="targets[empty][value]"]').setAttribute('disabled', 'disabled');

            // Append the inputs to the DOM
            document.querySelector('[data-targets-container="' + targetsContainerName + '"] [data-targets-inputs]').append(input);
        }
    });
});

// UTM Builder
document.querySelector('#utm-builder') && document.querySelector('#utm-builder').addEventListener('click', function () {
    let urlInput = document.querySelector('#i-url');

    let sources = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    try {
        let url = new URL(urlInput.value);

        sources.forEach(function (source) {
            // If the URL already has a source set
            if (url.searchParams.has(source)) {
                // Update the input with the current source value
                document.querySelector('input[name="' + source + '"]').value = url.searchParams.get(source);
            }
        });
    } catch(e) {
        sources.forEach(function (source) {
            // Update the input with the current source value
            document.querySelector('input[name="' + source + '"]').value = '';
        });
    }
});

document.querySelectorAll('#i-utm-source, #i-utm-medium, #i-utm-campaign, #i-utm-term, #i-utm-content').forEach(element => {
    element.addEventListener('input', function () {
        let urlInput = document.querySelector('#i-url');

        try {
            let url = new URL(urlInput.value);

            let targetName = element.getAttribute('name');

            let inputValue = document.querySelector('input[name="' + targetName + '"]').value;

            if (inputValue === "") {
                url.searchParams.delete(targetName);
            } else {
                url.searchParams.set(targetName, inputValue);
            }

            urlInput.value = url.href;
        } catch (e) {

        }
    });
});

// Initialize toasts
jQuery('.toast').toast();

/**
 * Get the value of a given cookie.
 *
 * @param   name
 * @returns {*}
 */
let getCookie = (name) => {
    var name = name + '=';
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');

    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while(c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if(c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return '';
};

/**
 * Set a cookie.
 *
 * @param   name
 * @param   value
 * @param   expire
 * @param   path
 */
let setCookie = (name, value, expire, path) => {
    document.cookie = name + "=" + value + ";expires=" + (new Date(expire).toUTCString()) + ";path=" + path;
};

/**
 * Center the pop-up window
 *
 * @param url
 * @param title
 * @param w
 * @param h
 */
let popupCenter = (url, title, w, h) => {
    // Fixes dual-screen position                         Most browsers      Firefox
    let dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
    let dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;

    let width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    let height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

    let systemZoom = width / window.screen.availWidth;
    let left = (width - w) / 2 / systemZoom + dualScreenLeft;
    let top = (height - h) / 2 / systemZoom + dualScreenTop;
    let newWindow = window.open(url, title, 'scrollbars=yes, width=' + w / systemZoom + ', height=' + h / systemZoom + ', top=' + top + ', left=' + left);

    // Puts focus on the newWindow
    if (window.focus) newWindow.focus();
};

/**
 * Chart
 *
 * @param n
 * @param x
 * @param s
 * @param c
 * @returns {string}
 */
Number.prototype.format = function (n, x, s, c) {
    let re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
        num = this.toFixed(Math.max(0, ~~n));

    return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
};

/**
 * Commarize large numbers
 *
 * @param number
 * @param min
 * @returns {string}
 */
let commarize = (number, min) => {
    min = min || 1e3;
    // Alter numbers larger than 1k
    if (number >= min) {
        let units = ["K", "M", "B", "T"];
        let order = Math.floor(Math.log(number) / Math.log(1000));
        let unitname = units[order - 1];
        let num = Number((number / 1000 ** order).toFixed(2));
        // output number remainder + unitname
        return num + unitname;
    }
    // return formatted original number
    return number.toLocaleString();
}