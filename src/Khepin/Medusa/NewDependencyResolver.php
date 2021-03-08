<?php


namespace Khepin\Medusa;


class NewDependencyResolver
{
    protected string $base_url = '';
    protected $curl;
    protected array $packages = [];
    protected string $providers_url = '';

    public function __construct(string $url='https://mirrors.aliyun.com/composer/') {
        if (file_exists('cache.json')) {
            $this->packages = json_decode(file_get_contents('cache.json'), true);
        }
        $this->base_url = $url;
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => ["Connection: keep-alive"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => false,
        ]);

        $base = $this->query('packages.json');
        $this->providers_url = $base['providers-url'];
        foreach ($base['provider-includes'] as $package => $hash) {
            $url = str_replace(['%hash%'], [$hash['sha256']], $package);
            foreach($this->query($url)['providers'] as $package => $hash) {
                $this->packages[$package] = $hash['sha256'];
            }
        }
        //file_put_contents('cache.json', json_encode($this->packages, JSON_UNESCAPED_UNICODE));
    }

    public function package(string $package): ?array {
        if (!isset($this->packages[$package])) {
            return null;
        }
        $hash = $this->packages[$package];
        $url = str_replace(['%package%', '%hash%'], [$package, $hash], $this->providers_url);

        return $this->query($url)['packages'][$package];
    }


    protected function query(string $file): array {
        $ch = $this->curl;
        if ($file[0] == '/') {
            $base = parse_url($this->base_url);
            $url = str_replace($base['path'], $file, $this->base_url);
        } else {
            $url = sprintf('%s%s', $this->base_url, $file);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        //$start = gettimeofday(true);
        //printf(">>> %15.3f url=%s\n", $start, $url);
        $r = curl_exec($ch);
        //$stop = gettimeofday(true);
        //printf("time: %6.3f\n", ($stop - $start));


        if (!$r || curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
            throw new \Error("HTTP Query Failed: path={$file}");
        }
        $data = @json_decode($r, true);
        if (!$data) {
            throw new \Error("HTTP Query Failed: path={$file} content=$r");
        }
        return $data;
    }

    static public function instance(): self {
        static $_ = null;
        if (is_null($_)) {
            $_ = new Static();
        }
        return $_;
    }
}

//$d = new NewDependencyResolver();
//var_dump($d->package('deployer/deployer'));
