/**
 * Get the input field of a license control
 * @param licenseControl
 * @returns {*}
 */
function getLicenseControlApiKeyInput(licenseControl)
{
    return licenseControl.find('.cuar-js-api-key');
}

/**
 * Get the input field of a license control
 * @param licenseControl
 * @returns {*}
 */
function getLicenseControlProductIdInput(licenseControl)
{
    return licenseControl.find('.cuar-js-product-id');
}

/**
 * Get the input field of a license control
 * @param licenseControl
 * @returns {*}
 */
function getLicenseControlActivateButton(licenseControl)
{
    return licenseControl.find('.cuar-js-activate-button');
}

/**
 * Get the result container of a license control
 * @param licenseControl
 * @returns {*}
 */
function getLicenseControlResultContainer(licenseControl)
{
    return licenseControl.find('.cuar-js-result > span');
}

/**
 * Get the add-on referred to by this control
 * @param licenseControl
 * @returns {*}
 */
function getLicenseControlAddOn(licenseControl)
{
    return getLicenseControlApiKeyInput(licenseControl).data('addon');
}

/**
 * Validate a license
 * @param licenseControl The control to enter the license key
 */
function activateLicense($, licenseControl)
{
    var apiKeyInput = getLicenseControlApiKeyInput(licenseControl);
    var productIdInput = getLicenseControlProductIdInput(licenseControl);
    var validateButton = getLicenseControlActivateButton(licenseControl);
    var checkResultContainer = getLicenseControlResultContainer(licenseControl);

    var licenseKey = apiKeyInput.val().trim();
    if (licenseKey.length === 0) {
        checkResultContainer.html('');
        return;
    }

    var productId = productIdInput.val();
    if (productId) {
        productId = productId.trim();
    }

    checkResultContainer.html(cuar.checkingLicense).removeClass().addClass('cuar-ajax-running');
    apiKeyInput.prop('disabled', true);
    productIdInput.prop('disabled', true);
    validateButton.prop('disabled', true);

    var data = {
        action    : 'cuar_validate_license',
        addon_id  : getLicenseControlAddOn(licenseControl),
        api_key   : licenseKey,
        product_id: productId,
        security  : cuar.licenseNonce
    };

    $.post(cuar.ajaxUrl, data, "json")
        .done(function (response)
        {
            console.log('[WPCA License] AJAX Response:', response);
            
            apiKeyInput.prop('disabled', false);
            productIdInput.prop('disabled', false);
            validateButton.prop('disabled', false);
            
            var isSuccess = false;
            var message = '';
            
            if (response.success) {
                if (response.data && response.data.success === true && response.data.activated === true) {
                    isSuccess = true;
                    message = response.data.message || cuar.licenseActivated || 'License activated successfully!';
                } else if (response.data && response.data.success === false) {
                    isSuccess = false;
                    message = response.data.error || response.data.message || 'License activation failed';
                } else if (response.message) {
                    isSuccess = true;
                    message = response.message;
                } else {
                    isSuccess = true;
                    message = 'License validated successfully';
                }
            } else {
                isSuccess = false;
                message = response.data?.message || response.error || 'License validation failed';
            }
            
            checkResultContainer
                .removeClass()
                .addClass(isSuccess ? 'cuar-ajax-success' : 'cuar-ajax-failure')
                .html(message);
            
            setTimeout(function() {
                console.log('[WPCA License] Reloading page to refresh license status...');
                location.reload();
            }, 1500);
        })
        .fail(function (xhr, status, error)
        {
            console.error('[WPCA License] AJAX Network Error:', status, error, xhr);
            
            apiKeyInput.prop('disabled', false);
            productIdInput.prop('disabled', false);
            validateButton.prop('disabled', false);
            
            checkResultContainer
                .removeClass()
                .addClass('cuar-ajax-failure')
                .html(cuar.unreachableLicenseServerError || 'Unable to reach license server');
            
            setTimeout(function() {
                console.log('[WPCA License] Reloading page after network error...');
                location.reload();
            }, 2000);
        });
}

// Runs the necessary logic on the license controls of the page
jQuery(document).ready(function ($)
{
    // Used in the licensing options page to check license key when the input value changes
    $(".cuar-js-license-field").each(function ()
    {
        var licenseControl = $(this);

        // Check license when input value changes
        licenseControl.on("click", ".cuar-js-activate-button", function (event)
        {
            event.preventDefault();
            activateLicense($, licenseControl);
            return false;
        });
    });
});