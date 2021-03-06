<?php
namespace Craft;


class CommerceMailerController extends BaseController
{

    protected $allowAnonymous = array('actionEmailProductEnquiry', 'sendMail');

    /**
     * Checks that the 'honeypot' field has not been filled out (assuming one has been set).
     *
     * @param string $fieldName The honeypot field name.
     * @return bool
     */
    protected function validateHoneypot($fieldName)
    {
        if (!$fieldName)
        {
            return true;
        }
        $honey = craft()->request->getPost($fieldName);
        return $honey == '';
    }

    public function actionSendMail()
    {
        //Get plugin settings
        $settings = craft()->plugins->getPlugin('commerceMailer')->getSettings();
        
        //Called via Ajax?
        $ajax = craft()->request->isAjaxRequest();

        //Settings to control behavour when testing - we don't want to debug via ajax or it stuffs up the JSON response...
        $debugPOST = ($settings->debugPOST and !$ajax);
        $emailing = $settings->emailing;

        $savedBody = false;

        //Must be called by POST
        $this->requirePostRequest();
        //We'll return all the POST data to the template, so kick of our return data with that...
        $vars = craft()->request->getPost();
  
        //Dump POST to page if debugging
        if ($debugPOST){
            echo '<h3>POST</h3><pre>';
            print_r($vars);
            echo '</pre>';
        }

        //Is this spam? Assume false.  
        $spam = false;
        $spam = !$this->validateHoneypot($settings->honeypotField);

        //If it's an internal email, make sure it's in the whitelist of names
        $emailWhitelist = array_map('trim', explode(',', $settings->whitelistedNames));
        if ($debugPOST){
            echo '<h3>Whitelist</h3><pre>';
            print_r($emailWhitelist);
            echo '</pre>';
        }
        if (isset($vars['internalName'])){
            if($vars['internalName']!=""){
                $spam = $spam && in_array($vars['internalName'], $emailWhitelist);
                if (!$spam){
                    $vars['toEmail'] = $vars['internalName'] . "@" . $settings->internalDomain;
                }
            }
        }           


        //hold a list of possible errors to pass back to template on error
        $errors = array();

        //Deal with extra fields...the message input might be just a message, or have other fields
        //Pinched from P & T ContactForm
        $postedMessage = craft()->request->getPost('message');
        
        if ($postedMessage)
        {
            if (is_array($postedMessage))
            {
                $savedBody = false;
                if (isset($postedMessage['body']))
                {
                    // Save the message body in case we need to reassign it in the event there's a validation error
                    $savedBody = $postedMessage['body'];
                }
                // If it's false, then there was no messages[body] input submitted.  If it's '', then validation needs to fail.
                if ($savedBody === false || $savedBody !== '')
                {
                    // Compile the message from each of the individual values
                    $compiledMessage = '';
                    foreach ($postedMessage as $key => $value)
                    {
                        if ($key != 'body')
                        {
                            if ($compiledMessage)
                            {
                                $compiledMessage .= "<br><br>";
                            }
                            $compiledMessage .= $key.': ';
                            if (is_array($value))
                            {
                                $compiledMessage .= implode(', ', $value);
                            }
                            else
                            {
                                $compiledMessage .= $value;
                            }
                        }
                    }
                    if (!empty($postedMessage['body']))
                    {
                        if ($compiledMessage)
                        {
                            $compiledMessage .= "<br><br>";
                        }
                        $compiledMessage .= $postedMessage['body'];
                    }
                    $vars['body'] = "<br>" . nl2br($compiledMessage);
                }
            }
            else
            {
                $vars['body'] = "<br>" . nl2br($postedMessage);
            }
        }
 

        // create an EmailModel & populate it
        $email = EmailModel::populateModel($vars);

        //Attach a file if there is one...
        $attachment = null;
        if (isset($_FILES['attachment']) && !empty($_FILES['attachment']['name']))
        {
            CommerceMailerPlugin::log("Found attachment " . $_FILES['attachment']['name']);
            $attachment = \CUploadedFile::getInstanceByName('attachment');
            $email->addAttachment($attachment->getTempName(), $attachment->getName(), 'base64', $attachment->getType());
        }

        //validate the email model 
        //put all our errors in one place, and return the email model if invalid - use message as this is what contactForm does
        $valid = $email->validate();
        if (!$valid){

            if ($savedBody !== false)
            {
                $vars['message'] = $savedBody;
            }

            foreach ($email->getAllErrors() as $key => $error){
                $errors[] = $error;
            }
        }

        // Product, order and cart data 
        if (isset($vars['productId'])) {
            $vars['product'] = craft()->commerce_products->getProductById($vars['productId']);
        } 
        if (isset($vars['orderId'])) {
            $vars['order'] = craft()->commerce_orders->getOrderById($vars['orderId']);
        } 
        $vars['cart'] = craft()->commerce_cart->getCart();
       

        //Actual template to load is built by using the settings templateFolder + the form's POST var with name 'template'
        $templateFolder = $settings->templateFolder;
        if (!isset($templateFolder)) {
            $errors[] = 'No template folder in Commerce Mailer settings';
        } 

        if (!isset($vars['template'])) {
            $errors[] = 'No template in POST';
        } 

        //@TODO - what to do about the plain text body - will be unrendered...?
        if (isset($templateFolder) and isset($vars['template']) and !$errors){
            // parse the html template
            $htmlBody = craft()->templates->render($templateFolder . "/" . $vars['template'], $vars);
            if ($debugPOST) {
                print("<h4>Subject: " . $vars['subject'] ."</h4>");
                print($htmlBody);
            }
            $email->htmlBody = $htmlBody;      
        }               

        //OK, actually do something....unless we have an error....
        if ($errors) {
            $errors[] = 'Email not sent.';
            foreach ($errors as $error) {
                CommerceMailerPlugin::logError($error);
            }
            //Log what page the error happened on...
            CommerceMailerPlugin::logError(craft()->request->getUrlReferrer());
            craft()->urlManager->setRouteVariables(['errors' => $errors, 'message' => $email] );
        } 
        else {

            if($emailing){
                $sent = false;
                //attempt to send the email
                if (!$spam){ 

                    //Special sauce for us....
                    if (isset(craft()->config->get('environmentVariables')['IsImageScience'])){

                        //Are we sending to a local address?
                        if(strpos($email->toEmail, "@" . $settings->internalDomain) === false){
                            //No - we'll MAY NEED to later resort to phpmail send this sucker out now that transactional email
                            //services won't allow univerified sending domains :( ... However this is working with mailgun currently...
                            $sent = craft()->businessLogic_messaging->sendCraftEmail($email->fromEmail, $email->toEmail, $email->subject, $email->htmlBody);
                        }
                        else{
                            //Make a freshdesk ticket with the API
                            $sent = craft()->businessLogic_freshdesk->ticket($email->fromEmail, $email->toEmail, $email->subject, $email->htmlBody, (isset($vars['order']) ? $vars['order'] : null), false, $attachment);         
                        }

                    }
                    // everyone else...use Craft Email to send...@TODO -> offer phpmail option here??
                    else{
                        $sent = craft()->email->sendEmail($email);
                    }

                }
                //spam - honey pot failed or email was not on the whitelist
                else{
                    CommerceMailerPlugin::log('CommerceMailer spam trapped an email to : ' . $vars['toEmail']);
                    // but we pretend we've sent it...
                    $sent = true;
                }
                //we tried to send an email, log error if there was one...
                if (!$sent){
                    $errors[] = 'Sending email failed.';
                    CommerceMailerPlugin::logError('Sending email failed.');
                    craft()->urlManager->setRouteVariables(['errors' => $errors, 'message' => $email] );                            
                }
                //success!
                else{
                    craft()->userSession->setFlash('notice', 'CommerceMailer has sent an email to : ' . $vars['toEmail']);
                    CommerceMailerPlugin::log('CommerceMailer has sent an email to : ' . $vars['toEmail']);
                }
            }
            else{
                if(!$spam){
                    CommerceMailerPlugin::logError('CommerceMailer would have has sent an email to : ' . $vars['toEmail']);
                }
                else {
                    CommerceMailerPlugin::logError('CommerceMailer would have spam trapped an email to : ' . $vars['toEmail']);
                }
            }

            //only redirect on non ajax calls and if debugging isn't enabled.
            if (!$debugPOST and !$ajax){
                $this->redirectToPostedUrl();
            }

        }

        if ($debugPOST and $errors){
            echo '<h3>ERRORS</h3><pre>';
            print_r($errors);
            echo '</pre>';
            echo '<h3>MESSAGE</h3><pre>';
            print_r($vars['message']);
            echo '</pre>';
        }

        // Appropriate Ajax responses...
        if($ajax){
            if($errors){
                $this->returnErrorJson($errors);
            }
            else{
                $this->returnJson(["success"=>true]);
            }
        }
    }

}
