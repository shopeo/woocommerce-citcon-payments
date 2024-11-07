const citcon_settings = window.wc.wcSettings.getSetting('citcon_data', {});
const channels = JSON.parse(citcon_settings.channels);
const citcon_label = window.wp.htmlEntities.decodeEntities(citcon_settings.title) || window.wp.i18n.__('Debit / Credit Card Payment', 'woocommerce-citcon-payments');
let payment_channel = citcon_settings.default_channel;
const {createElement, useEffect, useState} = React;
const payment_method = () => {
    return channels.map((channel) => {
        return Object(window.wp.element.createElement)('li', {
                className: 'wc_payment_method',
                style: {
                    marginBottom: '10px',
                }
            }, [
                Object(window.wp.element.createElement)('div', {
                    style: {
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px'
                    }
                }, [
                    Object(window.wp.element.createElement)('input', {
                        id: 'citcon_pay_method_' + channel.method,
                        className: 'input-radio',
                        type: 'radio',
                        name: 'payment_channel',
                        required: true,
                        value: channel.method,
                        defaultChecked: channel.method === citcon_settings.default_channel,
                        onChange: (e) => {
                            payment_channel = e.target.value;
                        }
                    }),
                    Object(window.wp.element.createElement)('label', {
                        for: 'citcon_pay_method_' + channel.method,
                        style: {
                            display: 'flex',
                            alignItems: 'center',
                            margin: '0',
                        }
                    }, [
                        Object(window.wp.element.createElement)('img', {
                            src: channel.icon,
                            alt: channel.title,
                            style: {
                                height: channel.icon_height + 'px',
                            }
                        })
                    ]),
                ])
            ]
        );
    });
}
const Content = (props) => {
    const {eventRegistration, emitResponse} = props;
    const {onPaymentProcessing} = eventRegistration;
    useEffect(() => {
        const unsubscribe = onPaymentProcessing(async () => {
            // Here we can do any processing we need, and then emit a response.
            // For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
            const customDataIsValid = !!payment_channel.length;

            if (customDataIsValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            payment_channel,
                        },
                    },
                };
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message: 'There was an error',
            };
        });
        // Unsubscribes when this component is unmounted.
        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentProcessing,
    ]);
    return Object(window.wp.element.createElement)('ul', {
        style: {
            listStyle: 'none',
            padding: '0',
        }
    }, payment_method());
};

const Citcon_Pay_Block_Gateway = {
    name: 'citcon',
    label: citcon_label,
    content: Object(window.wp.element.createElement)(Content),
    edit: Object(window.wp.element.createElement)(Content),
    canMakePayment: () => true,
    ariaLabel: citcon_label,
    supports: {
        features: citcon_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Citcon_Pay_Block_Gateway);