const citcon_settings = window.wc.wcSettings.getSetting('citcon_data', {});
const citcon_label = window.wp.htmlEntities.decodeEntities(citcon_settings.title) || window.wp.i18n.__('Debit / Credit Card Payment', 'woocommerce-citcon-payments');
const citcon_content = () => {
    return window.wp.htmlEntities.decodeEntities(citcon_settings.description || window.wp.i18n.__('Please note the payment times will be take a bit time please don’t refresh the page Your order will not be shipped until the payment been successfully, if the payment fails, please use bank transfer to pay, or contact us directly through online consultation, any issues and questions please contact us.', 'woocommerce-citcon-payments'));
};
const Citcon_Pay_Block_Gateway = {
    name: 'citcon',
    label: citcon_label,
    content: Object(window.wp.element.createElement)(citcon_content, null),
    edit: Object(window.wp.element.createElement)(citcon_content, null),
    canMakePayment: () => true,
    ariaLabel: citcon_label,
    supports: {
        features: citcon_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Citcon_Pay_Block_Gateway);