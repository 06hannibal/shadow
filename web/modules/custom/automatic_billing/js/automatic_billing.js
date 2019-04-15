(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.SubBlockBehavior = {
        attach: function (context, settings) {
            var selected = $('#price').on('change',function(){

                var prod_name = document.getElementById('prod_name').innerHTML;
                var prod_price = document.getElementById("price").value;


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
                                    value: prod_price,
                                }
                            }]
                        });
                    },
                    // Finalize the transaction
                    onApprove: function(data, actions) {
                        return actions.order.capture().then(function(details) {
                            // Show a success message to the buyer
                            alert('Your billing ID # ' + details.id +' have status ' + details.status + ' !');
                            var trasaction_status = details.status;


                            if (details.status === "COMPLETED"){
                                var vars = [prod_price, trasaction_status, prod_name];

                                var tran_status = $.ajax({
                                    type: "POST",
                                    url: 'https://shadow.loc/autobill/COMPLETED',
                                    data: JSON.stringify(trasaction_status),
                                    success: function (data) {
                                        alert("Transacrion status: " + trasaction_status);
                                    }
                                });
                                window.location.href = "https://shadow.loc/autobill/"+vars ;
                            }
                        });

                    }
                }).render('#paypal-button-container-cleaning');
            });
        }
    };
})(jQuery, Drupal, drupalSettings);