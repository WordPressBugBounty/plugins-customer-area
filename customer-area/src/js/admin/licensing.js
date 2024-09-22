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
        product_id: productId
    };

    $.post(cuar.ajaxUrl, data, function (response)
            {
                apiKeyInput.prop('disabled', false);
                productIdInput.prop('disabled', false);
                validateButton.prop('disabled', false);
                checkResultContainer
                        .removeClass()
                        .addClass(response.success ? 'cuar-ajax-success' : 'cuar-ajax-failure')
                        .html(response.success ? response.message : response.error);
            },
            "json",
            function ()
            {
                apiKeyInput.prop('disabled', false);
                productIdInput.prop('disabled', false);
                validateButton.prop('disabled', false);
                checkResultContainer
                        .removeClass()
                        .addClass('cuar-ajax-failure')
                        .html(cuar.unreachableLicenseServerError);
            }
    );
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
