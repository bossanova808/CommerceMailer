# Commerce Mailer

Makes it easier to implement forms to sent Craft Commerce products, carts and orders by email (usually to the store owner but optionally to others as well), using twig templates for the email.

Supports Ajax with JSON responses, and has simple spam trapping.  

### Notes on the spam trapping

* First, I suggest only using forms with a `toEmail` parameter behind a login as there is obviously a lot of potential for abuse there.  
* For forms not behind a login, use the `internalName` paramter which is matched against the whitelist in settings (and you supply just the name part of the email, the internal domain comes from settings).
* Also, spam trapping is done via the Honeypot technique (copies [Pixel & Tonic's Contact Form][contactform] for this) if you set a honeypot field name in settings.

## Installation

To install CommerceMailer, follow these steps:

* Download the latest release from the releases tab
* Upload the commercemailer/ folder to your craft/plugins/ folder.
* Go to Settings > Plugins from your Craft control panel and enable the CommerceMailer plugin.
* Click on “Mailer for Commerce” to go to the plugin’s settings page, and configure the plugin.

## Your Email Forms

The following variables are used in the form.

Name | Required | Notes
---- | -------- | -----
`action` | Yes | Must be `commerceMailer/sendmail`
`toName` | No | 
`toEmail` | No | Should probably only be used on forms behind a login to avoid spam. (Either toEmail or internalName must be supplied).
`internalName` | No | If supplied, will be added to the internal domain setting value, and the name must be found in the whitelist in the plugin settings
`fromEmail` | Yes | 
`fromName` | No | 
`subject` | Yes | 
`message`    | Yes | The body of your message, or an array of custom fields + a body, see below.
`productId` | No | ID of a product (not variant)
`orderId` | No | ID of an order
`template` | Yes | The name of your email template(appended to the email folder from settings)
`redirect` | No | URL to redirect to after email has been sent (ignored when called by Ajax)

Use the following form as an example - this is for a product enquiry to be sent to the default address from the plugin settings.

```
    <form id="commerceMailerForm" method="POST" >
        {{ getCsrfInput() }}
        <input type="hidden" name="action"              value="commerceMailer/sendmail">
        <input type="hidden" name="template"            value="contact-form">
        <input type="hidden" name="redirect"            value="/utility/email-sent">

        {# honeypot field defined in settings and with css to set display:none #}
        <input id="schatje" name="schatje" type="text">
        
        <p>Choose your enquiry type:</p>
        <input type="radio" checked="" value="enquiries" name="internalName"> <strong>General / Stock</strong> (goes to Meg, our office manager)
        <br>
        <input type="radio" value="services" name="internalName"> <strong>Services</strong> (goes to Elisa, our services manager)
        <br>
        <input type="radio" value="techsupport" name="internalName"> <strong>Technical / Advice</strong> (goes to Jeremy Daalder, our Director)
        <br>
        <input type="radio" value="website" name="internalName"> <strong>Website Issue</strong> (goes to Katie, our marketing &amp; website manager)
        <br>
        <br>
        <label class="combo is-required">
            <input type="text" placeholder="Your Name" name="fromName" class="combo__input">
            <span class="combo__label">Your Name</span>
        </label>
        <label class="combo is-required">
            <input type="text" placeholder="Your Email (please check carefully!)" name="fromEmail" class="combo__input">
            <br>
            <span class="combo__label">Your Email (please check carefully that you enter this correctly!)</span>
        </label>
        <label class="combo">
            <input type="text" placeholder="Your Phone Number" name="message[Phone Number]" class="combo__input">
            <br>
            <span class="combo__label">Your Phone Number (some answers are just too long for email!)</span>
        </label>
        <label class="combo is-required">
            <input type="text" placeholder="Enquiry Subject" name="subject" class="combo__input">
            <br>
            <span class="combo__label">What is this enquiry about?</span>
        </label>
        <br>
        <textarea placeholder="Enter your enquiry here." name="message[body]"></textarea>
        <br>
        <br>
        <input class="btn btn--blue" id="commerceMailerSubmitButton" type="submit" value="Send to Image Science">
    </form>

```

Alternatively, you can submit your form via Ajax & get JSON responses.  

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

### Adding additional fields

You can add additional fields to your form by splitting your “message” field into multiple fields, using an array syntax for the input names:

```
<h3><label for="message">Message</label></h3>
<textarea rows="10" cols="40" id="message" name="message[body]">{% if message is defined %}{{ message.message }}{% endif %}</textarea>

<h3><label for="phone">Your phone number</label></h3>
<input id="phone" type="text" name="message[Phone]" value="">

<h3>What services are you interested in?</h3>
<label><input type="checkbox" name="message[Services][]" value="Design"> Design</label>
<label><input type="checkbox" name="message[Services][]" value="Development"> Development</label>
<label><input type="checkbox" name="message[Services][]" value="Strategy"> Strategy</label>
<label><input type="checkbox" name="message[Services][]" value="Marketing"> Marketing</label>
```

If you have a primary “Message” field, you should name it ``message[body]``, like in that example.

An email sent with the above form might result in the following message:

    Phone: (555) 123-4567

    Services: Design, Development

    Hey guys, I really loved this simple contact form (I'm so tired of agencies
    asking for everything but my social security number up front), so I trust
    you guys know a thing or two about usability.

    I run a small coffee shop and we want to start attracting more freelancer-
    types to spend their days working from our shop (and sipping fine coffee!).
    A clean new website with lots of social media integration would probably
    help us out quite a bit there. Can you help us with that?

    Hope to hear from you soon.

    Cathy Chino

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