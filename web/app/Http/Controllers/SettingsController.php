<?php

namespace App\Http\Controllers;

use App\Services\ConfManagerService;
use App\Services\DTO\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{

    /**
     * Field to group mappings (to identify which panel (category) they should exist under)
     * @var array
     */
    private $fieldGroups = [

        'timezone' => Setting::GROUP_GENERAL,
        'callsign' => Setting::GROUP_GENERAL,
        'enabled' => Setting::GROUP_GENERAL,
        'courtesy_tone' => Setting::GROUP_GENERAL,
        'auto_ident' => Setting::GROUP_GENERAL,
        'ident_interval' => Setting::GROUP_GENERAL,
        'delayed_playback_interval' => Setting::GROUP_GENERAL,
        'pl_tone' => Setting::GROUP_GENERAL,
        'transmit_mode' => Setting::GROUP_GENERAL,
        'ident_time' => Setting::GROUP_GENERAL,
        //'ident_morse' => Setting::GROUP_GENERAL,

        //'record_device' => SettingEntity::GROUP_AUDIO,

        //'morse_wpm' => Setting::GROUP_MORSE,
        //'morse_frequency' => Setting::GROUP_MORSE,
        //'morse_output_volume' => Setting::GROUP_MORSE,

        'store_recordings' => Setting::GROUP_STORAGE,
        'purge_recording_after' => Setting::GROUP_STORAGE,

        'web_interface_enabled' => Setting::GROUP_WEBINTERFACE,
        'web_interface_port' => Setting::GROUP_WEBINTERFACE,
        'web_interface_bind_ip' => Setting::GROUP_WEBINTERFACE,
        'web_interface_logging' => Setting::GROUP_WEBINTERFACE,
        'web_gps_enabled' => Setting::GROUP_WEBINTERFACE,

        'tripwire_enabled' => Setting::GROUP_TRIPWIRE,
        'tripwire_url' => Setting::GROUP_TRIPWIRE,
        'tripwire_ignore_interval' => Setting::GROUP_TRIPWIRE,
        'tripwire_request_timeout' => Setting::GROUP_TRIPWIRE,

        'in_cor_pin' => Setting::GROUP_GPIO,
        'out_ptt_pin' => Setting::GROUP_GPIO,
        'out_ready_led_pin' => Setting::GROUP_GPIO,
        'out_rx_led_pin' => Setting::GROUP_GPIO,
        'out_tx_led_pin' => Setting::GROUP_GPIO,
        'cos_pin_invert' => Setting::GROUP_GPIO,
        'ptt_pin_invert' => Setting::GROUP_GPIO,
        'ready_pin_invert' => Setting::GROUP_GPIO,
        'rx_pin_invert' => Setting::GROUP_GPIO,
        'tx_pin_invert' => Setting::GROUP_GPIO,

    ];

    /**
     * A list of fields that will be rendered as a checkbox.
     * @var array
     */
    private $booleanFields = [
        'enabled',
        'auto_ident',
        'ident_time',
        'ident_morse',
        'store_recordings',
        'web_interface_enabled',
        'web_interface_logging',
        'web_gps_enabled',
        'tripwire_enabled',
        'rx_pin_invert',
        'tx_pin_invert',
        'cos_pin_invert',
        'ptt_pin_invert',
        'ready_pin_invert',
    ];

    /**
     * Optionally override a label
     * @var array
     */
    private $labelOverrides = [
        'enabled' => 'Enable Repeater',
        'tripwire_enabled' => 'Enable Tripwire',
        'purge_recording_after' => 'Purge recordings after (days)',
        'web_interface_bind_ip' => 'Web Interface Bind IP',
        'web_gps_enabled' => 'Web GPS Data Enabled',
    ];

    /**
     * Settings that should be ignored (not outputted to the settings screen)
     * @var array
     */
    private $ignoredSettings = [
        'ident_morse', // Future feature
        'record_device', // Disabling as this should ALWAYS be 'alsa' when running on a RPi
        'morse_wpm', // Future feature
        'morse_frequency', // Future feature
        'morse_output_volume', // Future feature
    ];

    private $fieldComments = [

        // General
        'timezone' => ['The timezone you wish to use for logging, TTS services, and the web interface (if enabled)'],
        'callsign' => [
            'Simplex repeater (ident) code',
            'This is phonetically transmitted if you enable the "Auto Ident" feature below.'
        ],
        'enabled' => [
            'Enable the "repeat" functionality.',
            'Optionally you can disable the repeater and therefore disabling transmission. This is',
            'useful if you wanted to record received transmissions eg. running Pirrot in surveillance mode.'
        ],
        'auto_ident' => [
            'Enable automatic identification?',
        ],
        'ident_interval' => [
            'When automatic identification is enabled, Pirrot will transmit the repeater identification every X seconds.',
            'The default value is "600" seconds (every 10 minutes).',
        ],
        'delayed_playback_interval' => [
            'You can optionally add a delay (in seconds) between the received transmission being re-transmitted by Pirrot.',
            'The default value is "0" (no delay, Pirrot will immediately repeat the transmission)',
        ],
        'courtesy_tone' => [
            'To disable courtesy tones set to: false',
            'Otherwise use the filename of the courtesy tone, eg. BeeBoo (without the .wav extension)'
        ],
        'pl_tone' => [
            'The PL/CTCSS to access the repeater',
            'Set to "false" if you do not have a CTCSS/PL code to access the repeater, otherwise set',
            'the CTCSS/PL tone here eg. "110.9" this will be "spoken" when the repeater transmits it\'s ident',
        ],
        'transmit_mode' => [
            'The Pirrot "listen" and transmission operation mode',
            '"vox" = Voice Operated (auto-record and then transmit when it "hears" mic input on the USB sound card.)',
            '"cor" = Carrier Operated Relay/Switch (record and then transmit when the COR/COS GPIO pin is ON (aka. "high"))',
        ],
        'ident_time' => [
            'Transmit the time with the ident message.',
        ],
        'ident_morse' => [
            'Send morse code with the ident (coming in the future!)',
        ],

        // Storage
        'store_recordings' => [
            'Enable saving of recordings. These can then be played or downloaded from the "Audio Recordings" section ',
            'of the Pirrot Web Interface.',
        ],
        'purge_recording_after' => [
            'Purge recording after (X days), 0 to disable purging of recordings.',
        ],

        // Web Interface
        'web_interface_enabled' => ['Enable the light-weight web interface'],
        'web_interface_port' => ['The TCP port to listen on'],
        'web_interface_bind_ip' => ['The IP address to bind to (default: 0.0.0.0)'],
        'web_interface_logging' => ['Enable logging of web server access logs to /var/log/pirrot-web.log'],
        'web_gps_enabled' => [
            'Enable GPS position and other data on the web dashboard view.',
            '* You MUST setup and configure the device and ensure that the GPS receiver is connected to the RaspberryPi.',
            '* Having this setting enabled but no device connected will cause the web interface to become unresponsive!'
        ],

        // Tripwire
        'tripwire_enabled' => ['Enable the tripwire feature (sends a web hook when transmission is received)'],
        'tripwire_url' => [
            'The URL to send the HTTP request payload to when the "tripwire" is activated (a transmission is received)',
            'eg. http://yourwebsite.com/my-tripwire-handler-endpoint'
        ],
        'tripwire_ignore_interval' => [
            'This value will ensure that further transmissions within this time period (in seconds) do not trigger',
            'additional HTTP web hook requests (default value is 300)'
        ],
        'tripwire_request_timeout' => ['HTTP request timeout (in seconds)'],

        // GPIO
        'in_cor_pin' => ['The GPIO input pin (BCM) number to use for the COS relay (required if running in COS mode).'],
        'out_ptt_pin' => ['The GPIO output pin (BCM) number to use for the PTT relay.'],
        'out_ready_led_pin' => ['The "Ready status" LED output pin (BCM) number'],
        'out_rx_led_pin' => ['The "RX" LED output pin (BCM) number'],
        'out_tx_led_pin' => ['The "TX" LED output pin (BCM) number'],
        'cos_pin_invert' => ['COS Pin is inverted?'],
        'ptt_pin_invert' => ['PTT Pin is inverted?'],
        'ready_pin_invert' => ['Ready LED pin is inverted?'],
        'rx_pin_invert' => ['RX (Recieve) LED pin is inverted?'],
        'tx_pin_invert' => ['TX (Transmit) LED pin is inverted?'],
    ];

    /**
     * Renders the Settings page.
     * @return \Illuminate\View\View
     */
    public function showSettingsPage()
    {

        $configFilePath = dirname(__DIR__) . '/../../../build/configs/pirrot_default.conf';
        if (file_exists('/etc/pirrot.conf')) {
            $configFilePath = '/etc/pirrot.conf';
        }

        // Get setting values from the configuration file.
        $config = new ConfManagerService($configFilePath);
        $configValues = $config->read();

        // Regex out the setting values and comments to provide a list of settings that we can render out.
        foreach ($this->fieldGroups as $field => $group) {

            if (!key_exists($field, $this->labelOverrides)) {
                $label = ucwords(str_replace('_', ' ', $field));
            } else {
                $label = $this->labelOverrides[$field];
            }

            // Get the value from the settings file...
            $value = $configValues[$field];

            $inputType = Setting::TYPE_TEXT;
            if (in_array($field, $this->booleanFields)) {
                $inputType = Setting::TYPE_BOOL;
            }

            $inputComments = null;
            if (key_exists($field, $this->fieldComments)) {
                $inputComments = $this->fieldComments[$field];
            }

            $panelInputs[$group][] = new Setting($field, $label, $group, $value, $inputType, $inputComments);
        }


        return view('_pages.settings')->with('panels', $panelInputs);
    }

    /**
     * Handles the updating of the settings and restarts the Pirrot daemon.
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function updateSettings(Request $request)
    {

        $configFilePath = dirname(__DIR__) . '/../../../build/configs/pirrot_default.conf';
        if (file_exists('/etc/pirrot.conf')) {
            $configFilePath = '/etc/pirrot.conf';
        }

        // Get setting values from the configuration file.
        $config = new ConfManagerService($configFilePath);
        $currentSettings = $config->read();
        $updateSettings = $request->json();
        $newSettings = [];
        foreach ($updateSettings as $setting) {
            $newSettings[$setting['name']] = $setting['value'];
        }

        // Remove settings that are on the "blacklist"/ignored list (prevent them being overwritten with "false")

        // Set all "boolean" type config items to "false" if the checkbox is not checked.
        $falseBooleanValues = array_diff_key($currentSettings, $newSettings, $this->ignoredSettings);
        foreach ($falseBooleanValues as $key => $value) {
            $newSettings[$key] = "false";
        }

        $updatedConfig = $config->update($newSettings);

        // Get the current request URL so we can manipulate it for the auto-refresh after the service has been restarted.
        $url = parse_url(request()->root());
        $response =
            [
                'check_url' => $url['scheme'] . "://" . $url['host'] . ':' . $newSettings['web_interface_port'] . '/up',
                'after_url' => $url['scheme'] . "://" . $url['host'] . ':' . $newSettings['web_interface_port'] . '/settings',
            ];

        // We will only write the new configuration file and attempt to restart the Pirrot daemon ONLY if it's actually running on a RPi.
        if (env('APP_ENV') !== 'production') {
            $response =
                [
                    'check_url' => request()->root() . '/up',
                    'after_url' => request()->root() . '/settings',
                ];
            return response($response, 200);
        }

        // Backup the old configuration file and then write the new file...
        system("cp " . $configFilePath . " /opt/pirrot/storage/backups/pirrot-" . date("dmYHis") . ".conf");
        file_put_contents('/etc/pirrot.conf', $updatedConfig);

        // Trigger a daemon restart (after two seconds to give us enough time to respond to the HTTP request)
        system('sudo /opt/pirrot/web/resources/scripts/restart-pirrot.sh > /dev/null &');

        return response($response, 200);
    }

}
