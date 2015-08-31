# Commerce Mailer

Makes it easier to implement forms to sent Craft Commerce products, carts and orders by email, using full Craft templates for the email.

Supports Ajax with JSON responses.

The following variables are used in the form.

Name | Required | Notes
---- | -------- | -----
`toEmail` | Yes | Pass `default` to use the email from the plugin settings
`toName` | No | Pass `default` to use the name from the plugin settings
`fromEmail` | Yes | 
`fromName` | No | 
`subject` | Yes | 
`body`	  | Yes |
`productId` | No | ID of a product (not variant)
`orderId` | No | ID of an order
`action` | Yes | Must be `commerceMailer/sendmail`
`template` | Yes | The name of your email template(appended to the email folder from settings)
`redirect` | No | URL to redirect to after email has been sent (ignored when called by Ajax)

Use the following form as an example - this is for a product enquiry to be sent to the default address from the plugin settings.

```
<form id="commerceMailerForm" method="POST" >
    {{ getCsrfInput() }}
    <input type="hidden" name="action"              value="commerceMailer/sendmail">
    <input type="hidden" name="template"            value="product-enquiry">
    <input type="hidden" name="redirect"            value="/utility/email-sent">
    <input type="hidden" name="productId"           value="{{ product.id }}">
    <input type="hidden" name="subject"             value="Product Enquiry: {{ product.title }}" >
    <input type="hidden" name="toEmail"             value="default" >
    <input type="hidden" name="toName"              value="default" >

    Your Name:
    <input type="text" placeholder="Your Name" name="fromName">
    Your Email:
    <input type="text" placeholder="Your Email" name="fromEmail">
	Your Message:
	<textarea placeholder="Enter your question here." name="body"></textarea>
	<input type="submit" id="commerceMailerSubmitButton" value="Send to Image Science" class="btn">

</form>
```

Alternatively, submit via Ajax & get JSON responses.  

```
$("#commerceMailerForm").submit(function(e) {

    e.preventDefault();
    var data = $(this).serialize();
    data[window.csrfTokenName] = window.csrfTokenValue;

    $.post('/actions/commerceMailer/sendmail', data, function(response) {

        if (response.success) {
            $("#commerceMailerSubmitButton").val("Sent!");
        } 
        else {
           $("#commerceMailerSubmitButton").val("Error!");
        }
	});
        
});
```

## Email Templates

All post data is passed to your templates in twig variables with the same names as above.
If you have provided the `id` of a `product` or `order`, those will be available in twig variables with those names as well.  
If there is an active cart, it will always be available in `cart`.

## Thanks

Thanks go out to [@lukeholder](https://github.com/lukeholder) and [@crawf](https://github.com/engram-design)