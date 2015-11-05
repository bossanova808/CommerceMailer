<?php
namespace Craft;

class CommerceMailerPlugin extends BasePlugin
{

    protected $settings;

    public function init()
    {
        $this->settings = $this->getSettings();
    }

    function getName()
    {
         return Craft::t('Mailer for Commerce');
    }

    function getVersion()
    {
        return '0.0.4';
    }

    function getDeveloper()
    {
        return 'Jeremy Daalder';
    }

    function getDeveloperUrl()
    {
        return 'https://github.com/bossanova808';
    }

    function getDocumentationUrl(){
        return 'https://github.com/bossanova808/CommerceMailer';
    }

    function getDescription(){
        return 'Commerce Mailer helps you set up forms for emailing product enquiries and customer cart/order details.';
    }

    function getReleaseFeedUrl(){
        return 'https://raw.githubusercontent.com/bossanova808/craft-plugin-updates/master/updates-commercemailer.json';
    }

    function hasSettings(){
        return true;
    }

    public function defineSettings()
    {
        return array(
            'internalDomain'                => AttributeType::String,
            'whitelistedNames'              => AttributeType::String,
            'honeypotField'                 => AttributeType::String,
            'templateFolder'                => AttributeType::String,
            'appendSenderToSubject'         => AttributeType::Bool,
            'debug'                         => AttributeType::Bool,
            'emailing'                      => AttributeType::Bool,
        );
    }

    public function getSettingsHtml()
    {

        $settings = $this->settings;

        $variables = array(
            'name'     => $this->getName(true),
            'version'  => $this->getVersion(),
            'settings' => $settings,
            'description' => $this->getDescription(),
        );

        return craft()->templates->render('commercemailer/_settings', $variables);

   }
}