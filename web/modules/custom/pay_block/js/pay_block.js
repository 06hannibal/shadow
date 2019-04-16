(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.PayBlockBehavior = {
        attach: function (context, settings) {
            var title = document.getElementById('prod_title').innerHTML;
            var pricex = document.getElementById('prod_price').innerHTML;
            var entity_ids = document.getElementById('entity_id').innerHTML;
            console.log("Entity ID " + entity_ids);
            var price = pricex.substr(1,3);
            var paypl = paypal.Buttons({
                style: {
                    color:  'blue',
                    shape:  'pill',
                    label:  'buynow',
                    height: 40,
                    branding: true

                },
                // Set up the transaction
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: price,
                            }
                        }]
                    });
                },
                // Finalize the transaction
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        // Show a success message to the buyer
                        alert('Your transaction ID # ' + details.id +' have status ' + details.status + ' and your '+title+' will be delivered to ' + details.payer.address.country_code + ' !');
                        var playlogs = details.status;
                        // console.log(details);
                        // console.log(details.payer.name.given_name);

                        if (details.status === "COMPLETED"){
                            var current_url = document.URL;
                            var current_status = details.status;
                            var current_url_substr = current_url.substr(27,30);

                            // console.log("Entity IDsss " + entity_ids);

                            var array_vars = [current_status, current_url_substr, entity_ids];

                            var tran_status = $.ajax({
                                type: "POST",
                                url: 'http://shadow.itguild.com.ua/products/COMPLETED',
                                data: JSON.stringify(playlogs),
                                success: function (data) {
                                    alert("Transacrion status: " + playlogs);
                                }
                            });
                            window.location.href = "http://shadow.itguild.com.ua/products/"+array_vars;
                        }
                    });

                }
            }).render('#paypal-button-container');
        }
    };
})(jQuery, Drupal, drupalSettings);