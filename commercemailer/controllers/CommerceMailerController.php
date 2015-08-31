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

    public function actionSendMail()
    {
        //Get plugin settings
        $settings = craft()->plugins->getPlugin('commerceMailer')->getSettings();
        
        //Called via Ajax?
        $ajax = craft()->request->isAjaxRequest();

        //Settings to control behavour when testing - we don't want to debug via ajax or it stuffs up the JSON response...
        $debug = ($settings->debug and !$ajax);
        $emailing = $settings->emailing;
 
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

        //hold a list of possible errors to pass back to template on error
        $errors = array();

        // Swap in values from settings if 'default' supplied
        if (isset($vars['toEmail'])){
            if ($vars['toEmail']==="default"){
                $vars['toEmail'] = $settings->defaultEmail;               
            }
        }
        if (isset($vars['toName'])){
            if ($vars['toName']==="default"){
                $vars['toName'] = $settings->defaultName;
            }
        }
 
        // create an EmailModel, populate it, we'll vaidate it later
        $email = EmailModel::populateModel($vars);

        //validate the email model 
        //put all our errors in one place, and return the email model if invalid
        $valid = $email->validate();
        if (!$valid){
            $vars['email'] = $email;
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

        if (isset($templateFolder) and isset($vars['template']) and !$errors){
            // parse the html template
            $htmlBody = craft()->templates->render($templateFolder . "/" . $vars['template'], $vars);
            if ($debug) {
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
            craft()->urlManager->setRouteVariables(['errors' => $errors, 'email' => $email] );
        } 
        else {

            if($emailing){
                $sent = craft()->email->sendEmail($email);
                if (!$sent){
                    $errors[] = 'craft()->email->sendEmail failed.';
                    $this->logError('craft()->email->sendEmail failed.');
                    craft()->urlManager->setRouteVariables(['errors' => $errors, 'email' => $email] );                            
                }
                else{
                    craft()->userSession->setFlash('market', 'CommerceMailer has sent an email to : ' . $vars['toEmail']);
                    $this->logInfo('CommerceMailer has sent an email to : ' . $vars['toEmail']);
                }
            }
            else {
                $this->logInfo('CommerceMailer would have has sent an email to : ' . $vars['toEmail']);
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
