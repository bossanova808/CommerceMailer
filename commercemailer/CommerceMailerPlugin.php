<?php
namespace Craft;

class CommerceMailerPlugin extends BasePlugin
{
    private $version = "0.1.3";
    private $schemaVersion = "0.0.0";

    private $name = 'Commerce Mailer';
    private $description = 'Commerce Mailer helps you set up forms for emailing product enquiries and customer cart/order details.';
    private $documentationUrl = 'https://github.com/bossanova808/CommerceMailer';
    private $developer = "Jeremy Daalder";
    private $developerUrl = "https://github.com/bossanova808";
    private $releaseFeedUrl = "https://raw.githubusercontent.com/bossanova808/CommerceMailer/master/releases.json";

    protected static $settings;

    public function init()
    {
        self::$settings = $this->getSettings();
    }

    /**
     * Static log functions for this plugin
     *
     * @param mixed $msg
     * @param string $level
     * @param bool $force
     *
     * @return null
     */
    public static function logError($msg){
        CommerceMailerPlugin::log($msg, LogLevel::Error, $force = true);
    }
    public static function logWarning($msg){
        CommerceMailerPlugin::log($msg, LogLevel::Warning, $force = true);
    }
    // If debugging is set to true in this plugin's settings, then log every message, devMode or not.
    public static function log($msg, $level = LogLevel::Info, $force = false)
    {
        if(self::$settings['debug']) $force=true;

        if (is_string($msg))
        {
            $msg = "\n\n" . $msg . "\n";
        }
        else
        {
            $msg = "\n\n" . print_r($msg, true) . "\n";
        }

        parent::log($msg, $level, $force);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getDocumentationUrl()
    {
        return $this->documentationUrl;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getSchemaVersion()
    {
        return $this->schemaVersion;
    }

    public function getDeveloper()
    {
        return $this->developer;
    }

    public function getDeveloperUrl()
    {
        return $this->developerUrl;
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
            'debugPOST'                     => AttributeType::Bool,            
            'emailing'                      => AttributeType::Bool,
        );
    }

    public function getSettingsHtml()
    {

        $settings = self::$settings;

        $variables = array(
            'name'     => $this->getName(true),
            'version'  => $this->getVersion(),
            'settings' => $settings,
            'description' => $this->getDescription(),
        );

        return craft()->templates->render('commercemailer/_settings', $variables);

   }
}