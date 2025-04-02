(function ( ) {

jQuery(function ($) {
    $(document).ajaxError(function () {
        if (typeof window._depayUnmountLoading == "function") {
            window._depayUnmountLoading();
        }
    });

    $(document).ajaxComplete(function () {
        if (typeof window._depayUnmountLoading == "function") {
            window._depayUnmountLoading();
        }
    });

    $("form.pmpro_form").on("submit", async () => {
        var values = $("form.pmpro_form").serialize();
        if (values.match("pmpro_checkout_gateway=pmp_unuspay_gateway")) {
            let { unmount } = await UnusPayWidgets.Loading({
                text: "Loading payment data...",
            });
            setTimeout(unmount, 10000);
        }
    });
});


const displayCheckout = async () => {
    if (window.location.hash.startsWith("#pmp-unuspay-checkout-")) {
        const checkoutId = window.location.hash.match(
            /pmp-unuspay-checkout-(.*?)@/
        )[1];
        const response = JSON.parse(
            await wp.apiRequest({
                path: `/unuspay/pmp/checkouts/${checkoutId}`,
                method: "POST",
            })
        );
        if (response.redirect) {
            window.location = response.redirect;
            return;
        }
        const paymentInfo = [];
        response.tokens.forEach((token) => {
            paymentInfo.push({
                blockchain: token.blockchain,
                amount: token.amount,
                token: token.tokenAddress,
                receiver: token.receiveAddress,
                fee: {
                    amount: token.feeRate + "%",
                    receiver: token.feeAddress,
                },
            });
        });
        let configuration = {
            accept: paymentInfo,
            closed: () => {
                window.location.hash = "";
                window.location.reload(true);
            },
            track: {
                id: checkoutId,
                endpoint: "/wp-json/unuspay/pmp/track",
                poll: {
                    endpoint: "/wp-json/unuspay/pmp/release"
                }

            }
        };

        UnusPayWidgets.Payment(configuration);
    }
};

document.addEventListener('DOMContentLoaded', displayCheckout);
window.addEventListener('hashchange', displayCheckout);

})()
