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
        return '0.0.3';
    }

    function getDeveloper()
    {
        return 'Jeremy Daalder';
    }

    function getDeveloperUrl()
    {
        return '';
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
        );

        return craft()->templates->render('commercemailer/_settings', $variables);

   }
}