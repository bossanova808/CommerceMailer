# Commerce Mailer

Makes it easier to implement forms to sent Craft Commerce products, carts and orders by email, using full Craft templates for the email.

Supports Ajax with JSON responses, and has simple spam trapping via the Honeypot technique (copies P & T Contact Form code for this).

## Installation

To install CommerceMailer, follow these steps:

* Upload the contactform/ folder to your craft/plugins/ folder.
* Go to Settings > Plugins from your Craft control panel and enable the CommerceMailer plugin.
* Click on “Mailer for Commerce” to go to the plugin’s settings page, and configure the plugin.

## Your Email Forms

The following variables are used in the form.

Name | Required | Notes
---- | -------- | -----
`toEmail` | Yes | Pass `default` to use the email from the plugin settings
`toName` | No | Pass `default` to use the name from the plugin settings
`fromEmail` | Yes | 
`fromName` | No | 
`subject` | Yes | 
`body`    | Yes |
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

    {# Honeypot field #}
    <input id="schatje" name="schatje" type="text" style="display:none;">

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

### The “Honeypot” field

(Code & description copied from [Pixel & Tonic's Contact Form][contactform] plugin.)

[contactform]: https://github.com/pixelandtonic/ContactForm "Pixel & Tonic Contact Form"

The [Honeypot Captcha][honeypot] is a simple anti-spam technique, which greatly reduces the efficacy of spambots without expecting your visitors to decipher various tortured letterforms.

[honeypot]: http://haacked.com/archive/2007/09/11/honeypot-captcha.aspx/ "The origins of the Honeypot Captcha"

In brief, it works like this:

1. You add a normal text field (our “honeypot”) to your form, and hide it using CSS.
2. Normal (human) visitors won't fill out this invisible text field, but those crazy spambots will.
3. The ContactForm plugin checks to see if the “honeypot” form field contains text. If it does, it assumes the form was submitted by “Evil People”, and ignores it (but pretends that everything is A-OK, so the evildoer is none the wiser).

### Example “Honeypot” implementation
When naming your form field, it's probably best to avoid monikers such as “dieEvilSpammers”, in favour of something a little more tempting. For example:

```html
<input id="preferredKitten" name="preferredKitten" type="text">
```

In this case, you could hide your form field using the following CSS:

```css
input#preferredKitten { display: none; }
```

## Email Templates

All post data is passed to your templates in twig variables with the same names as above.
If you have provided the `id` of a `product` or `order`, those will be available in twig variables with those names as well.  
If there is an active cart, it will always be available in `cart`.






## Thanks

Thanks go out to [@lukeholder](https://github.com/lukeholder) and [@crawf](https://github.com/engram-design)