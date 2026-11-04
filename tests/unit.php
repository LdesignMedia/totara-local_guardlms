<?php
/**
 * Standalone plugin regression tests. Totara APIs are explicit test doubles.
 * These checks do not constitute Totara runtime or end-to-end verification.
 */
define('MOODLE_INTERNAL', true);
$values = [];
$CFG = (object)['wwwroot' => 'https://totara.example.test', 'totara_release' => '19.0.22',
    'totara_version' => '2024100100', 'dataroot' => sys_get_temp_dir() . '/totara-unit-' . bin2hex(random_bytes(8)),
    'libdir' => __DIR__ . '/fixtures'];
$SESSION = new stdClass();
$USER = (object)['id' => 7];
function get_config($plugin, $key) { global $values; return $values[$key] ?? false; }
function set_config($key, $value, $plugin) { global $values; $values[$key] = $value; }
function unset_config($key, $plugin) { global $values; unset($values[$key]); }
class moodle_exception extends RuntimeException {
    public function __construct($key, ...$args) { parent::__construct($key); }
}
class moodle_url {
    public string $url;
    public array $params;
    public function __construct($url, $params = []) { $this->url = $url; $this->params = $params; }
}
class core_plugin_manager {
    public static array $plugins = [];
    public static function instance() { return new self(); }
    public function get_plugins() { return self::$plugins; }
}
class curl {
    public static array $calls = [];
    public static int $status = 200;
    public static int $errno = 0;
    public static array $data = [];
    private array $headers;
    public function __construct($options) {}
    public function setHeader($headers) { $this->headers = $headers; }
    public function post($url, $body, $options) {
        self::$calls[] = compact('url', 'body', 'options') + ['headers' => $this->headers];
        return json_encode(['data' => self::$data]);
    }
    public function get_info() { return ['http_code' => self::$status]; }
    public function get_errno() { return self::$errno; }
}
$DB = new class {
    public function get_dbfamily() { return 'postgres'; }
    public function get_server_info() { return ['version' => '14.18', 'password' => 'never-report']; }
};
foreach (['config', 'credentials', 'collector', 'client', 'connector'] as $name) {
    require __DIR__ . '/../classes/local/' . $name . '.php';
}
use local_guardlms\local\config;
use local_guardlms\local\credentials;
use local_guardlms\local\collector;
use local_guardlms\local\client;
use local_guardlms\local\connector;
$count = 0;
function check(bool $condition, string $name): void {
    global $count;
    if (!$condition) { throw new RuntimeException('FAIL ' . $name); }
    $count++;
    echo 'PASS ' . $name . "\n";
}
function refuses(callable $operation, string $key): void {
    try { $operation(); } catch (moodle_exception $e) {
        check($e->getMessage() === $key, $key);
        return;
    }
    throw new RuntimeException('Expected rejection ' . $key);
}
try {
    check(config::baseurl() === 'https://dashboard.guardlms.com', 'default HTTPS origin');
    foreach (['http://receiver.test', 'https://user:pass@receiver.test', 'https://receiver.test?key=x', 'https://receiver.test/#x'] as $url) {
        set_config('baseurl', $url, 'local_guardlms');
        refuses(fn() => config::baseurl(), 'error:baseurl');
    }
    set_config('baseurl', 'https://receiver.test', 'local_guardlms');
    check(credentials::get() === '', 'disconnected has no credential');
    credentials::store('test-only-secret');
    check(credentials::get() === 'test-only-secret', 'encrypted credential round-trip');
    check(!str_contains(json_encode($values), 'test-only-secret'), 'config contains ciphertext only');
    check((fileperms($CFG->dataroot . '/local_guardlms/key') & 0777) === 0600, 'key file permissions');
    $CFG->wwwroot = 'https://clone.example.test';
    check(credentials::get() === '', 'cloned site cannot use credential');
    $CFG->wwwroot = 'https://totara.example.test';
    set_config('baseurl', 'https://other-service.test', 'local_guardlms');
    check(credentials::get() === '', 'changed service cannot receive credential');
    $values['credentialservice'] = 'https://other-service.test';
    refuses(fn() => credentials::get(), 'error:credentials');
    $values['credentialservice'] = 'https://receiver.test';
    set_config('baseurl', 'https://receiver.test', 'local_guardlms');
    $original = $values['credential'];
    $values['credential'] = base64_encode(str_repeat('a', 40));
    refuses(fn() => credentials::get(), 'error:credentials');
    $values['credential'] = $original;
    $plugin = new class {
        public $component = 'local_guardlms';
        public $versiondb = '2026110400';
        public $versiondisk = '2026110500';
        public function is_enabled() { return null; }
    };
    $uninstalled = clone $plugin;
    $uninstalled->component = 'local_uninstalled';
    $uninstalled->versiondb = null;
    core_plugin_manager::$plugins = ['local' => ['guardlms' => $plugin, 'uninstalled' => $uninstalled]];
    $payload = collector::build_payload();
    check(count($payload['totara']['plugins']) === 1, 'only database-installed plugins');
    check($payload['totara']['plugins'][0]['version'] === '2026110400', 'installed version wins over pending disk upgrade');
    check($payload['totara']['plugins'][0]['enabled'] === null, 'unknown enabled state stays unknown');
    check($payload['totara']['release'] === '19.0.22', 'Totara release reported separately');
    check(!isset($payload['moodle']) && !isset($payload['totara']['plugins'][0]['updates']), 'no invented Moodle inventory or update verdict');
    check(!str_contains(json_encode($payload), 'never-report'), 'database secrets excluded');
    unset($CFG->totara_release);
    refuses(fn() => collector::build_payload(), 'error:nottotara');
    $CFG->totara_release = '19.0.22';
    $registered = core_plugin_manager::$plugins;
    core_plugin_manager::$plugins = [];
    refuses(fn() => collector::build_payload(), 'error:inventory');
    core_plugin_manager::$plugins = $registered;
    $bad = new class {
        public $component = 'local_broken';
        public $versiondb = '2026110400';
        public function is_enabled() { throw new RuntimeException('registry failure'); }
    };
    core_plugin_manager::$plugins['local']['broken'] = $bad;
    $before = count(curl::$calls);
    set_config('enabled', 1, 'local_guardlms');
    try { connector::push(); } catch (RuntimeException $e) {}
    check(count(curl::$calls) === $before, 'registry failure sends no partial inventory');
    unset(core_plugin_manager::$plugins['local']['broken']);
    curl::$data = ['stale' => false, 'components_upserted' => 4];
    connector::push();
    check(!empty($values['lastpush']), 'accepted push updates freshness');
    $call = end(curl::$calls);
    check($call['url'] === 'https://receiver.test/api/externalpush/totara', 'fixed Totara endpoint');
    check($call['options']['CURLOPT_SSL_VERIFYPEER'] && $call['options']['CURLOPT_SSL_VERIFYHOST'] === 2, 'TLS peer and hostname verification');
    check($call['options']['CURLOPT_FOLLOWLOCATION'] === false, 'no credential-bearing redirects');
    curl::$status = 401;
    refuses(fn() => connector::push(), 'error:http');
    check(!empty($values['authrejected']), 'rejected key prompts reconnect');
    curl::$status = 200;
    connector::push();
    check(empty($values['authrejected']), 'accepted retry clears key warning');
    $url = connector::start();
    check($url->url === 'https://receiver.test/connect/totara', 'Totara consent route');
    $state = $url->params['state'];
    refuses(fn() => connector::complete(str_repeat('a', 64), 'wrong'), 'error:state');
    refuses(fn() => connector::complete(str_repeat('a', 64), $state), 'error:state');
    $url = connector::start();
    $SESSION->local_guardlms_connect['expires'] = time() - 1;
    refuses(fn() => connector::complete(str_repeat('a', 64), $url->params['state']), 'error:state');
    $url = connector::start();
    $USER->id = 8;
    refuses(fn() => connector::complete(str_repeat('a', 64), $url->params['state']), 'error:state');
    $USER->id = 7;
    $url = connector::start();
    curl::$data = ['token' => 'new-test-token', 'verification_token' => 'fixture-verification', 'expires_at' => '2027-01-01'];
    connector::complete(str_repeat('a', 64), $url->params['state']);
    check(credentials::get() === 'new-test-token', 'successful exchange stores encrypted token');
    check(!empty($values['lastpush']) && $values['verificationtoken'] === 'fixture-verification', 'successful connect sends initial push');
    connector::disconnect();
    check(credentials::get() === '' && empty($values['enabled']), 'disconnect drops key and reporting');
    check(empty($values['verificationtoken']) && !isset($SESSION->local_guardlms_connect), 'disconnect clears ownership and pending state');
    echo "\n{$count} standalone regression checks passed (Totara APIs doubled).\n";
} finally {
    foreach (glob($CFG->dataroot . '/local_guardlms/*') ?: [] as $file) { unlink($file); }
    if (is_dir($CFG->dataroot . '/local_guardlms')) { rmdir($CFG->dataroot . '/local_guardlms'); }
    if (is_dir($CFG->dataroot)) { rmdir($CFG->dataroot); }
}
