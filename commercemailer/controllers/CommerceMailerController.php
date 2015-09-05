<?php
namespace Craft;


class CommerceMailerController extends BaseController
{

    protected $allowAnonymous = array('actionEmailProductEnquiry');

    private function logError($error){
        CommerceMailerPlugin::log($error, LogLevel::Error);
    }

    private function logInfo($message){
        CommerceMailerPlugin::log($message, LogLevel::Info);
    }


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
        $debug = ($settings->debug and !$ajax);
        $emailing = $settings->emailing;

        $savedBody = false;

        //Must be called by POST
        $this->requirePostRequest();
        //We'll return all the POST data to the template, so kick of our return data with that...
        $vars = craft()->request->getPost();
  
        //Dump POST to page if debugging
        if ($debug){
            echo '<h3>POST</h3><pre>';
            print_r($vars);
            echo '</pre>';
        }

        //Is this spam? Assume false.  
        $spam = false;
        $spam = !$this->validateHoneypot($settings->honeypotField);

        //If it's an internal email, make sure it's in the whitelist of names
        $emailWhitelist = array_map('trim', explode(',', $settings->whitelistedNames));
        if ($debug){
            echo '<h3>Whitelist</h3><pre>';
            print_r($emailWhitelist);
            echo '</pre>';
        }
        if (isset($vars['internalName'])){
            $spam = $spam && in_array($vars['internalName'], $emailWhitelist);
            if (!$spam){
                $vars['toEmail'] = $vars['internalName'] . "@" . $settings->internalDomain;
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
                    $vars['body'] = $compiledMessage;
                }
            }
            else
            {
                $vars['body'] = $postedMessage;
            }
        }
 
        // create an EmailModel & populate it
        $email = EmailModel::populateModel($vars);


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
            $vars['product'] = craft()->market_product->getById($vars['productId']);
        } 
        if (isset($vars['orderId'])) {
            $vars['order'] = craft()->market_order->getById($vars['orderId']);
        } 
        $vars['cart'] = craft()->market_cart->getCart();


        //Actual template to load is built by using the settings templateFolder + the form's POST var with name 'template'
        $templateFolder = $settings->templateFolder;
        if (!isset($templateFolder)) {
            $errors[] = 'No template folder in Commerce Mailer settings';
        } 

        if (!isset($vars['template'])) {
            $errors[] = 'No template in POST';
        } 

        //@TODO - what to do about the plain text body - will be unrendered...
        if (isset($templateFolder) and isset($vars['template']) and !$errors){
            // parse the html template
            $htmlBody = craft()->templates->render($templateFolder . "/" . $vars['template'], $vars);
            if ($debug) {
                print("<h4>Subject: " . $vars['subject'] ."</h4>");
                print($htmlBody);
            }
            $email->htmlBody = $htmlBody;      
        }               

        //OK, actually do something....unless we have an error....
        if ($errors) {
            $errors[] = 'Email not sent.';
            foreach ($errors as $error) {
                $this->logError($error);
            }
            craft()->urlManager->setRouteVariables(['errors' => $errors, 'message' => $email] );
        } 
        else {

            if($emailing){
                $sent = false;
                //attempt to send the email
                if (!$spam){                    
                    $sent = craft()->email->sendEmail($email);
                }
                //spam - honey pot failed or email was not on the whitelist
                else{
                    $this->logInfo('CommerceMailer spam trapped an email to : ' . $vars['toEmail']);
                    // but we pretend we've sent it...
                    $sent = true;
                }
                //we tried to send an email, log the result..
                if (!$sent){
                    $errors[] = 'craft()->email->sendEmail failed.';
                    $this->logError('craft()->email->sendEmail failed.');
                    craft()->urlManager->setRouteVariables(['errors' => $errors, 'message' => $email] );                            
                }
                //success!
                else{
                    craft()->userSession->setFlash('market', 'CommerceMailer has sent an email to : ' . $vars['toEmail']);
                    $this->logInfo('CommerceMailer has sent an email to : ' . $vars['toEmail']);
                }
            }
            else{
                if(!$spam){
                    $this->logInfo('CommerceMailer would have has sent an email to : ' . $vars['toEmail']);
                }
                else {
                    $this->logInfo('CommerceMailer would have spam trapped an email to : ' . $vars['toEmail']);
                }
            }

            //only redirect on non ajax calls and if debugging isn't enabled.
            if (!$debug and !$ajax){
                $this->redirectToPostedUrl();
            }

        }

        if ($debug and $errors){
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
