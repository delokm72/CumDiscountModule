define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals'
], function (Component, quote, totals) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Prostor_CumDiscount/checkout/cart/totals/cumulative-discount',
            title: 'Prostor Cumulative'
        },

        totals: quote.getTotals(),

        isDisplayed: function () {
            return this.getPureValue() !== 0;
        },

        getPureValue: function () {
            var totalsData = totals.totals();
            var segments;
            var index;
            var segment;

            if (!totalsData || !totalsData.total_segments) {
                return 0;
            }

            segments = totalsData.total_segments;
            for (index = 0; index < segments.length; index++) {
                segment = segments[index];
                if (segment.code === 'cumulative_discount') {
                    return parseFloat(segment.value || 0);
                }
            }

            return 0;
        },

        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        }
    });
});

