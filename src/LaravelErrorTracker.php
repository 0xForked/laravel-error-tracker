<?php

namespace PollieDev\LaravelErrorTracker;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\Process\Process;

class LaravelErrorTracker
{

    static $reported = false;

    protected $exception;
    protected $snippetLineCount = 31;

    public static function Report(\Exception $exception) {
        if (!self::$reported) {
            (new self())->handle(request(), $exception);
            self::$reported = true;
        }
    }

    protected function handle(Request $request, \Exception $exception) {
        if (config('app.debug') || !config('error-tracker.base_url')) {
            return;
        }

        $this->exception = $exception;

        $vars = [
            'website'      => $request->getHost(),
            'message'      => $exception->getMessage(),
            'env'          => [
                'laravel_version'       => app()->version(),
                'laravel_locale'        => app()->getLocale(),
                'laravel_config_cached' => app()->configurationIsCached(),
                'php_version'           => phpversion()
            ],
            'route'        => [
                'uri'        => $request->route()->uri,
                'methods'    => $request->route()->methods,
                'action'     => $request->route()->action,
                'parameters' => $request->route()->parameters
            ],
            'request'      => [
                'url'       => $request->getUri(),
                'ip'        => $request->getClientIp(),
                'method'    => $request->getMethod(),
                'useragent' => $request->headers->get('User-Agent'),
                'headers'   => $request->headers->all()
            ],
            'request_data' => [
                'queryString' => $request->query->all(),
                'body'        => $request->request->all(),
                'files'       => $request->files->all(),
                'session'     => session()->all()
            ],
            'git'          => [
                'hash'    => $this->hash(),
                'message' => $this->message(),
                'tag'     => $this->tag(),
                'remote'  => $this->remote(),
                'isDirty' => !$this->isClean(),
            ],
            'frames'       => [],
            'meta' => $this->getMetaData()
        ];

        $currentFile = $exception->getFile();
        $currentLine = $exception->getLine();

        foreach ($exception->getTrace() as $rawFrame) {
            $vars["frames"][] = [
                'line_number'          => $currentLine,
                'method'               => $rawFrame['function'] ?? null,
                'class'                => $rawFrame['class'] ?? null,
                'code_snippet'         => $this->getFileSnippet($currentFile, $currentLine),
                'file'                 => str_replace(base_path(), '', $currentFile),
                'filePath'             => $currentFile,
                'is_application_frame' => $this->frameFileFromApplication($currentFile),
            ];

            $currentFile = $rawFrame['file'] ?? 'unknown';
            $currentLine = $rawFrame['line'] ?? 0;
        }

        $this->request($vars);
    }

    protected function command($command) {
        $process = (new \ReflectionClass(Process::class))->hasMethod('fromShellCommandline')
            ? Process::fromShellCommandline($command, base_path())
            : new Process($command, base_path());

        $process->run();

        return trim($process->getOutput());
    }

    public function hash(): ?string {
        return $this->command("git log --pretty=format:'%H' -n 1");
    }

    public function message(): ?string {
        return $this->command("git log --pretty=format:'%s' -n 1");
    }

    public function tag(): ?string {
        return $this->command('git describe --tags --abbrev=0');
    }

    public function remote(): ?string {
        return $this->command('git config --get remote.origin.url');
    }

    public function isClean(): bool {
        return empty($this->command('git status -s'));
    }

    public function frameFileFromApplication($frameFilename) {
        $applicationPath = base_path();
        $relativeFile = str_replace('\\', '/', $frameFilename);

        if (!empty($applicationPath)) {
            $relativeFile = array_reverse(explode($applicationPath ?? '', $frameFilename, 2))[0];
        }

        if (strpos($relativeFile, '/vendor') === 0) {
            return false;
        }

        return true;
    }

    public function getFileSnippet(string $fileName, $currentLine) {
        if (!file_exists($fileName)) {
            return [];
        }

        try {
            $file = new File($fileName);

            [$startLineNumber, $endLineNumber] = $this->getBounds($file->numberOfLines(), $currentLine);

            $code = [];

            $line = $file->getLine($startLineNumber);

            $currentLineNumber = $startLineNumber;

            while ($currentLineNumber <= $endLineNumber) {
                $code[$currentLineNumber] = rtrim(substr($line, 0, 250));

                $line = $file->getNextLine();
                $currentLineNumber++;
            }

            return $code;
        } catch(RuntimeException $exception) {
            return [];
        }
    }

    private function getBounds($totalNumberOfLineInFile, $currentLine): array {
        $startLine = max($currentLine - floor($this->snippetLineCount / 2), 1);
        $endLine = $startLine + ($this->snippetLineCount - 1);

        if ($endLine > $totalNumberOfLineInFile) {
            $endLine = $totalNumberOfLineInFile;
            $startLine = max($endLine - ($this->snippetLineCount - 1), 1);
        }

        return [$startLine, $endLine];
    }

    private function getMetaData() {
        $metaData = config('error-tracker.metaData');
        foreach ($metaData as $key => $value) {
            if ($value instanceof Closure) {
                $metaData[$key] = $value();
            }
        }
        return $metaData;
    }


    private function request($payload) {
        try {
            $client = new Client(['verify' => false]);
            $client->post(static::getUrl(), [
                RequestOptions::JSON => $payload
            ]);
        } catch(\Exception $e) {
        }
    }

    private static function getUrl(): string {
        $url = config('error-tracker.base_url');

        return trim($url, '/') . "/api/report";
    }
}
