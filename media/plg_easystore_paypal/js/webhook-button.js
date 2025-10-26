/**
 * @copyright   Copyright (C) 2024 JoomShaper. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 * @since       2.0.0
 */

Joomla = window.Joomla || {};
(function (Joomla, document) {
    document.addEventListener('DOMContentLoaded', function () {

        let paymentEnvironment = document.querySelector('input[name="jform[params][shop_environment]"]:checked')?.value;

        document.addEventListener('change', function (event) {

            console.log('change')
            if (document.getElementById('jform_params_shop_environment').contains(event.target)) {
                paymentEnvironment = event.target.value;
            }
        })

        document.addEventListener('click', function (event) {
            if (event.target.id === 'easystore-create-webhook-for-paypal') {
                event.preventDefault();

                const url = `${Joomla.getOptions(
                    'easystore.base',
                )}/administrator/index.php?option=com_ajax&plugin=paypal&group=easystore&format=json`;

                event.target.innerText = Joomla.Text._('PLG_EASYSTORE_PAYPAL_WEBHOOK_BUTTON_CREATE');

                let formData = new FormData();
                formData.append('payment_environment', paymentEnvironment);

                Joomla.request({
                    url,
                    method: 'POST',
                    onSuccess: response => {
                    
                        response = JSON.parse(response);

                        const webhookID = paymentEnvironment == 'sandbox' ? 'test' : 'live';

                        if (response.data !== undefined && response.data !== null) {
                            if (response.data.webhook_id) {
                                console.log(response.data.webhook_id)
                                document.querySelector('#jform_params_'+ webhookID +'_webhook_id').value = response.data.webhook_id;
                                event.target.innerText = Joomla.Text._('PLG_EASYSTORE_PAYPAL_WEBHOOK_ENDPOINT_CREATED');

                                Joomla.renderMessages({
                                    message: [Joomla.Text._(response.data.message)],
                                });

                                // Trigger the save button
                                Joomla.submitbutton('plugin.apply');
                            } else {
                                Joomla.renderMessages({
                                    error: [Joomla.Text._(response.data.message)],
                                });
                                event.target.innerText = Joomla.Text._('PLG_EASYSTORE_PAYPAL_WEBHOOK_BUTTON_DESC');
                            }
                        }

                        if (!response.success && response.message !== null) {
                            Joomla.renderMessages({
                                error: [Joomla.Text._(response.message)],
                            });
                            event.target.innerText = Joomla.Text._('PLG_EASYSTORE_PAYPAL_WEBHOOK_BUTTON_DESC');
                        }
                    },
                    onError: xhr => {
                        Joomla.renderMessages(Joomla.ajaxErrorsMessages(xhr));
                    },
                });
            }
        });
    });
})(Joomla, document);
