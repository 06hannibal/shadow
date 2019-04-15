(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.SubBlockBehavior = {
        attach: function (context, settings) {
            paypal.Buttons({
                style: {
                    color:  'silver',
                    shape:  'pill',
                    label:  'pay',
                    height: 40,
                },
                // Set up the transaction
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: 10.00,
                            }
                        }]
                    });
                },
                // Finalize the transaction
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        // Show a success message to the buyer
                        alert('Your subscription ID # ' + details.id +' have status ' + details.status + ' !');
                        var playlogs = details.status;

                        if (details.status === "COMPLETED"){
                            var button = document.getElementById('href_for_subs').style.display = "flex";
                            var tran_status = $.ajax({
                                type: "POST",
                                url: 'https://shadow.loc/forsubs/COMPLETED',
                                data: JSON.stringify(playlogs),
                                success: function (data) {
                                    alert("Transacrion status: " + playlogs);
                                }
                            });
                            window.location.href = "https://shadow.loc/forsubs/COMPLETED";
                        }
                    });

                }
            }).render('#paypal-button-container-subscribe');

        }
    };
})(jQuery, Drupal, drupalSettings);